<?php
// pages/barangsaya.php
require 'config/db.php';

if (!isset($_SESSION['user'])) {
    echo "<script>window.location='index.php?page=login';</script>";
    exit;
}

function resolveGambarItem($gambarRaw) {
    if (empty($gambarRaw)) return null;
    $list = json_decode($gambarRaw, true);
    if (is_array($list) && !empty($list[0]) && file_exists("uploads/" . $list[0])) {
        return "uploads/" . $list[0];
    }
    if (!is_array($list) && file_exists("uploads/" . $gambarRaw)) {
        return "uploads/" . $gambarRaw;
    }
    return null;
}

$owner_id = (int) $_SESSION['user'];
$tab      = $_GET['tab'] ?? 'barang';

// Barang milik owner
$stmt = $conn->prepare("SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$owner_id]);
$barang_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pesanan masuk untuk barang milik owner
$stmt2 = $conn->prepare("
    SELECT r.*,
           i.nama_barang, i.harga, i.gambar,
           u.username AS penyewa, u.nomor_wa AS wa_penyewa
    FROM rentals r
    JOIN items i ON r.item_id  = i.id
    JOIN users u ON r.user_id  = u.id
    WHERE i.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt2->execute([$owner_id]);
$pesanan_list = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_barang  = count($barang_list);
$total_pesanan = count($pesanan_list);
$lunas         = count(array_filter($pesanan_list, fn($r) => $r['status_pembayaran'] === 'lunas'));
$pending       = count(array_filter($pesanan_list, fn($r) => $r['status_pembayaran'] === 'pending'));

$av_colors = [
    ['#eef0ff','#3d4bff'], ['#e9f9f0','#1a7a46'],
    ['#fff7e6','#cc7a00'], ['#fce7f3','#9d174d'], ['#e4f7f5','#0d7d72'],
];

// Label status pinjam
function labelPinjam(string $s): array {
    return match($s) {
        'sedang_dipinjam' => ['Sedang Berjalan', 'eff6ff', '2563eb', 'bbf7d0', 'ti-clock-play'],
        'selesai'         => ['Selesai',          'f4f5f7', '6b7280', 'd1d5db', 'ti-circle-check'],
        default           => ['Belum Mulai',       'fff7e6', 'cc7a00', 'fed7aa', 'ti-clock'],
    };
}
?>

<style>
    .bsaya-wrap { max-width: 1000px; margin: 0 auto; padding: 8px 0 60px; }

    .bsaya-title { font-size: 24px; font-weight: 800; color: #1a1d2e; }
    .bsaya-sub   { font-size: 13px; color: #6b7280; margin-top: 4px; margin-bottom: 24px; }

    /* Stats */
    .stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 24px; }
    .stat-mini { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .stat-mini-icon { width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .stat-mini-icon i { font-size: 20px; }
    .stat-mini-icon.blue  { background: #eef0ff; color: #3d4bff; }
    .stat-mini-icon.green { background: #e9f9f0; color: #16a34a; }
    .stat-mini-icon.amber { background: #fff7e6; color: #cc7a00; }
    .stat-mini-icon.red   { background: #fff5f5; color: #dc2626; }
    .stat-mini-val   { font-size: 22px; font-weight: 800; color: #1a1d2e; line-height: 1; }
    .stat-mini-label { font-size: 11.5px; color: #6b7280; margin-top: 2px; }

    /* Tabs */
    .tabs { display: flex; gap: 4px; margin-bottom: 20px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 4px; width: fit-content; }
    .tab-btn { display: flex; align-items: center; gap: 7px; padding: 8px 18px; border-radius: 9px; font-size: 13.5px; font-weight: 600; cursor: pointer; color: #6b7280; border: none; background: transparent; font-family: 'Plus Jakarta Sans', sans-serif; text-decoration: none; transition: all 0.15s; }
    .tab-btn:hover { color: #1a1d2e; background: #f4f5f7; }
    .tab-btn.active { background: #3d4bff; color: #fff; }
    .tab-btn i { font-size: 16px; }
    .tab-badge { background: #ff5c5c; color: #fff; font-size: 10px; font-weight: 700; border-radius: 20px; padding: 1px 6px; }
    .tab-btn.active .tab-badge { background: rgba(255,255,255,0.3); }

    /* Section header */
    .sec-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .sec-title { font-size: 15px; font-weight: 700; color: #1a1d2e; }
    .btn-tambah { display: inline-flex; align-items: center; gap: 6px; background: #3d4bff; color: #fff; font-size: 13px; font-weight: 600; padding: 8px 16px; border-radius: 9px; text-decoration: none; transition: background 0.15s; }
    .btn-tambah:hover { background: #2c38d4; }

    /* Barang grid */
    .barang-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px,1fr)); gap: 14px; }
    .barang-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; display: flex; flex-direction: column; transition: box-shadow 0.18s, transform 0.18s; }
    .barang-card:hover { box-shadow: 0 6px 24px rgba(61,75,255,0.1); transform: translateY(-3px); }
    .barang-thumb { aspect-ratio: 4/3; background: #f0f1f5; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
    .barang-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .barang-thumb i   { font-size: 40px; color: #d1d5db; }
    .status-dot { position: absolute; top: 10px; left: 10px; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 20px; display: flex; align-items: center; gap: 4px; }
    .status-dot.approved { background: #e9f9f0; color: #16a34a; }
    .status-dot.pending  { background: #fff7e6; color: #cc7a00; }
    .status-dot.rejected { background: #fff5f5; color: #dc2626; }
    .stok-badge { position: absolute; top: 10px; right: 10px; background: #1a1d2e; color: #fff; font-size: 10.5px; font-weight: 700; padding: 3px 8px; border-radius: 20px; }
    .barang-body { padding: 12px 14px; flex: 1; display: flex; flex-direction: column; }
    .barang-name { font-size: 13.5px; font-weight: 700; color: #1a1d2e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px; }
    .barang-price { font-size: 13px; font-weight: 700; color: #3d4bff; margin-bottom: 12px; }
    .barang-actions { display: flex; gap: 6px; margin-top: auto; }
    .btn-edit-sm { flex: 1; display: flex; align-items: center; justify-content: center; gap: 5px; padding: 7px; border-radius: 8px; font-size: 12px; font-weight: 600; background: #eef0ff; color: #3d4bff; text-decoration: none; transition: background 0.15s; }
    .btn-edit-sm:hover { background: #dde0ff; }
    .btn-del-sm { display: flex; align-items: center; justify-content: center; padding: 7px 10px; border-radius: 8px; font-size: 13px; background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; transition: background 0.15s; cursor: pointer; font-family: inherit; }
    .btn-del-sm:hover { background: #fee2e2; }

    /* Edit form */
    .edit-form-wrap { background: #fff; border: 1.5px solid #3d4bff; border-radius: 16px; padding: 20px; margin-bottom: 14px; display: none; }
    .edit-form-wrap.show { display: block; }
    .edit-form-title { font-size: 14px; font-weight: 700; color: #1a1d2e; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .edit-form-title i { color: #3d4bff; }
    .edit-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .edit-group { display: flex; flex-direction: column; gap: 5px; }
    .edit-group.full { grid-column: 1/-1; }
    .edit-group label { font-size: 12px; font-weight: 600; color: #374151; }
    .edit-group input, .edit-group textarea, .edit-group select { border: 1.5px solid #e5e7eb; border-radius: 9px; padding: 9px 12px; font-family: inherit; font-size: 13px; color: #1a1d2e; background: #fff; outline: none; transition: border-color 0.15s; }
    .edit-group input:focus, .edit-group textarea:focus, .edit-group select:focus { border-color: #3d4bff; }
    .edit-group textarea { resize: none; }
    .edit-actions { display: flex; gap: 8px; margin-top: 14px; }
    .btn-save { flex: 1; padding: 10px; background: #3d4bff; color: #fff; border: none; border-radius: 9px; font-family: inherit; font-size: 13.5px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: background 0.15s; }
    .btn-save:hover { background: #2c38d4; }
    .btn-cancel { padding: 10px 20px; background: #f4f5f7; color: #6b7280; border: none; border-radius: 9px; font-family: inherit; font-size: 13.5px; font-weight: 600; cursor: pointer; }

    /* ═══ PESANAN TABLE ═══ */
    .pesanan-list { display: flex; flex-direction: column; gap: 14px; }

    .pesanan-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden;
    }
    .pesanan-card.lunas-card { border-top: 3px solid #86efac; }
    .pesanan-card.pending-card { border-top: 3px solid #e5e7eb; }
    .pesanan-card.waiting-card { border-top: 3px solid #fde68a; }

    .pc-head { display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-bottom: 1px solid #f0f1f3; }
    .pc-thumb { width: 50px; height: 50px; border-radius: 10px; flex-shrink: 0; background: #f0f1f5; overflow: hidden; display: flex; align-items: center; justify-content: center; }
    .pc-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .pc-thumb i   { font-size: 22px; color: #d1d5db; }
    .pc-info { flex: 1; min-width: 0; }
    .pc-name { font-size: 14px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pc-sub  { font-size: 12px; color: #6b7280; margin-top: 3px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .pc-sub i { font-size: 13px; }
    .pc-right { display: flex; flex-direction: column; align-items: flex-end; gap: 5px; flex-shrink: 0; }
    .pc-total { font-size: 16px; font-weight: 800; color: #3d4bff; }
    .pc-id    { font-size: 11px; color: #9ca3af; }

    /* Badges pembayaran */
    .pay-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 20px; }
    .pay-badge i { font-size: 12px; }
    .pb-lunas    { background: #e9f9f0; color: #16a34a; border: 1px solid #bbf7d0; }
    .pb-waiting  { background: #fefce8; color: #a16207; border: 1px solid #fde68a; }
    .pb-pending  { background: #f4f5f7; color: #6b7280; }
    .pb-ditolak  { background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; }

    /* Status pinjam area */
    .pc-pinjam {
        padding: 14px 18px; border-top: 1px solid #f0f1f3;
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        flex-wrap: wrap;
    }
    .pinjam-label { font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 5px; }
    .pinjam-steps { display: flex; align-items: center; gap: 0; }
    .pstep {
        display: flex; align-items: center; gap: 6px;
        font-size: 12px; font-weight: 600; color: #9ca3af; padding: 5px 12px;
        border-radius: 20px; border: 1.5px solid #e5e7eb; background: #fff;
    }
    .pstep i { font-size: 14px; }
    .pstep.done    { color: #16a34a; border-color: #bbf7d0; background: #e9f9f0; }
    .pstep.active  { color: #2563eb; border-color: #93c5fd; background: #eff6ff; }
    .pstep-arrow   { font-size: 14px; color: #d1d5db; margin: 0 4px; }

    /* Tombol ubah status */
    .pinjam-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .btn-status {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 16px; border-radius: 9px; font-size: 13px; font-weight: 600;
        cursor: pointer; border: none; font-family: inherit; transition: all 0.15s;
    }
    .btn-status i { font-size: 16px; }
    .btn-mulai   { background: #eff6ff; color: #2563eb; border: 1px solid #93c5fd; }
    .btn-mulai:hover { background: #dbeafe; }
    .btn-selesai { background: #e9f9f0; color: #16a34a; border: 1px solid #bbf7d0; }
    .btn-selesai:hover { background: #dcfce7; }
    .btn-wa-owner {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 14px; border-radius: 9px; font-size: 13px; font-weight: 600;
        background: #e9f9f0; color: #16a34a; border: 1px solid #bbf7d0;
        text-decoration: none; transition: background 0.15s;
    }
    .btn-wa-owner:hover { background: #dcfce7; }

    /* Info menunggu konfirmasi */
    .waiting-note {
        display: flex; align-items: center; gap: 8px;
        background: #fefce8; border: 1px solid #fde68a; border-radius: 10px;
        padding: 10px 14px; font-size: 12.5px; color: #a16207;
        margin: 0 18px 14px;
    }
    .waiting-note i { font-size: 16px; flex-shrink: 0; }

    /* Empty */
    .empty-state { text-align: center; padding: 48px 20px; color: #9ca3af; }
    .empty-state i { font-size: 44px; display: block; margin-bottom: 10px; color: #e5e7eb; }
    .empty-state h3 { font-size: 14px; font-weight: 700; color: #6b7280; margin-bottom: 4px; }

    @media (max-width: 720px) {
        .stats-row { grid-template-columns: repeat(2,1fr); }
        .edit-grid { grid-template-columns: 1fr; }
        .barang-grid { grid-template-columns: 1fr 1fr; }
    }
</style>

<div class="bsaya-wrap">
    <div class="bsaya-title">Barang Saya</div>
    <div class="bsaya-sub">Kelola barang dan pantau pesanan yang masuk</div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-mini">
            <div class="stat-mini-icon blue"><i class="ti ti-box-seam"></i></div>
            <div><div class="stat-mini-val"><?= $total_barang ?></div><div class="stat-mini-label">Total Barang</div></div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-icon green"><i class="ti ti-shopping-cart-check"></i></div>
            <div><div class="stat-mini-val"><?= $total_pesanan ?></div><div class="stat-mini-label">Total Pesanan</div></div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-icon green"><i class="ti ti-circle-check"></i></div>
            <div><div class="stat-mini-val"><?= $lunas ?></div><div class="stat-mini-label">Pembayaran Lunas</div></div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-icon amber"><i class="ti ti-clock"></i></div>
            <div><div class="stat-mini-val"><?= $pending ?></div><div class="stat-mini-label">Menunggu Bayar</div></div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <a href="?page=barangsaya&tab=barang" class="tab-btn <?= $tab==='barang'?'active':'' ?>">
            <i class="ti ti-box-seam"></i> Barang Saya
        </a>
        <a href="?page=barangsaya&tab=pesanan" class="tab-btn <?= $tab==='pesanan'?'active':'' ?>">
            <i class="ti ti-shopping-cart"></i> Pesanan Masuk
            <?php if ($pending > 0): ?><span class="tab-badge"><?= $pending ?></span><?php endif; ?>
        </a>
    </div>

    <?php if ($tab === 'barang'): ?>
    <!-- ═══════ TAB BARANG ═══════ -->

        <!-- Edit form inline -->
        <div class="edit-form-wrap" id="editFormWrap">
            <div class="edit-form-title"><i class="ti ti-edit"></i> Edit Barang</div>
            <form action="actions/update_barang.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <div class="edit-grid">
                    <div class="edit-group"><label>Nama Barang</label><input type="text" name="nama_barang" id="edit_nama" required></div>
                    <div class="edit-group"><label>Kategori</label><input type="text" name="kategori" id="edit_kategori"></div>
                    <div class="edit-group"><label>Harga / Hari (Rp)</label><input type="number" name="harga" id="edit_harga" min="0" required></div>
                    <div class="edit-group"><label>Stok</label><input type="number" name="stok" id="edit_stok" min="0" required></div>
                    <div class="edit-group"><label>Lokasi</label><input type="text" name="lokasi" id="edit_lokasi"></div>
                    <div class="edit-group"><label>Foto Baru (opsional)</label><input type="file" name="gambar" accept="image/*"></div>
                    <div class="edit-group full"><label>Deskripsi</label><textarea name="deskripsi" id="edit_deskripsi" rows="3"></textarea></div>
                </div>
                <div class="edit-actions">
                    <button type="submit" class="btn-save"><i class="ti ti-device-floppy"></i> Simpan</button>
                    <button type="button" class="btn-cancel" onclick="closeEdit()">Batal</button>
                </div>
            </form>
        </div>

        <div class="sec-header">
            <span class="sec-title"><?= $total_barang ?> barang terdaftar</span>
            <a href="index.php?page=tambah_barang" class="btn-tambah"><i class="ti ti-plus"></i> Tambah Barang</a>
        </div>

        <?php if (empty($barang_list)): ?>
            <div class="empty-state"><i class="ti ti-box-off"></i><h3>Belum ada barang</h3></div>
        <?php else: ?>
        <div class="barang-grid">
            <?php foreach ($barang_list as $b):
                $g  = resolveGambarItem($b['gambar']);
                $st = $b['status'] ?? 'pending';
            ?>
            <div class="barang-card">
                <div class="barang-thumb">
                    <?php if ($g): ?><img src="<?= htmlspecialchars($g) ?>" alt=""><?php else: ?><i class="ti ti-box-seam"></i><?php endif; ?>
                    <span class="status-dot <?= $st ?>">
                        <?php if ($st==='approved'): ?><i class="ti ti-circle-check" style="font-size:11px;"></i> Aktif
                        <?php elseif($st==='rejected'): ?><i class="ti ti-circle-x" style="font-size:11px;"></i> Ditolak
                        <?php else: ?><i class="ti ti-clock" style="font-size:11px;"></i> Pending<?php endif; ?>
                    </span>
                    <span class="stok-badge">Stok: <?= $b['stok'] ?? 0 ?></span>
                </div>
                <div class="barang-body">
                    <div class="barang-name"><?= htmlspecialchars($b['nama_barang']) ?></div>
                    <div class="barang-price">Rp <?= number_format($b['harga'],0,',','.') ?>/hr</div>
                    <div class="barang-actions">
                        <a href="#" class="btn-edit-sm"
                           onclick="openEdit(<?= htmlspecialchars(json_encode($b)) ?>); return false;">
                            <i class="ti ti-edit"></i> Edit
                        </a>
                        <form action="actions/delete_item.php" method="POST" style="display:contents"
                              onsubmit="return confirm('Hapus barang ini?')">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button type="submit" class="btn-del-sm"><i class="ti ti-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    <?php else: ?>
    <!-- ═══════ TAB PESANAN ═══════ -->

        <div class="sec-header">
            <span class="sec-title"><?= $total_pesanan ?> pesanan masuk</span>
        </div>

        <?php if (empty($pesanan_list)): ?>
            <div class="empty-state"><i class="ti ti-shopping-cart-off"></i><h3>Belum ada pesanan</h3></div>
        <?php else: ?>
        <div class="pesanan-list">
            <?php foreach ($pesanan_list as $p):
                $sp   = $p['status_pembayaran'] ?? 'pending';
                $spj  = $p['status_pinjam']     ?? 'belum_mulai';
                $dur  = (int) ((strtotime($p['tanggal_selesai']) - strtotime($p['tanggal_mulai'])) / 86400);
                $tot  = $p['total_harga'] ?: ($dur * $p['harga']);
                $g    = resolveGambarItem($p['gambar']);
                $c    = $av_colors[abs(crc32($p['penyewa'])) % 5];
                $init = strtoupper(substr($p['penyewa'], 0, 2));
                $tgl1 = date('d M Y', strtotime($p['tanggal_mulai']));
                $tgl2 = date('d M Y', strtotime($p['tanggal_selesai']));

                // Card class
                $cardClass = match($sp) {
                    'lunas'               => 'lunas-card',
                    'menunggu_konfirmasi' => 'waiting-card',
                    default               => 'pending-card',
                };

                // Badge pembayaran
                $payBadge = match($sp) {
                    'lunas'               => '<span class="pay-badge pb-lunas"><i class="ti ti-check"></i> Lunas</span>',
                    'menunggu_konfirmasi' => '<span class="pay-badge pb-waiting"><i class="ti ti-hourglass"></i> Menunggu Konfirmasi Admin</span>',
                    'ditolak'             => '<span class="pay-badge pb-ditolak"><i class="ti ti-x"></i> Ditolak</span>',
                    default               => '<span class="pay-badge pb-pending"><i class="ti ti-clock"></i> Belum Bayar</span>',
                };

                // Step status pinjam
                $steps = [
                    'belum_mulai'     => ['Belum Mulai',    'ti-clock',        false, false],
                    'sedang_dipinjam' => ['Sedang Berjalan','ti-clock-play',   false, false],
                    'selesai'         => ['Selesai',         'ti-circle-check', false, false],
                ];
                $order_pinjam = ['belum_mulai', 'sedang_dipinjam', 'selesai'];
                $current_idx  = array_search($spj, $order_pinjam);
            ?>
            <div class="pesanan-card <?= $cardClass ?>">

                <!-- Head -->
                <div class="pc-head">
                    <div class="pc-thumb">
                        <?php if ($g): ?><img src="<?= htmlspecialchars($g) ?>" alt=""><?php else: ?><i class="ti ti-box-seam"></i><?php endif; ?>
                    </div>
                    <div class="pc-info">
                        <div class="pc-name"><?= htmlspecialchars($p['nama_barang']) ?></div>
                        <div class="pc-sub">
                            <span style="display:flex;align-items:center;gap:5px;">
                                <span style="width:22px;height:22px;border-radius:50%;background:<?= $c[0] ?>;color:<?= $c[1] ?>;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:800;"><?= $init ?></span>
                                <?= htmlspecialchars($p['penyewa']) ?>
                            </span>
                            <span><i class="ti ti-calendar"></i> <?= $tgl1 ?> → <?= $tgl2 ?></span>
                            <span><i class="ti ti-clock"></i> <?= $dur ?> hari</span>
                        </div>
                    </div>
                    <div class="pc-right">
                        <?= $payBadge ?>
                        <div class="pc-total">Rp <?= number_format($tot,0,',','.') ?></div>
                        <div class="pc-id">#<?= str_pad($p['id'],6,'0',STR_PAD_LEFT) ?></div>
                    </div>
                </div>

                <!-- Menunggu konfirmasi admin -->
                <?php if ($sp === 'menunggu_konfirmasi'): ?>
                <div class="waiting-note">
                    <i class="ti ti-info-circle"></i>
                    Penyewa sudah upload bukti bayar. Menunggu konfirmasi dari admin.
                </div>
                <?php endif; ?>

                <!-- Status pinjam (hanya kalau lunas) -->
                <?php if ($sp === 'lunas'): ?>
                <div class="pc-pinjam">
                    <div>
                        <div class="pinjam-label">Status Peminjaman</div>
                        <div class="pinjam-steps">
                            <?php foreach ($order_pinjam as $idx => $step_key):
                                $step_labels = ['Belum Mulai','Sedang Berjalan','Selesai'];
                                $step_icons  = ['ti-clock','ti-clock-play','ti-circle-check'];
                                $stepClass   = '';
                                if ($idx < $current_idx)       $stepClass = 'done';
                                elseif ($idx === $current_idx) $stepClass = 'active';
                            ?>
                                <?php if ($idx > 0): ?><span class="pstep-arrow">›</span><?php endif; ?>
                                <span class="pstep <?= $stepClass ?>">
                                    <i class="ti <?= $step_icons[$idx] ?>"></i>
                                    <?= $step_labels[$idx] ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="pinjam-actions">
                        <!-- Tombol hubungi penyewa -->
                        <?php if (!empty($p['wa_penyewa'])): ?>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/','', $p['wa_penyewa']) ?>?text=Halo+<?= urlencode($p['penyewa']) ?>+pesananmu+sudah+dikonfirmasi"
                           target="_blank" class="btn-wa-owner">
                            <i class="ti ti-brand-whatsapp"></i> WA Penyewa
                        </a>
                        <?php endif; ?>

                        <!-- Ubah status -->
                        <?php if ($spj === 'belum_mulai'): ?>
                            <form method="POST" action="actions/update_status_pinjam.php" style="display:contents">
                                <input type="hidden" name="rental_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="status_pinjam" value="sedang_dipinjam">
                                <button type="submit" class="btn-status btn-mulai"
                                        onclick="return confirm('Tandai barang mulai dipinjam?')">
                                    <i class="ti ti-clock-play"></i> Mulai Dipinjam
                                </button>
                            </form>

                        <?php elseif ($spj === 'sedang_dipinjam'): ?>
                            <form method="POST" action="actions/update_status_pinjam.php" style="display:contents">
                                <input type="hidden" name="rental_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="status_pinjam" value="selesai">
                                <button type="submit" class="btn-status btn-selesai"
                                        onclick="return confirm('Tandai peminjaman selesai?')">
                                    <i class="ti ti-circle-check"></i> Tandai Selesai
                                </button>
                            </form>

                        <?php else: ?>
                            <span style="font-size:13px;color:#16a34a;font-weight:600;display:flex;align-items:center;gap:5px;">
                                <i class="ti ti-circle-check"></i> Peminjaman Selesai
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<script>
function openEdit(b) {
    document.getElementById('edit_id').value        = b.id;
    document.getElementById('edit_nama').value      = b.nama_barang || '';
    document.getElementById('edit_kategori').value  = b.kategori    || '';
    document.getElementById('edit_harga').value     = b.harga       || '';
    document.getElementById('edit_stok').value      = b.stok        || 0;
    document.getElementById('edit_lokasi').value    = b.lokasi      || '';
    document.getElementById('edit_deskripsi').value = b.deskripsi   || '';
    const wrap = document.getElementById('editFormWrap');
    wrap.classList.add('show');
    wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
function closeEdit() {
    document.getElementById('editFormWrap').classList.remove('show');
}
</script>