<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php?page=login");
    exit;
}

// ── Handle POST: cairkan dana
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rental_id = (int) ($_POST['rental_id'] ?? 0);
    $aksi      = $_POST['aksi'] ?? '';

    if ($rental_id > 0 && $aksi === 'cairkan_dana') {
        if (!empty($_FILES['bukti_pencairan']['name']) && $_FILES['bukti_pencairan']['error'] === 0) {
            $ext     = strtolower(pathinfo($_FILES['bukti_pencairan']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            if (in_array($ext, $allowed)) {
                $bukti_file = 'pencairan_' . $rental_id . '_' . time() . '.' . $ext;
                $dest       = '../uploads/bukti/' . $bukti_file;
                if (move_uploaded_file($_FILES['bukti_pencairan']['tmp_name'], $dest)) {
                    $conn->prepare("
                        UPDATE rentals
                        SET status_pencairan = 'sudah_dicairkan',
                            bukti_pencairan  = ?,
                            tanggal_pencairan = NOW()
                        WHERE id = ?
                    ")->execute([$bukti_file, $rental_id]);
                }
            }
        }
    }

    $tab = $_GET['tab'] ?? 'belum';
    header("Location: pencairan.php?tab=$tab");
    exit;
}

// ── Stats
$total_belum   = (int) $conn->query("
    SELECT COUNT(*) FROM rentals
    WHERE status_pinjam = 'selesai' AND status_pembayaran = 'lunas' AND status_pencairan = 'belum_dicairkan'
")->fetchColumn();

$total_sudah   = (int) $conn->query("
    SELECT COUNT(*) FROM rentals WHERE status_pencairan = 'sudah_dicairkan'
")->fetchColumn();

$total_nilai_belum = (int) $conn->query("
    SELECT COALESCE(SUM(total_harga - COALESCE(komisi_admin, ROUND(total_harga * 0.05))), 0)
    FROM rentals
    WHERE status_pinjam = 'selesai' AND status_pembayaran = 'lunas' AND status_pencairan = 'belum_dicairkan'
")->fetchColumn();

$total_nilai_sudah = (int) $conn->query("
    SELECT COALESCE(SUM(jumlah_dicairkan), 0)
    FROM rentals WHERE status_pencairan = 'sudah_dicairkan'
")->fetchColumn();

// ── Tab & Filter
$tab    = $_GET['tab']  ?? 'belum';
$search = trim($_GET['q'] ?? '');
$sort   = $_GET['sort'] ?? 'terbaru';

$where  = [];
$params = [];

if ($tab === 'belum') {
    $where[] = "r.status_pinjam = 'selesai' AND r.status_pembayaran = 'lunas' AND r.status_pencairan = 'belum_dicairkan'";
} elseif ($tab === 'sudah') {
    $where[] = "r.status_pencairan = 'sudah_dicairkan'";
} else {
    $where[] = "(r.status_pinjam = 'selesai' AND r.status_pembayaran = 'lunas')";
}

if ($search !== '') {
    $where[]      = "(i.nama_barang LIKE :q OR u.username LIKE :q OR pu.username LIKE :q)";
    $params[':q'] = "%$search%";
}

$order = match($sort) {
    'terlama'  => 'r.created_at ASC',
    'terbesar' => 'r.total_harga DESC',
    'terkecil' => 'r.total_harga ASC',
    default    => 'r.created_at DESC',
};

$sql = "
    SELECT r.*,
           i.nama_barang, i.harga, i.gambar, i.lokasi,
           u.username  AS penyewa,
           pu.username AS pemilik,
           pu.metode_pembayaran     AS pemilik_metode,
           pu.nama_penyedia         AS pemilik_penyedia,
           pu.nomor_rekening        AS pemilik_rekening,
           pu.nama_pemilik_rekening AS pemilik_nama_rek,
           pu.foto_qris             AS pemilik_foto_qris
    FROM rentals r
    JOIN items i  ON r.item_id = i.id
    JOIN users u  ON r.user_id = u.id
    JOIN users pu ON i.user_id = pu.id
    WHERE " . implode(' AND ', $where ?: ['1=1']) . "
    ORDER BY $order
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$av_colors = [
    ['#eef0ff','#3d4bff'], ['#e9f9f0','#1a7a46'],
    ['#fff7e6','#cc7a00'], ['#fce7f3','#9d174d'], ['#e4f7f5','#0d7d72'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pencairan Dana - ItemLend Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f4f5f7; color: #1a1d2e; min-height: 100vh; }
a { text-decoration: none; color: inherit; }

.admin-wrap { display: flex; min-height: 100vh; }
.main { margin-left: 220px; flex: 1; display: flex; flex-direction: column; }

/* Topbar */
.topbar { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 0 28px; height: 60px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
.topbar h1 { font-size: 17px; font-weight: 600; }
.topbar p  { font-size: 12px; color: #6b7280; margin-top: 1px; }
.topbar-right { display: flex; align-items: center; gap: 12px; }
.admin-pill { background: #eef0ff; color: #3d4bff; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
.avatar { width: 32px; height: 32px; border-radius: 50%; background: #3d4bff; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; }

.content { padding: 24px 28px; max-width: 1100px; }

/* Stats */
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
.stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px; display: flex; align-items: center; gap: 14px; }
.stat-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-icon i { font-size: 21px; }
.stat-icon.purple { background: #f5f3ff; color: #7c3aed; }
.stat-icon.green  { background: #e9f9f0; color: #16a34a; }
.stat-icon.amber  { background: #fff7e6; color: #cc7a00; }
.stat-icon.blue   { background: #eef0ff; color: #3d4bff; }
.stat-label { font-size: 11.5px; color: #6b7280; margin-bottom: 4px; }
.stat-value { font-size: 20px; font-weight: 800; line-height: 1; }
.stat-value.sm { font-size: 14px; }

/* Toolbar */
.toolbar { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 0 14px; height: 38px; flex: 1; min-width: 200px; }
.search-box i { color: #9ca3af; font-size: 16px; }
.search-box input { border: none; outline: none; font-family: inherit; font-size: 13px; background: transparent; width: 100%; }
.sort-select { height: 38px; border: 1px solid #e5e7eb; border-radius: 10px; padding: 0 12px; background: #fff; font-family: inherit; font-size: 13px; outline: none; }
.result-count { font-size: 13px; color: #6b7280; white-space: nowrap; }

/* Tabs */
.tab-bar { display: flex; gap: 6px; margin-bottom: 18px; overflow-x: auto; padding-bottom: 2px; scrollbar-width: none; }
.tab-bar::-webkit-scrollbar { display: none; }
.tab-pill { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 20px; white-space: nowrap; font-size: 13px; font-weight: 600; border: 1.5px solid #e5e7eb; color: #6b7280; background: #fff; transition: all 0.15s; }
.tab-pill:hover { border-color: #7c3aed; color: #7c3aed; }
.tab-pill.active { background: #7c3aed; border-color: #7c3aed; color: #fff; }
.tc { font-size: 10.5px; font-weight: 700; background: #f0f1f3; color: #6b7280; border-radius: 20px; padding: 1px 7px; }
.tab-pill.active .tc { background: rgba(255,255,255,0.25); color: #fff; }
.tc.urgent { background: #7c3aed; color: #fff; }

/* Export bar */
.export-bar { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px 18px; margin-bottom: 18px; flex-wrap: wrap; }
.export-bar-label { display: flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 700; color: #1a1d2e; white-space: nowrap; }
.export-bar-label i { font-size: 18px; color: #7c3aed; }
.export-sep { width: 1px; height: 24px; background: #e5e7eb; }
.export-input { height: 38px; border: 1.5px solid #e5e7eb; border-radius: 9px; padding: 0 12px; background: #fff; font-family: inherit; font-size: 13px; color: #1a1d2e; outline: none; }
.export-input:focus { border-color: #7c3aed; }
.export-input-group { display: flex; align-items: center; gap: 6px; }
.export-input-group label { font-size: 12px; font-weight: 600; color: #6b7280; white-space: nowrap; }
.export-input-wrap { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.btn-export { display: inline-flex; align-items: center; gap: 7px; padding: 9px 18px; background: #7c3aed; color: #fff; border: none; border-radius: 9px; font-family: inherit; font-size: 13px; font-weight: 700; cursor: pointer; transition: background 0.15s; white-space: nowrap; margin-left: auto; }
.btn-export:hover { background: #6d28d9; }
.btn-export i { font-size: 17px; }

/* Cards */
.pcairan-list { display: flex; flex-direction: column; gap: 12px; }

.pc-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
.pc-card.urgent { border-left: 4px solid #7c3aed; }
.pc-card.done   { border-left: 4px solid #16a34a; }

/* Head */
.pc-head { display: flex; align-items: center; gap: 14px; padding: 14px 18px; border-bottom: 1px solid #f0f1f3; }
.pc-thumb { width: 52px; height: 52px; border-radius: 10px; flex-shrink: 0; background: #f0f1f5; overflow: hidden; display: flex; align-items: center; justify-content: center; }
.pc-thumb img { width: 100%; height: 100%; object-fit: cover; }
.pc-thumb i { font-size: 22px; color: #d1d5db; }
.pc-info { flex: 1; min-width: 0; }
.pc-name { font-size: 14px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pc-meta { font-size: 12px; color: #6b7280; margin-top: 3px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.pc-meta i { font-size: 13px; }
.pc-right { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; flex-shrink: 0; }
.pc-total { font-size: 16px; font-weight: 800; color: #7c3aed; }
.pc-id { font-size: 11px; color: #9ca3af; }

/* Badges */
.sbadge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 20px; }
.sb-siapcair  { background: #f5f3ff; color: #7c3aed; border: 1px solid #ddd6fe; }
.sb-dicairkan { background: #e9f9f0; color: #16a34a; border: 1px solid #bbf7d0; }
.sb-nometode  { background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; }

/* Grid 3 kolom */
.pc-body { display: grid; grid-template-columns: 1fr 1fr 1fr; border-bottom: 1px solid #f0f1f3; }
.pc-cell { padding: 12px 18px; border-right: 1px solid #f0f1f3; display: flex; flex-direction: column; gap: 3px; }
.pc-cell:last-child { border-right: none; }
.pc-cell-label { font-size: 10.5px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.04em; }
.pc-cell-val   { font-size: 13px; font-weight: 600; color: #1a1d2e; display: flex; align-items: center; gap: 6px; }
.av-mini { width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 800; }

/* Nominal breakdown */
.nominal-row { padding: 12px 18px; border-bottom: 1px solid #f0f1f3; background: #faf9ff; display: flex; gap: 20px; flex-wrap: wrap; align-items: center; }
.nominal-item { display: flex; flex-direction: column; gap: 2px; }
.nominal-label { font-size: 10.5px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.04em; }
.nominal-val   { font-size: 14px; font-weight: 800; }
.nominal-val.total   { color: #1a1d2e; }
.nominal-val.komisi  { color: #dc2626; }
.nominal-val.dicairkan { color: #7c3aed; }
.nominal-sep { color: #d1d5db; font-size: 18px; }

/* Tujuan transfer — non-QRIS */
.tujuan-row { padding: 12px 18px; border-bottom: 1px solid #f0f1f3; background: #f5f3ff; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.tujuan-row i { font-size: 18px; color: #7c3aed; flex-shrink: 0; }
.tujuan-detail { flex: 1; }
.tujuan-title { font-size: 12px; font-weight: 700; color: #6d28d9; margin-bottom: 2px; }
.tujuan-val   { font-size: 13.5px; font-weight: 800; color: #1a1d2e; }
.tujuan-sub   { font-size: 11.5px; color: #6b7280; }

/* QRIS row */
.qris-row { padding: 14px 18px; border-bottom: 1px solid #f0f1f3; background: #f5f3ff; display: flex; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
.qris-left { display: flex; flex-direction: column; gap: 4px; flex: 1; }
.qris-title { font-size: 12px; font-weight: 700; color: #6d28d9; display: flex; align-items: center; gap: 6px; }
.qris-title i { font-size: 16px; }
.qris-name  { font-size: 14px; font-weight: 800; color: #1a1d2e; margin-top: 2px; }
.qris-sub   { font-size: 11.5px; color: #6b7280; }
.qris-img-wrap { flex-shrink: 0; }
.qris-img {
    width: 130px; height: 130px;
    object-fit: contain;
    border-radius: 10px;
    border: 2px solid #ddd6fe;
    background: #fff;
    cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s;
}
.qris-img:hover { transform: scale(1.03); box-shadow: 0 4px 16px rgba(124,58,237,0.18); }
.qris-img-label { font-size: 10px; font-weight: 700; color: #7c3aed; text-align: center; margin-top: 5px; letter-spacing: 0.04em; }

/* Lightbox */
.lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 9999; align-items: center; justify-content: center; }
.lightbox.active { display: flex; }
.lightbox img { max-width: 90vw; max-height: 88vh; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
.lightbox-close { position: absolute; top: 18px; right: 22px; background: rgba(255,255,255,0.15); color: #fff; border: none; border-radius: 50%; width: 36px; height: 36px; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.15s; }
.lightbox-close:hover { background: rgba(255,255,255,0.3); }

/* No metode warning */
.nometode-row { padding: 12px 18px; border-bottom: 1px solid #f0f1f3; background: #fff5f5; display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #dc2626; font-weight: 600; }
.nometode-row i { font-size: 16px; }

/* Sudah dicairkan row */
.done-row { padding: 12px 18px; border-bottom: 1px solid #f0f1f3; background: #f0fdf4; display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.done-row i { font-size: 18px; color: #16a34a; flex-shrink: 0; }
.done-detail { flex: 1; }
.done-title { font-size: 12px; font-weight: 700; color: #16a34a; margin-bottom: 3px; }
.done-sub { font-size: 12px; color: #6b7280; display: flex; gap: 14px; flex-wrap: wrap; }

/* Footer form pencairan */
.pc-footer { padding: 14px 18px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.pc-footer label { font-size: 12px; font-weight: 600; color: #6b7280; }
.pc-footer input[type="file"] { font-size: 12px; font-family: inherit; }
.btn-cairkan { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 9px; background: #7c3aed; color: #fff; border: none; font-family: inherit; font-size: 13px; font-weight: 700; cursor: pointer; transition: background 0.15s; }
.btn-cairkan:hover { background: #6d28d9; }
.btn-cairkan i { font-size: 15px; }
.btn-bukti { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; background: #fff; border: 1px solid #bbf7d0; border-radius: 8px; font-size: 12px; font-weight: 600; color: #16a34a; }
.btn-bukti:hover { background: #f0fdf4; }

/* Empty */
.empty-state { text-align: center; padding: 56px; color: #9ca3af; background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; }
.empty-state i  { font-size: 48px; display: block; margin-bottom: 12px; color: #e5e7eb; }
.empty-state h3 { font-size: 14px; font-weight: 700; color: #6b7280; }

@media (max-width: 860px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .pc-body { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 580px) {
    .pc-body { grid-template-columns: 1fr; }
    .pc-cell { border-right: none; border-bottom: 1px solid #f0f1f3; }
    .content { padding: 16px; }
    .qris-img { width: 100px; height: 100px; }
}
</style>
</head>
<body>
<div class="admin-wrap">

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="topbar">
            <div>
                <h1>Pencairan Dana</h1>
                <p>Transfer hasil sewa ke pemilik barang setelah transaksi selesai</p>
            </div>
            <div class="topbar-right">
                <span class="admin-pill">Admin</span>
                <div class="avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
            </div>
        </div>

        <div class="content">

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="ti ti-wallet"></i></div>
                    <div>
                        <div class="stat-label">Perlu Dicairkan</div>
                        <div class="stat-value"><?= $total_belum ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="ti ti-cash"></i></div>
                    <div>
                        <div class="stat-label">Total Nilai Tertahan</div>
                        <div class="stat-value sm">Rp <?= number_format($total_nilai_belum, 0, ',', '.') ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="ti ti-circle-check"></i></div>
                    <div>
                        <div class="stat-label">Sudah Dicairkan</div>
                        <div class="stat-value"><?= $total_sudah ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="ti ti-trending-up"></i></div>
                    <div>
                        <div class="stat-label">Total Dicairkan</div>
                        <div class="stat-value sm">Rp <?= number_format($total_nilai_sudah, 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <form method="GET" action="" id="filterForm">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                <div class="toolbar">
                    <div class="search-box">
                        <i class="ti ti-search"></i>
                        <input type="text" name="q" placeholder="Cari barang, penyewa, pemilik..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <select name="sort" class="sort-select" onchange="document.getElementById('filterForm').submit()">
                        <option value="terbaru"  <?= $sort === 'terbaru'  ? 'selected' : '' ?>>Terbaru</option>
                        <option value="terlama"  <?= $sort === 'terlama'  ? 'selected' : '' ?>>Terlama</option>
                        <option value="terbesar" <?= $sort === 'terbesar' ? 'selected' : '' ?>>Total Terbesar</option>
                        <option value="terkecil" <?= $sort === 'terkecil' ? 'selected' : '' ?>>Total Terkecil</option>
                    </select>
                    <span class="result-count"><?= count($rows) ?> transaksi</span>
                </div>
            </form>

            <!-- Export -->
            <div class="export-bar">
                <div class="export-bar-label">
                    <i class="ti ti-file-spreadsheet"></i> Export Laporan Pencairan
                </div>
                <div class="export-sep"></div>
                <form method="GET" action="export_pencairan.php" target="_blank"
                      style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;flex:1;">
                    <div class="export-input-wrap">
                        <div class="export-input-group">
                            <label>Dari</label>
                            <input type="date" name="dari" class="export-input"
                                   value="<?= date('Y-m-01') ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="export-input-group">
                            <label>Sampai</label>
                            <input type="date" name="sampai" class="export-input"
                                   value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="export-input-group">
                            <label>Status</label>
                            <select name="status" class="export-input" style="min-width:160px;">
                                <option value="semua">Semua</option>
                                <option value="belum_dicairkan">Belum Dicairkan</option>
                                <option value="sudah_dicairkan">Sudah Dicairkan</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-export">
                        <i class="ti ti-download"></i> Download Excel
                    </button>
                </form>
            </div>

            <!-- Tabs -->
            <div class="tab-bar">
                <?php
                $tabs_def = [
                    'belum' => ['Perlu Dicairkan', 'ti-wallet',       $total_belum, $total_belum > 0 ? 'urgent' : ''],
                    'sudah' => ['Sudah Dicairkan',  'ti-circle-check', $total_sudah, ''],
                    'semua' => ['Semua',            'ti-list',         $total_belum + $total_sudah, ''],
                ];
                foreach ($tabs_def as $key => [$label, $icon, $cnt, $badge_cls]):
                    $active = $tab === $key ? 'active' : '';
                ?>
                <a href="?tab=<?= $key ?><?= $search ? '&q='.urlencode($search) : '' ?>"
                   class="tab-pill <?= $active ?>">
                    <i class="ti <?= $icon ?>"></i> <?= $label ?>
                    <span class="tc <?= $badge_cls ?>"><?= $cnt ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- List -->
            <?php if (empty($rows)): ?>
            <div class="empty-state">
                <i class="ti ti-wallet-off"></i>
                <h3>Tidak ada data pencairan di kategori ini</h3>
            </div>
            <?php else: ?>
            <div class="pcairan-list">
                <?php foreach ($rows as $r):
                    $spc  = $r['status_pencairan'] ?? 'belum_dicairkan';
                    $dur  = (int) ((strtotime($r['tanggal_selesai']) - strtotime($r['tanggal_mulai'])) / 86400);
                    $tot  = $r['total_harga'] ?: ($dur * $r['harga']);
                    $komisi    = $r['komisi_admin']     ?: (int) round($tot * 0.05);
                    $dicairkan = $r['jumlah_dicairkan']  ?: ($tot - $komisi);

                    // Gambar barang
                    $g = null;
                    if (!empty($r['gambar'])) {
                        $list  = json_decode($r['gambar'], true);
                        $first = (is_array($list) && !empty($list[0])) ? $list[0] : $r['gambar'];
                        if (file_exists("../uploads/" . $first)) $g = "../uploads/" . $first;
                    }

                    $cp = $av_colors[abs(crc32($r['penyewa']  ?? '')) % 5];
                    $co = $av_colors[abs(crc32($r['pemilik']  ?? '')) % 5];
                    $ip = strtoupper(substr($r['penyewa']  ?? '?', 0, 2));
                    $io = strtoupper(substr($r['pemilik']  ?? '?', 0, 2));

                    $has_metode  = !empty($r['pemilik_metode']);
                    $is_qris     = $has_metode
                                   && strtolower($r['pemilik_penyedia'] ?? '') === 'qris'
                                   && !empty($r['pemilik_foto_qris']);

                    // Path foto QRIS
                    $qris_path = null;
                    if ($is_qris) {
                        $qris_file = $r['pemilik_foto_qris'];
                        // File disimpan dengan prefix timestamp di kolom foto_qris (lihat data: '1785854610_qris_...')
                        $try = "../uploads/" . $qris_file;
                        if (file_exists($try)) $qris_path = $try;
                    }

                    $badge = $spc === 'sudah_dicairkan'
                        ? '<span class="sbadge sb-dicairkan"><i class="ti ti-circle-check"></i> Sudah Dicairkan</span>'
                        : ($has_metode
                            ? '<span class="sbadge sb-siapcair"><i class="ti ti-wallet"></i> Siap Dicairkan</span>'
                            : '<span class="sbadge sb-nometode"><i class="ti ti-alert-circle"></i> Metode Belum Ada</span>');

                    $card_cls = $spc === 'sudah_dicairkan' ? 'done' : 'urgent';
                    $qris_uid = 'qris_' . $r['id'];
                ?>
                <div class="pc-card <?= $card_cls ?>">

                    <!-- Head -->
                    <div class="pc-head">
                        <div class="pc-thumb">
                            <?php if ($g): ?><img src="<?= htmlspecialchars($g) ?>" alt=""><?php else: ?><i class="ti ti-box-seam"></i><?php endif; ?>
                        </div>
                        <div class="pc-info">
                            <div class="pc-name"><?= htmlspecialchars($r['nama_barang']) ?></div>
                            <div class="pc-meta">
                                <?php if (!empty($r['lokasi'])): ?><span><i class="ti ti-map-pin"></i> <?= htmlspecialchars($r['lokasi']) ?></span><?php endif; ?>
                                <span><i class="ti ti-calendar"></i> <?= $dur ?> hari</span>
                                <span><i class="ti ti-clock"></i> Selesai <?= date('d M Y', strtotime($r['tanggal_selesai'])) ?></span>
                            </div>
                        </div>
                        <div class="pc-right">
                            <?= $badge ?>
                            <div class="pc-total">Rp <?= number_format($dicairkan, 0, ',', '.') ?></div>
                            <div class="pc-id">#<?= str_pad($r['id'], 6, '0', STR_PAD_LEFT) ?></div>
                        </div>
                    </div>

                    <!-- Info 3 kolom -->
                    <div class="pc-body">
                        <div class="pc-cell">
                            <div class="pc-cell-label">Penyewa</div>
                            <div class="pc-cell-val">
                                <div class="av-mini" style="background:<?= $cp[0] ?>;color:<?= $cp[1] ?>;"><?= $ip ?></div>
                                <?= htmlspecialchars($r['penyewa']) ?>
                            </div>
                        </div>
                        <div class="pc-cell">
                            <div class="pc-cell-label">Pemilik Barang</div>
                            <div class="pc-cell-val">
                                <div class="av-mini" style="background:<?= $co[0] ?>;color:<?= $co[1] ?>;"><?= $io ?></div>
                                <?= htmlspecialchars($r['pemilik']) ?>
                            </div>
                        </div>
                        <div class="pc-cell">
                            <div class="pc-cell-label">Periode Sewa</div>
                            <div class="pc-cell-val" style="font-size:12.5px;">
                                <i class="ti ti-calendar-event"></i>
                                <?= date('d M Y', strtotime($r['tanggal_mulai'])) ?> →
                                <?= date('d M Y', strtotime($r['tanggal_selesai'])) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Nominal breakdown -->
                    <div class="nominal-row">
                        <div class="nominal-item">
                            <div class="nominal-label">Total Sewa</div>
                            <div class="nominal-val total">Rp <?= number_format($tot, 0, ',', '.') ?></div>
                        </div>
                        <span class="nominal-sep">−</span>
                        <div class="nominal-item">
                            <div class="nominal-label">Komisi Admin (5%)</div>
                            <div class="nominal-val komisi">Rp <?= number_format($komisi, 0, ',', '.') ?></div>
                        </div>
                        <span class="nominal-sep">=</span>
                        <div class="nominal-item">
                            <div class="nominal-label">Ditransfer ke Pemilik</div>
                            <div class="nominal-val dicairkan">Rp <?= number_format($dicairkan, 0, ',', '.') ?></div>
                        </div>
                    </div>

                    <?php if ($spc === 'sudah_dicairkan'): ?>
                    <!-- Sudah dicairkan -->
                    <div class="done-row">
                        <i class="ti ti-circle-check"></i>
                        <div class="done-detail">
                            <div class="done-title">Dana sudah dicairkan</div>
                            <div class="done-sub">
                                <?php if (!empty($r['tanggal_pencairan'])): ?>
                                <span><i class="ti ti-calendar"></i> <?= date('d M Y H:i', strtotime($r['tanggal_pencairan'])) ?></span>
                                <?php endif; ?>
                                <?php if ($has_metode): ?>
                                <span><i class="ti ti-credit-card"></i> <?= htmlspecialchars($r['pemilik_penyedia']) ?>
                                    <?php if (!$is_qris): ?>— <?= htmlspecialchars($r['pemilik_rekening']) ?><?php endif; ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($r['bukti_pencairan'])): ?>
                        <a href="../uploads/bukti/<?= htmlspecialchars($r['bukti_pencairan']) ?>"
                           target="_blank" class="btn-bukti">
                            <i class="ti ti-eye" style="font-size:13px;"></i> Lihat Bukti
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php elseif ($is_qris): ?>
                    <!-- Tujuan transfer: QRIS → tampilkan foto QR -->
                    <div class="qris-row">
                        <div class="qris-left">
                            <div class="qris-title"><i class="ti ti-qrcode"></i> Tujuan Transfer · QRIS</div>
                            <div class="qris-name"><?= htmlspecialchars($r['pemilik_nama_rek'] ?: $r['pemilik']) ?></div>
                            <div class="qris-sub">Scan QR di samping untuk transfer</div>
                        </div>
                        <div class="qris-img-wrap">
                            <?php if ($qris_path): ?>
                                <img
                                    src="<?= htmlspecialchars($qris_path) ?>"
                                    alt="QR Code <?= htmlspecialchars($r['pemilik']) ?>"
                                    class="qris-img"
                                    onclick="openLightbox('<?= htmlspecialchars($qris_path) ?>')"
                                    title="Klik untuk perbesar"
                                >
                                <div class="qris-img-label">KLIK UNTUK PERBESAR</div>
                            <?php else: ?>
                                <div style="width:130px;height:130px;border-radius:10px;border:2px dashed #ddd6fe;background:#f5f3ff;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;color:#9ca3af;font-size:12px;text-align:center;padding:8px;">
                                    <i class="ti ti-photo-off" style="font-size:24px;color:#c4b5fd;"></i>
                                    File QR tidak ditemukan
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Form cairkan (QRIS) -->
                    <form method="POST" enctype="multipart/form-data" class="pc-footer">
                        <input type="hidden" name="rental_id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="aksi" value="cairkan_dana">
                        <label>Bukti transfer:</label>
                        <input type="file" name="bukti_pencairan" accept=".jpg,.jpeg,.png,.pdf" required>
                        <button type="submit" class="btn-cairkan"
                                onclick="return confirm('Konfirmasi: Rp <?= number_format($dicairkan, 0, ',', '.') ?> sudah ditransfer via QRIS ke <?= addslashes($r['pemilik_nama_rek'] ?? $r['pemilik']) ?>?')">
                            <i class="ti ti-send"></i> Tandai Sudah Dicairkan
                        </button>
                    </form>

                    <?php elseif ($has_metode): ?>
                    <!-- Tujuan transfer: Bank / e-wallet non-QRIS -->
                    <div class="tujuan-row">
                        <i class="ti ti-<?= $r['pemilik_metode'] === 'bank' ? 'building-bank' : 'device-mobile' ?>"></i>
                        <div class="tujuan-detail">
                            <div class="tujuan-title">Tujuan Transfer · <?= ucfirst($r['pemilik_metode']) ?></div>
                            <div class="tujuan-val"><?= htmlspecialchars($r['pemilik_penyedia']) ?> — <?= htmlspecialchars($r['pemilik_rekening']) ?></div>
                            <div class="tujuan-sub">a.n. <?= htmlspecialchars($r['pemilik_nama_rek']) ?></div>
                        </div>
                    </div>

                    <!-- Form cairkan (non-QRIS) -->
                    <form method="POST" enctype="multipart/form-data" class="pc-footer">
                        <input type="hidden" name="rental_id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="aksi" value="cairkan_dana">
                        <label>Bukti transfer:</label>
                        <input type="file" name="bukti_pencairan" accept=".jpg,.jpeg,.png,.pdf" required>
                        <button type="submit" class="btn-cairkan"
                                onclick="return confirm('Konfirmasi: Rp <?= number_format($dicairkan, 0, ',', '.') ?> sudah ditransfer ke <?= addslashes($r['pemilik_nama_rek'] ?? $r['pemilik']) ?>?')">
                            <i class="ti ti-send"></i> Tandai Sudah Dicairkan
                        </button>
                    </form>

                    <?php else: ?>
                    <!-- Belum ada metode -->
                    <div class="nometode-row">
                        <i class="ti ti-alert-circle"></i>
                        Pemilik belum setup metode pembayaran — belum bisa dicairkan
                    </div>
                    <?php endif; ?>

                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Lightbox untuk foto QRIS -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()"><i class="ti ti-x"></i></button>
    <img id="lightbox-img" src="" alt="QR Code">
</div>

<script>
function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
});
</script>
</body>
</html>