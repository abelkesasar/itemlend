<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php?page=login");
    exit;
}

// ── Handle aksi POST (konfirmasi / update status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rental_id = (int) ($_POST['rental_id'] ?? 0);
    $aksi      = $_POST['aksi'] ?? '';

    if ($rental_id > 0) {
        switch ($aksi) {
            case 'konfirmasi_bayar':
                $conn->prepare("UPDATE rentals SET status_pembayaran='lunas', paid_at=NOW() WHERE id=?")
                     ->execute([$rental_id]);
                break;
            case 'tolak_bayar':
                $catatan = trim($_POST['catatan'] ?? '');
                $conn->prepare("UPDATE rentals SET status_pembayaran='ditolak', catatan_admin=? WHERE id=?")
                     ->execute([$catatan, $rental_id]);
                break;
            case 'mulai_pinjam':
                $conn->prepare("UPDATE rentals SET status_pinjam='sedang_dipinjam' WHERE id=?")
                     ->execute([$rental_id]);
                break;
            case 'selesai_pinjam':
                $conn->prepare("UPDATE rentals SET status_pinjam='selesai' WHERE id=?")
                     ->execute([$rental_id]);
                break;
        }
    }
    header("Location: rentals.php" . ($_GET['tab'] ? "?tab=".$_GET['tab'] : ''));
    exit;
}

// ── Stats
$pending_users = $conn->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$total_rentals = $conn->query("SELECT COUNT(*) FROM rentals")->fetchColumn();
$total_revenue = $conn->query("SELECT SUM(total_harga) FROM rentals WHERE status_pembayaran='lunas'")->fetchColumn() ?? 0;
$menunggu_konfirmasi = $conn->query("SELECT COUNT(*) FROM rentals WHERE status_pembayaran='menunggu_konfirmasi'")->fetchColumn();

// ── Tab & Search
$tab    = $_GET['tab']    ?? 'semua';
$search = trim($_GET['q'] ?? '');
$sort   = $_GET['sort']   ?? 'terbaru';

// ── Build query
$where  = ["1=1"];
$params = [];

if ($search !== '') {
    $where[]         = "(i.nama_barang LIKE :q OR u.username LIKE :q OR pu.username LIKE :q)";
    $params[':q']    = "%$search%";
}

$tab_where = match($tab) {
    'menunggu_konfirmasi' => "r.status_pembayaran = 'menunggu_konfirmasi'",
    'lunas'               => "r.status_pembayaran = 'lunas'",
    'ditolak'             => "r.status_pembayaran = 'ditolak'",
    'sedang_dipinjam'     => "r.status_pinjam = 'sedang_dipinjam'",
    'selesai'             => "r.status_pinjam = 'selesai'",
    'pending'             => "r.status_pembayaran = 'pending'",
    default               => "1=1",
};
$where[] = $tab_where;

$order = match($sort) {
    'terlama' => 'r.created_at ASC',
    'terbesar'=> 'r.total_harga DESC',
    'terkecil'=> 'r.total_harga ASC',
    default   => 'r.created_at DESC',
};

$sql = "
    SELECT r.*,
           i.nama_barang, i.harga, i.gambar, i.lokasi,
           u.username  AS penyewa,
           pu.username AS pemilik
    FROM rentals r
    JOIN items i  ON r.item_id  = i.id
    JOIN users u  ON r.user_id  = u.id
    JOIN users pu ON i.user_id  = pu.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY $order
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count per tab
function countTab(PDO $conn, string $cond): int {
    return (int) $conn->query("
        SELECT COUNT(*) FROM rentals r
        JOIN items i ON r.item_id = i.id
        JOIN users u ON r.user_id = u.id
        JOIN users pu ON i.user_id = pu.id
        WHERE $cond
    ")->fetchColumn();
}
$tab_counts = [
    'semua'               => $total_rentals,
    'pending'             => countTab($conn, "r.status_pembayaran='pending'"),
    'menunggu_konfirmasi' => $menunggu_konfirmasi,
    'lunas'               => countTab($conn, "r.status_pembayaran='lunas'"),
    'ditolak'             => countTab($conn, "r.status_pembayaran='ditolak'"),
    'sedang_dipinjam'     => countTab($conn, "r.status_pinjam='sedang_dipinjam'"),
    'selesai'             => countTab($conn, "r.status_pinjam='selesai'"),
];

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
    <title>Kelola Rental - ItemLend Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f4f5f7; color: #1a1d2e; min-height: 100vh; }
        a { text-decoration: none; color: inherit; }

        /* ── Topbar ── */
        .topbar {
            background: #fff; border-bottom: 1px solid #e5e7eb;
            padding: 0 28px; height: 60px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar h1 { font-size: 17px; font-weight: 600; }
        .topbar p  { font-size: 12px; color: #6b7280; margin-top: 1px; }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .admin-pill { background: #eef0ff; color: #3d4bff; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
        .avatar { width: 32px; height: 32px; border-radius: 50%; background: #3d4bff; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; }

        /* ── Content ── */
        .content { padding: 24px 28px; }

        /* ── Stats ── */
        .stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 24px; }
        .stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px; display: flex; align-items: center; gap: 14px; }
        .stat-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .stat-icon i { font-size: 21px; }
        .stat-icon.blue   { background: #eef0ff; color: #3d4bff; }
        .stat-icon.green  { background: #e9f9f0; color: #16a34a; }
        .stat-icon.amber  { background: #fff7e6; color: #cc7a00; }
        .stat-icon.teal   { background: #e4f7f5; color: #0d7d72; }
        .stat-label { font-size: 12px; color: #6b7280; margin-bottom: 3px; }
        .stat-value { font-size: 22px; font-weight: 800; color: #1a1d2e; line-height: 1; }

        /* ── Toolbar ── */
        .toolbar { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
        .search-box {
            display: flex; align-items: center; gap: 8px;
            background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
            padding: 0 14px; height: 38px; flex: 1; min-width: 200px;
        }
        .search-box i { color: #9ca3af; font-size: 16px; }
        .search-box input { border: none; outline: none; font-family: inherit; font-size: 13px; background: transparent; width: 100%; }
        .sort-select { height: 38px; border: 1px solid #e5e7eb; border-radius: 10px; padding: 0 12px; background: #fff; font-family: inherit; font-size: 13px; outline: none; }
        .result-count { font-size: 13px; color: #6b7280; white-space: nowrap; }

        /* ── Tabs ── */
        .tab-bar { display: flex; gap: 6px; margin-bottom: 18px; overflow-x: auto; padding-bottom: 2px; scrollbar-width: none; }
        .tab-bar::-webkit-scrollbar { display: none; }
        .tab-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 20px; white-space: nowrap;
            font-size: 13px; font-weight: 600;
            border: 1.5px solid #e5e7eb; color: #6b7280; background: #fff;
            transition: all 0.15s; flex-shrink: 0;
        }
        .tab-pill:hover { border-color: #3d4bff; color: #3d4bff; }
        .tab-pill.active { background: #3d4bff; border-color: #3d4bff; color: #fff; }
        .tc { font-size: 10.5px; font-weight: 700; background: #f0f1f3; color: #6b7280; border-radius: 20px; padding: 1px 7px; }
        .tab-pill.active .tc { background: rgba(255,255,255,0.25); color: #fff; }
        .tc.red { background: #ff5c5c; color: #fff; }

        /* ── Rental cards ── */
        .rental-list { display: flex; flex-direction: column; gap: 12px; }

        .rental-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
            overflow: hidden;
        }
        .rental-card.urgent { border-left: 4px solid #f59e0b; }

        /* Header baris */
        .rc-head {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 18px; border-bottom: 1px solid #f0f1f3;
        }
        .rc-thumb {
            width: 52px; height: 52px; border-radius: 10px; flex-shrink: 0;
            background: #f0f1f5; overflow: hidden;
            display: flex; align-items: center; justify-content: center;
        }
        .rc-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .rc-thumb i   { font-size: 22px; color: #d1d5db; }
        .rc-info { flex: 1; min-width: 0; }
        .rc-name { font-size: 14px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .rc-meta { font-size: 12px; color: #6b7280; margin-top: 3px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .rc-meta i { font-size: 13px; }
        .rc-right { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; flex-shrink: 0; }
        .rc-total { font-size: 16px; font-weight: 800; color: #3d4bff; }
        .rc-id    { font-size: 11px; color: #9ca3af; }

        /* Status badges */
        .sbadge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 20px; }
        .sbadge i { font-size: 12px; }
        .sb-pending    { background: #f0f1f3; color: #6b7280; }
        .sb-waiting    { background: #fefce8; color: #a16207; border: 1px solid #fde68a; }
        .sb-lunas      { background: #e9f9f0; color: #16a34a; border: 1px solid #bbf7d0; }
        .sb-ditolak    { background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; }
        .sb-pinjam     { background: #eff6ff; color: #2563eb; border: 1px solid #93c5fd; }
        .sb-selesai    { background: #f4f5f7; color: #6b7280; border: 1px solid #d1d5db; }

        /* Body: user info + dates */
        .rc-body {
            display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0;
            border-bottom: 1px solid #f0f1f3;
        }
        .rc-cell {
            padding: 12px 18px; border-right: 1px solid #f0f1f3;
            display: flex; flex-direction: column; gap: 3px;
        }
        .rc-cell:last-child { border-right: none; }
        .rc-cell-label { font-size: 10.5px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.04em; }
        .rc-cell-val   { font-size: 13px; font-weight: 600; color: #1a1d2e; display: flex; align-items: center; gap: 6px; }
        .rc-cell-val i { font-size: 15px; }

        /* Avatar mini */
        .av-mini { width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 800; }

        /* Bukti pembayaran */
        .bukti-row {
            padding: 10px 18px; border-bottom: 1px solid #f0f1f3;
            display: flex; align-items: center; gap: 10px;
            background: #fffbeb;
        }
        .bukti-row i { font-size: 16px; color: #d97706; flex-shrink: 0; }
        .bukti-row span { font-size: 12.5px; color: #92400e; flex: 1; }
        .btn-bukti {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 12px; background: #fff; border: 1px solid #fed7aa;
            border-radius: 7px; font-size: 12px; font-weight: 600; color: #d97706;
            cursor: pointer; transition: background 0.15s;
        }
        .btn-bukti:hover { background: #fff7e6; }

        /* Catatan admin (ditolak) */
        .catatan-row {
            padding: 10px 18px; border-bottom: 1px solid #f0f1f3;
            background: #fff5f5; font-size: 12.5px; color: #dc2626;
            display: flex; align-items: center; gap: 8px;
        }
        .catatan-row i { flex-shrink: 0; }

        /* Progress bar */
        .progress-row {
            padding: 10px 18px; border-bottom: 1px solid #f0f1f3;
            display: flex; align-items: center; gap: 10px;
            background: #eff6ff;
        }
        .progress-row i { font-size: 15px; color: #2563eb; flex-shrink: 0; }
        .prog-bar-wrap { flex: 1; background: #dbeafe; border-radius: 20px; height: 6px; overflow: hidden; }
        .prog-bar { height: 100%; background: #3d4bff; border-radius: 20px; }
        .prog-text { font-size: 12px; font-weight: 700; color: #1d4ed8; white-space: nowrap; }

        /* Footer aksi */
        .rc-footer { padding: 12px 18px; display: flex; gap: 8px; flex-wrap: wrap; }

        .btn-aksi {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 8px;
            font-size: 12.5px; font-weight: 600; cursor: pointer;
            border: none; font-family: inherit; transition: all 0.15s;
        }
        .btn-aksi i { font-size: 15px; }
        .btn-konfirmasi { background: #3d4bff; color: #fff; }
        .btn-konfirmasi:hover { background: #2c38d4; }
        .btn-tolak   { background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; }
        .btn-tolak:hover { background: #fee2e2; }
        .btn-mulai   { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .btn-mulai:hover { background: #dbeafe; }
        .btn-selesai { background: #e9f9f0; color: #16a34a; border: 1px solid #bbf7d0; }
        .btn-selesai:hover { background: #dcfce7; }
        .btn-view    { background: #f4f5f7; color: #374151; border: 1px solid #e5e7eb; }
        .btn-view:hover { background: #e5e7eb; }

        /* Modal tolak */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.4); z-index: 999;
            align-items: center; justify-content: center;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: #fff; border-radius: 16px; padding: 28px;
            width: 100%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        .modal-title { font-size: 16px; font-weight: 800; margin-bottom: 8px; color: #1a1d2e; }
        .modal-sub   { font-size: 13px; color: #6b7280; margin-bottom: 16px; }
        .modal-box textarea {
            width: 100%; border: 1.5px solid #e5e7eb; border-radius: 10px;
            padding: 10px 14px; font-family: inherit; font-size: 13px;
            resize: none; outline: none; margin-bottom: 14px;
        }
        .modal-box textarea:focus { border-color: #3d4bff; }
        .modal-actions { display: flex; gap: 8px; }
        .btn-modal-ok  { flex: 1; padding: 10px; background: #dc2626; color: #fff; border: none; border-radius: 9px; font-family: inherit; font-size: 13.5px; font-weight: 700; cursor: pointer; }
        .btn-modal-cancel { padding: 10px 20px; background: #f4f5f7; color: #6b7280; border: none; border-radius: 9px; font-family: inherit; font-size: 13.5px; font-weight: 600; cursor: pointer; }

        /* Empty state */
        .empty-state { text-align: center; padding: 56px; color: #9ca3af; background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; }
        .empty-state i { font-size: 48px; display: block; margin-bottom: 12px; color: #e5e7eb; }
        .empty-state h3 { font-size: 14px; font-weight: 700; color: #6b7280; }

        /* Responsive */
        @media (max-width: 860px) {
            .stats-grid { grid-template-columns: repeat(2,1fr); }
            .rc-body { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 580px) {
            .rc-body { grid-template-columns: 1fr; }
            .rc-cell { border-right: none; border-bottom: 1px solid #f0f1f3; }
        }
    </style>
</head>
<body>
<div class="admin-wrap" style="display:flex;min-height:100vh;">

    <?php include 'sidebar.php'; ?>

    <div class="main">

        <!-- Topbar -->
        <div class="topbar">
            <div>
                <h1>Kelola Rental</h1>
                <p>Monitor semua transaksi sewa & konfirmasi pembayaran</p>
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
                    <div class="stat-icon blue"><i class="ti ti-shopping-cart"></i></div>
                    <div>
                        <div class="stat-label">Total Rental</div>
                        <div class="stat-value"><?= $total_rentals ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="ti ti-hourglass"></i></div>
                    <div>
                        <div class="stat-label">Perlu Dikonfirmasi</div>
                        <div class="stat-value"><?= $menunggu_konfirmasi ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="ti ti-cash"></i></div>
                    <div>
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-value" style="font-size:16px;">Rp <?= number_format($total_revenue, 0, ',', '.') ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon teal"><i class="ti ti-clock-play"></i></div>
                    <div>
                        <div class="stat-label">Sedang Dipinjam</div>
                        <div class="stat-value"><?= $tab_counts['sedang_dipinjam'] ?></div>
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
                        <option value="terbaru"  <?= $sort==='terbaru'  ?'selected':'' ?>>Terbaru</option>
                        <option value="terlama"  <?= $sort==='terlama'  ?'selected':'' ?>>Terlama</option>
                        <option value="terbesar" <?= $sort==='terbesar' ?'selected':'' ?>>Total Terbesar</option>
                        <option value="terkecil" <?= $sort==='terkecil' ?'selected':'' ?>>Total Terkecil</option>
                    </select>
                    <button type="submit" style="display:none"></button>
                    <span class="result-count"><?= count($rentals) ?> transaksi</span>
                </div>
            </form>

            <!-- Tabs -->
            <div class="tab-bar">
                <?php
                $tabs_def = [
                    'semua'               => ['label'=>'Semua',               'icon'=>'ti-list'],
                    'pending'             => ['label'=>'Belum Bayar',          'icon'=>'ti-clock'],
                    'menunggu_konfirmasi' => ['label'=>'Perlu Konfirmasi',     'icon'=>'ti-hourglass'],
                    'lunas'               => ['label'=>'Lunas',                'icon'=>'ti-check'],
                    'ditolak'             => ['label'=>'Ditolak',              'icon'=>'ti-x'],
                    'sedang_dipinjam'     => ['label'=>'Sedang Dipinjam',      'icon'=>'ti-clock-play'],
                    'selesai'             => ['label'=>'Selesai',              'icon'=>'ti-circle-check'],
                ];
                foreach ($tabs_def as $key => $td):
                    $cnt    = $tab_counts[$key] ?? 0;
                    $active = $tab === $key ? 'active' : '';
                    $badge_class = ($key === 'menunggu_konfirmasi' && $cnt > 0) ? 'red' : '';
                ?>
                <a href="?tab=<?= $key ?><?= $search ? '&q='.urlencode($search) : '' ?>"
                   class="tab-pill <?= $active ?>">
                    <i class="ti <?= $td['icon'] ?>"></i>
                    <?= $td['label'] ?>
                    <span class="tc <?= $badge_class ?>"><?= $cnt ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- List rental -->
            <?php if (empty($rentals)): ?>
                <div class="empty-state">
                    <i class="ti ti-shopping-cart-off"></i>
                    <h3>Tidak ada transaksi di kategori ini</h3>
                </div>
            <?php else: ?>
            <div class="rental-list">
                <?php foreach ($rentals as $r):
                    $sp   = $r['status_pembayaran'] ?? 'pending';
                    $spj  = $r['status_pinjam']     ?? 'belum_mulai';
                    $dur  = (int) ((strtotime($r['tanggal_selesai']) - strtotime($r['tanggal_mulai'])) / 86400);
                    $tot  = $r['total_harga'] ?: ($dur * $r['harga']);
                    $g    = (!empty($r['gambar']) && file_exists("../uploads/".$r['gambar']))
                            ? "../uploads/".$r['gambar'] : null;

                    $cp   = $av_colors[abs(crc32($r['penyewa']  ?? '')) % 5];
                    $co   = $av_colors[abs(crc32($r['pemilik']  ?? '')) % 5];
                    $ip   = strtoupper(substr($r['penyewa']  ?? '?', 0, 2));
                    $io   = strtoupper(substr($r['pemilik']  ?? '?', 0, 2));

                    // Progress untuk sedang dipinjam
                    $progress  = 0;
                    $sisa_hari = 0;
                    if ($spj === 'sedang_dipinjam') {
                        $now     = time();
                        $mulai   = strtotime($r['tanggal_mulai']);
                        $selesai = strtotime($r['tanggal_selesai']);
                        $progress  = $selesai > $mulai ? min(100, max(0, round(($now-$mulai)/($selesai-$mulai)*100))) : 0;
                        $sisa_hari = max(0, (int) ceil(($selesai - $now) / 86400));
                    }

                    // Badge status
                    $badge = match(true) {
                        $spj === 'selesai'             => '<span class="sbadge sb-selesai"><i class="ti ti-circle-check"></i> Selesai</span>',
                        $spj === 'sedang_dipinjam'     => '<span class="sbadge sb-pinjam"><i class="ti ti-clock-play"></i> Dipinjam</span>',
                        $sp  === 'lunas'               => '<span class="sbadge sb-lunas"><i class="ti ti-check"></i> Lunas</span>',
                        $sp  === 'menunggu_konfirmasi' => '<span class="sbadge sb-waiting"><i class="ti ti-hourglass"></i> Perlu Konfirmasi</span>',
                        $sp  === 'ditolak'             => '<span class="sbadge sb-ditolak"><i class="ti ti-x"></i> Ditolak</span>',
                        default                        => '<span class="sbadge sb-pending"><i class="ti ti-clock"></i> Belum Bayar</span>',
                    };

                    $is_urgent = $sp === 'menunggu_konfirmasi';
                ?>
                <div class="rental-card <?= $is_urgent ? 'urgent' : '' ?>">

                    <!-- Header -->
                    <div class="rc-head">
                        <div class="rc-thumb">
                            <?php if ($g): ?>
                                <img src="<?= htmlspecialchars($g) ?>" alt="">
                            <?php else: ?>
                                <i class="ti ti-box-seam"></i>
                            <?php endif; ?>
                        </div>
                        <div class="rc-info">
                            <div class="rc-name"><?= htmlspecialchars($r['nama_barang']) ?></div>
                            <div class="rc-meta">
                                <?php if (!empty($r['lokasi'])): ?>
                                <span><i class="ti ti-map-pin"></i> <?= htmlspecialchars($r['lokasi']) ?></span>
                                <?php endif; ?>
                                <span><i class="ti ti-calendar"></i> <?= $dur ?> hari</span>
                                <span><i class="ti ti-clock"></i> <?= date('d M Y H:i', strtotime($r['created_at'])) ?></span>
                                <?php if (!empty($r['metode_pembayaran'])): ?>
                                <span><i class="ti ti-credit-card"></i> <?= ucfirst($r['metode_pembayaran']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="rc-right">
                            <?= $badge ?>
                            <div class="rc-total">Rp <?= number_format($tot, 0, ',', '.') ?></div>
                            <div class="rc-id">#<?= str_pad($r['id'], 6, '0', STR_PAD_LEFT) ?></div>
                        </div>
                    </div>

                    <!-- Info baris: penyewa, pemilik, tanggal -->
                    <div class="rc-body">
                        <div class="rc-cell">
                            <div class="rc-cell-label">Penyewa</div>
                            <div class="rc-cell-val">
                                <div class="av-mini" style="background:<?= $cp[0] ?>;color:<?= $cp[1] ?>;"><?= $ip ?></div>
                                <?= htmlspecialchars($r['penyewa']) ?>
                            </div>
                        </div>
                        <div class="rc-cell">
                            <div class="rc-cell-label">Pemilik Barang</div>
                            <div class="rc-cell-val">
                                <div class="av-mini" style="background:<?= $co[0] ?>;color:<?= $co[1] ?>;"><?= $io ?></div>
                                <?= htmlspecialchars($r['pemilik']) ?>
                            </div>
                        </div>
                        <div class="rc-cell">
                            <div class="rc-cell-label">Periode Sewa</div>
                            <div class="rc-cell-val" style="font-size:12.5px;">
                                <i class="ti ti-calendar-event"></i>
                                <?= date('d M Y', strtotime($r['tanggal_mulai'])) ?>
                                &rarr;
                                <?= date('d M Y', strtotime($r['tanggal_selesai'])) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Bukti pembayaran (kalau ada) -->
                    <?php if ($sp === 'menunggu_konfirmasi' && !empty($r['bukti_pembayaran'])): ?>
                    <div class="bukti-row">
                        <i class="ti ti-photo"></i>
                        <span>Bukti pembayaran sudah diupload oleh penyewa</span>
                        <a href="../uploads/bukti/<?= htmlspecialchars($r['bukti_pembayaran']) ?>"
                           target="_blank" class="btn-bukti">
                            <i class="ti ti-eye" style="font-size:13px;"></i> Lihat Bukti
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Progress bar dipinjam -->
                    <?php if ($spj === 'sedang_dipinjam'): ?>
                    <div class="progress-row">
                        <i class="ti ti-clock-play"></i>
                        <div class="prog-bar-wrap">
                            <div class="prog-bar" style="width:<?= $progress ?>%;"></div>
                        </div>
                        <span class="prog-text"><?= $sisa_hari ?> hari tersisa</span>
                    </div>
                    <?php endif; ?>

                    <!-- Catatan admin (ditolak) -->
                    <?php if ($sp === 'ditolak' && !empty($r['catatan_admin'])): ?>
                    <div class="catatan-row">
                        <i class="ti ti-message-circle"></i>
                        Alasan: <?= htmlspecialchars($r['catatan_admin']) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Paid at -->
                    <?php if (!empty($r['paid_at'])): ?>
                    <div style="padding:8px 18px;background:#f8f9fb;border-top:1px solid #f0f1f3;font-size:12px;color:#6b7280;display:flex;align-items:center;gap:6px;">
                        <i class="ti ti-circle-check" style="color:#16a34a;font-size:14px;"></i>
                        Dikonfirmasi pada <?= date('d M Y H:i', strtotime($r['paid_at'])) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Aksi -->
                    <div class="rc-footer">
                        <?php if ($sp === 'menunggu_konfirmasi'): ?>
                            <form method="POST" style="display:contents">
                                <input type="hidden" name="rental_id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="aksi" value="konfirmasi_bayar">
                                <button type="submit" class="btn-aksi btn-konfirmasi"
                                        onclick="return confirm('Konfirmasi pembayaran ini?')">
                                    <i class="ti ti-check"></i> Konfirmasi Bayar
                                </button>
                            </form>
                            <button type="button" class="btn-aksi btn-tolak"
                                    onclick="openTolak(<?= $r['id'] ?>)">
                                <i class="ti ti-x"></i> Tolak
                            </button>

                        <?php elseif ($sp === 'lunas' && $spj === 'belum_mulai'): ?>
                            <form method="POST" style="display:contents">
                                <input type="hidden" name="rental_id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="aksi" value="mulai_pinjam">
                                <button type="submit" class="btn-aksi btn-mulai"
                                        onclick="return confirm('Tandai barang mulai dipinjam?')">
                                    <i class="ti ti-clock-play"></i> Mulai Dipinjam
                                </button>
                            </form>

                        <?php elseif ($spj === 'sedang_dipinjam'): ?>
                            <form method="POST" style="display:contents">
                                <input type="hidden" name="rental_id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="aksi" value="selesai_pinjam">
                                <button type="submit" class="btn-aksi btn-selesai"
                                        onclick="return confirm('Tandai peminjaman selesai?')">
                                    <i class="ti ti-circle-check"></i> Tandai Selesai
                                </button>
                            </form>
                        <?php endif; ?>

                        <a href="../index.php?page=detail&id=<?= $r['item_id'] ?>"
                           target="_blank" class="btn-aksi btn-view">
                            <i class="ti ti-eye"></i> Lihat Barang
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Modal Tolak -->
<div class="modal-overlay" id="modalTolak">
    <div class="modal-box">
        <div class="modal-title">Tolak Pembayaran</div>
        <div class="modal-sub">Berikan alasan penolakan agar penyewa tahu apa yang harus diperbaiki.</div>
        <form method="POST" id="formTolak">
            <input type="hidden" name="rental_id" id="tolak_rental_id">
            <input type="hidden" name="aksi" value="tolak_bayar">
            <textarea name="catatan" rows="4" placeholder="contoh: Bukti transfer tidak jelas, nominal tidak sesuai..."></textarea>
            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeTolak()">Batal</button>
                <button type="submit" class="btn-modal-ok">Tolak Pembayaran</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTolak(id) {
    document.getElementById('tolak_rental_id').value = id;
    document.getElementById('modalTolak').classList.add('show');
}
function closeTolak() {
    document.getElementById('modalTolak').classList.remove('show');
}
document.getElementById('modalTolak').addEventListener('click', function(e) {
    if (e.target === this) closeTolak();
});
</script>
</body>
</html>