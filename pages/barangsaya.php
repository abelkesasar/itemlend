<?php
// pages/barangsaya.php
// session sudah aktif dari index.php

require 'config/db.php';

if (!isset($_SESSION['user'])) {
    echo "<script>window.location='index.php?page=login';</script>";
    exit;
}

$owner_id = (int) $_SESSION['user'];

// ── Ambil semua barang milik owner
$stmt = $conn->prepare("
    SELECT * FROM items
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$owner_id]);
$barang_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Ambil semua pesanan masuk untuk barang milik owner
$stmt2 = $conn->prepare("
    SELECT r.*,
           i.nama_barang, i.harga, i.gambar,
           u.username AS penyewa
    FROM rentals r
    JOIN items   i ON r.item_id  = i.id
    JOIN users   u ON r.user_id  = u.id
    WHERE i.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt2->execute([$owner_id]);
$pesanan_list = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// ── Stats ringkas
$total_barang  = count($barang_list);
$total_pesanan = count($pesanan_list);
$lunas         = count(array_filter($pesanan_list, fn($r) => $r['status_pembayaran'] === 'lunas'));
$pending       = count(array_filter($pesanan_list, fn($r) => $r['status_pembayaran'] === 'pending'));

// ── Avatar colors
$av_colors = [
    ['#eef0ff','#3d4bff'], ['#e9f9f0','#1a7a46'],
    ['#fff7e6','#cc7a00'], ['#fce7f3','#9d174d'], ['#e4f7f5','#0d7d72'],
];

// ── Active tab
$tab = $_GET['tab'] ?? 'barang';
?>

<style>
    .bsaya-wrap { max-width: 1000px; margin: 0 auto; padding: 8px 0 60px; }

    /* ── Page header ── */
    .bsaya-header { margin-bottom: 24px; }
    .bsaya-title  { font-size: 24px; font-weight: 800; color: #1a1d2e; }
    .bsaya-sub    { font-size: 13px; color: #6b7280; margin-top: 4px; }

    /* ── Stats row ── */
    .stats-row {
        display: grid; grid-template-columns: repeat(4, 1fr);
        gap: 12px; margin-bottom: 24px;
    }
    .stat-mini {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: 16px; display: flex; align-items: center; gap: 12px;
    }
    .stat-mini-icon {
        width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
    }
    .stat-mini-icon i { font-size: 20px; }
    .stat-mini-icon.blue  { background: #eef0ff; color: #3d4bff; }
    .stat-mini-icon.green { background: #e9f9f0; color: #16a34a; }
    .stat-mini-icon.amber { background: #fff7e6; color: #cc7a00; }
    .stat-mini-icon.red   { background: #fff5f5; color: #dc2626; }
    .stat-mini-val   { font-size: 22px; font-weight: 800; color: #1a1d2e; line-height: 1; }
    .stat-mini-label { font-size: 11.5px; color: #6b7280; margin-top: 2px; }

    /* ── Tabs ── */
    .tabs {
        display: flex; gap: 4px; margin-bottom: 20px;
        background: #fff; border: 1px solid #e5e7eb;
        border-radius: 12px; padding: 4px;
        width: fit-content;
    }
    .tab-btn {
        display: flex; align-items: center; gap: 7px;
        padding: 8px 18px; border-radius: 9px;
        font-size: 13.5px; font-weight: 600; cursor: pointer;
        color: #6b7280; border: none; background: transparent;
        font-family: 'Plus Jakarta Sans', sans-serif;
        text-decoration: none; transition: all 0.15s;
    }
    .tab-btn:hover { color: #1a1d2e; background: #f4f5f7; }
    .tab-btn.active { background: #3d4bff; color: #fff; }
    .tab-btn i { font-size: 16px; }
    .tab-badge {
        background: #ff5c5c; color: #fff;
        font-size: 10px; font-weight: 700;
        border-radius: 20px; padding: 1px 6px; line-height: 1.4;
    }
    .tab-btn.active .tab-badge { background: rgba(255,255,255,0.3); }

    /* ── Section header ── */
    .sec-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 16px;
    }
    .sec-title { font-size: 15px; font-weight: 700; color: #1a1d2e; }
    .btn-tambah {
        display: inline-flex; align-items: center; gap: 6px;
        background: #3d4bff; color: #fff;
        font-size: 13px; font-weight: 600;
        padding: 8px 16px; border-radius: 9px;
        text-decoration: none; transition: background 0.15s;
    }
    .btn-tambah:hover { background: #2c38d4; }

    /* ── Barang grid ── */
    .barang-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 14px;
    }
    .barang-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        overflow: hidden; display: flex; flex-direction: column;
        transition: box-shadow 0.18s, transform 0.18s;
    }
    .barang-card:hover {
        box-shadow: 0 6px 24px rgba(61,75,255,0.1);
        transform: translateY(-3px);
    }
    .barang-thumb {
        aspect-ratio: 4/3; background: #f0f1f5;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden; position: relative;
    }
    .barang-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .barang-thumb i   { font-size: 40px; color: #d1d5db; }
    .status-dot {
        position: absolute; top: 10px; left: 10px;
        font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 20px;
        display: flex; align-items: center; gap: 4px;
    }
    .status-dot.approved { background: #e9f9f0; color: #16a34a; }
    .status-dot.pending  { background: #fff7e6; color: #cc7a00; }
    .status-dot.rejected { background: #fff5f5; color: #dc2626; }
    .stok-badge {
        position: absolute; top: 10px; right: 10px;
        background: #1a1d2e; color: #fff;
        font-size: 10.5px; font-weight: 700;
        padding: 3px 8px; border-radius: 20px;
    }
    .barang-body { padding: 12px 14px; flex: 1; display: flex; flex-direction: column; }
    .barang-name {
        font-size: 13.5px; font-weight: 700; color: #1a1d2e;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px;
    }
    .barang-price { font-size: 13px; font-weight: 700; color: #3d4bff; margin-bottom: 12px; }
    .barang-actions { display: flex; gap: 6px; margin-top: auto; }
    .btn-edit-sm {
        flex: 1; display: flex; align-items: center; justify-content: center; gap: 5px;
        padding: 7px; border-radius: 8px; font-size: 12px; font-weight: 600;
        background: #eef0ff; color: #3d4bff; text-decoration: none;
        transition: background 0.15s;
    }
    .btn-edit-sm:hover { background: #dde0ff; }
    .btn-del-sm {
        display: flex; align-items: center; justify-content: center;
        padding: 7px 10px; border-radius: 8px; font-size: 13px;
        background: #fff5f5; color: #dc2626; text-decoration: none;
        border: 1px solid #fecaca; transition: background 0.15s; cursor: pointer;
        border: none;
    }
    .btn-del-sm:hover { background: #fee2e2; }

    /* ── Edit inline form ── */
    .edit-form-wrap {
        background: #fff; border: 1.5px solid #3d4bff;
        border-radius: 16px; padding: 20px; margin-bottom: 14px;
        display: none;
    }
    .edit-form-wrap.show { display: block; }
    .edit-form-title {
        font-size: 14px; font-weight: 700; color: #1a1d2e;
        margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
    }
    .edit-form-title i { color: #3d4bff; }
    .edit-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .edit-group { display: flex; flex-direction: column; gap: 5px; }
    .edit-group.full { grid-column: 1/-1; }
    .edit-group label { font-size: 12px; font-weight: 600; color: #374151; }
    .edit-group input,
    .edit-group textarea,
    .edit-group select {
        border: 1.5px solid #e5e7eb; border-radius: 9px;
        padding: 9px 12px; font-family: inherit; font-size: 13px;
        color: #1a1d2e; background: #fff; outline: none;
        transition: border-color 0.15s;
    }
    .edit-group input:focus,
    .edit-group textarea:focus,
    .edit-group select:focus { border-color: #3d4bff; }
    .edit-group textarea { resize: none; }
    .edit-actions { display: flex; gap: 8px; margin-top: 14px; }
    .btn-save {
        flex: 1; padding: 10px; background: #3d4bff; color: #fff;
        border: none; border-radius: 9px; font-family: inherit;
        font-size: 13.5px; font-weight: 700; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 6px;
        transition: background 0.15s;
    }
    .btn-save:hover { background: #2c38d4; }
    .btn-cancel {
        padding: 10px 20px; background: #f4f5f7; color: #6b7280;
        border: none; border-radius: 9px; font-family: inherit;
        font-size: 13.5px; font-weight: 600; cursor: pointer;
        transition: background 0.15s;
    }
    .btn-cancel:hover { background: #e5e7eb; }

    /* ── Pesanan table ── */
    .pesanan-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden;
    }
    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #f8f9fb; border-bottom: 1px solid #e5e7eb; }
    thead th {
        padding: 12px 16px; font-size: 11px; font-weight: 700;
        color: #6b7280; text-align: left; text-transform: uppercase; letter-spacing: 0.04em;
        white-space: nowrap;
    }
    tbody tr { border-bottom: 1px solid #f0f1f3; transition: background 0.12s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: #fafbff; }
    tbody td { padding: 13px 16px; font-size: 13px; vertical-align: middle; }

    .penyewa-cell { display: flex; align-items: center; gap: 8px; }
    .penyewa-av {
        width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 10px; font-weight: 700;
    }
    .penyewa-name { font-weight: 600; color: #1a1d2e; }

    .barang-cell { display: flex; align-items: center; gap: 8px; }
    .barang-mini-thumb {
        width: 34px; height: 34px; border-radius: 7px; flex-shrink: 0;
        background: #f0f1f5; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
    }
    .barang-mini-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .barang-mini-thumb i { font-size: 16px; color: #d1d5db; }
    .barang-mini-name { font-weight: 600; font-size: 12.5px; color: #1a1d2e;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px; }

    .badge-status {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px;
        white-space: nowrap;
    }
    .badge-lunas   { background: #e9f9f0; color: #16a34a; border: 1px solid #bbf7d0; }
    .badge-pending { background: #fff7e6; color: #cc7a00; border: 1px solid #fed7aa; }
    .badge-gagal   { background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; }

    .total-cell { font-weight: 700; color: #3d4bff; font-size: 13px; }
    .date-cell  { font-size: 12px; color: #6b7280; white-space: nowrap; }

    /* ── Empty state ── */
    .empty-state {
        text-align: center; padding: 56px 20px; color: #9ca3af;
    }
    .empty-state i    { font-size: 48px; display: block; margin-bottom: 12px; color: #e5e7eb; }
    .empty-state h3   { font-size: 14px; font-weight: 700; color: #6b7280; margin-bottom: 6px; }
    .empty-state p    { font-size: 13px; }

    /* ── Responsive ── */
    @media (max-width: 720px) {
        .stats-row { grid-template-columns: repeat(2, 1fr); }
        .hide-sm { display: none; }
        .edit-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 480px) {
        .barang-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
        .stats-row { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<div class="bsaya-wrap">

    <!-- Header -->
    <div class="bsaya-header">
        <div class="bsaya-title">Barang Saya</div>
        <div class="bsaya-sub">Kelola barang dan pantau pesanan yang masuk</div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-mini">
            <div class="stat-mini-icon blue"><i class="ti ti-box-seam"></i></div>
            <div>
                <div class="stat-mini-val"><?= $total_barang ?></div>
                <div class="stat-mini-label">Total Barang</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-icon green"><i class="ti ti-shopping-cart-check"></i></div>
            <div>
                <div class="stat-mini-val"><?= $total_pesanan ?></div>
                <div class="stat-mini-label">Total Pesanan</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-icon green"><i class="ti ti-circle-check"></i></div>
            <div>
                <div class="stat-mini-val"><?= $lunas ?></div>
                <div class="stat-mini-label">Pembayaran Lunas</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-icon amber"><i class="ti ti-clock"></i></div>
            <div>
                <div class="stat-mini-val"><?= $pending ?></div>
                <div class="stat-mini-label">Menunggu Bayar</div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <a href="?page=barangsaya&tab=barang"
           class="tab-btn <?= $tab === 'barang' ? 'active' : '' ?>">
            <i class="ti ti-box-seam"></i> Barang Saya
        </a>
        <a href="?page=barangsaya&tab=pesanan"
           class="tab-btn <?= $tab === 'pesanan' ? 'active' : '' ?>">
            <i class="ti ti-shopping-cart"></i> Pesanan Masuk
            <?php if ($pending > 0): ?>
                <span class="tab-badge"><?= $pending ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- ═══════════════ TAB: BARANG ═══════════════ -->
    <?php if ($tab === 'barang'): ?>

        <!-- Edit form (hidden, ditampilkan via JS) -->
        <div class="edit-form-wrap" id="editFormWrap">
            <div class="edit-form-title">
                <i class="ti ti-edit"></i> Edit Barang
            </div>
            <form action="actions/update_barang.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <div class="edit-grid">
                    <div class="edit-group">
                        <label>Nama Barang</label>
                        <input type="text" name="nama_barang" id="edit_nama" required>
                    </div>
                    <div class="edit-group">
                        <label>Kategori</label>
                        <input type="text" name="kategori" id="edit_kategori" placeholder="Elektronik, Kendaraan...">
                    </div>
                    <div class="edit-group">
                        <label>Harga / Hari (Rp)</label>
                        <input type="number" name="harga" id="edit_harga" min="0" required>
                    </div>
                    <div class="edit-group">
                        <label>Stok</label>
                        <input type="number" name="stok" id="edit_stok" min="0" required>
                    </div>
                    <div class="edit-group">
                        <label>Lokasi</label>
                        <input type="text" name="lokasi" id="edit_lokasi" placeholder="Kota / Kecamatan">
                    </div>
                    <div class="edit-group">
                        <label>Foto Baru (opsional)</label>
                        <input type="file" name="gambar" accept="image/*">
                    </div>
                    <div class="edit-group full">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" id="edit_deskripsi" rows="3"></textarea>
                    </div>
                </div>
                <div class="edit-actions">
                    <button type="submit" class="btn-save">
                        <i class="ti ti-device-floppy"></i> Simpan Perubahan
                    </button>
                    <button type="button" class="btn-cancel" onclick="closeEdit()">Batal</button>
                </div>
            </form>
        </div>

        <div class="sec-header">
            <span class="sec-title"><?= $total_barang ?> barang terdaftar</span>
            <a href="index.php?page=tambah_barang" class="btn-tambah">
                <i class="ti ti-plus"></i> Tambah Barang
            </a>
        </div>

        <?php if (empty($barang_list)): ?>
            <div class="empty-state">
                <i class="ti ti-box-off"></i>
                <h3>Belum ada barang</h3>
                <p>Mulai daftarkan barang pertamamu!</p>
            </div>
        <?php else: ?>
            <div class="barang-grid">
                <?php foreach ($barang_list as $b):
                    $g = null;

if (!empty($b['gambar'])) {

    $gambar_list = json_decode($b['gambar'], true);

    // Format baru (multiple image JSON)
    if (is_array($gambar_list) && !empty($gambar_list[0])) {

        $first_image = $gambar_list[0];

        if (file_exists("uploads/" . $first_image)) {
            $g = "uploads/" . $first_image;
        }

    } else {

        // Support gambar lama
        if (file_exists("uploads/" . $b['gambar'])) {
            $g = "uploads/" . $b['gambar'];
        }
    }
}
                    $st = $b['status'] ?? 'pending';
                    $stok = $b['stok'] ?? 0;
                ?>
                <div class="barang-card">
                    <div class="barang-thumb">
                        <?php if ($g): ?>
                            <img src="<?= htmlspecialchars($g) ?>" alt="">
                        <?php else: ?>
                            <i class="ti ti-box-seam"></i>
                        <?php endif; ?>
                        <span class="status-dot <?= $st ?>">
                            <?php if ($st === 'approved'): ?><i class="ti ti-circle-check" style="font-size:11px;"></i> Aktif
                            <?php elseif ($st === 'rejected'): ?><i class="ti ti-circle-x" style="font-size:11px;"></i> Ditolak
                            <?php else: ?><i class="ti ti-clock" style="font-size:11px;"></i> Pending<?php endif; ?>
                        </span>
                        <span class="stok-badge">Stok: <?= $stok ?></span>
                    </div>
                    <div class="barang-body">
                        <div class="barang-name"><?= htmlspecialchars($b['nama_barang']) ?></div>
                        <div class="barang-price">Rp <?= number_format($b['harga'], 0, ',', '.') ?>/hr</div>
                        <div class="barang-actions">
                            <a href="#" class="btn-edit-sm"
                               onclick="openEdit(<?= htmlspecialchars(json_encode($b)) ?>); return false;">
                                <i class="ti ti-edit"></i> Edit
                            </a>
                            <form action="actions/delete_item.php" method="POST" style="display:contents"
                                  onsubmit="return confirm('Hapus barang ini?')">
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <button type="submit" class="btn-del-sm">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <!-- ═══════════════ TAB: PESANAN ═══════════════ -->
    <?php else: ?>

        <div class="sec-header">
            <span class="sec-title"><?= $total_pesanan ?> pesanan masuk</span>
        </div>

        <?php if (empty($pesanan_list)): ?>
            <div class="empty-state">
                <i class="ti ti-shopping-cart-off"></i>
                <h3>Belum ada pesanan</h3>
                <p>Pesanan dari penyewa akan muncul di sini.</p>
            </div>
        <?php else: ?>
            <div class="pesanan-card">
                <table>
                    <thead>
                        <tr>
                            <th>Penyewa</th>
                            <th>Barang</th>
                            <th class="hide-sm">Tanggal Sewa</th>
                            <th class="hide-sm">Durasi</th>
                            <th>Total</th>
                            <th>Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pesanan_list as $p):
                            $c    = $av_colors[abs(crc32($p['penyewa'])) % 5];
                            $init = strtoupper(substr($p['penyewa'], 0, 2));
                            $dur  = (int) ((strtotime($p['tanggal_selesai']) - strtotime($p['tanggal_mulai'])) / 86400);
                            $tot  = $p['total_harga'] ?: ($dur * $p['harga']);
                            $tgl  = date('d M Y', strtotime($p['tanggal_mulai']));
                            $tgl2 = date('d M Y', strtotime($p['tanggal_selesai']));
                            $pg = null;

if (!empty($p['gambar'])) {

    $gambar_list = json_decode($p['gambar'], true);

    // Format baru
    if (is_array($gambar_list) && !empty($gambar_list[0])) {

        $first_image = $gambar_list[0];

        if (file_exists("uploads/" . $first_image)) {
            $pg = "uploads/" . $first_image;
        }

    } else {

        // Support format lama
        if (file_exists("uploads/" . $p['gambar'])) {
            $pg = "uploads/" . $p['gambar'];
        }
    }
}
                            $sp   = $p['status_pembayaran'] ?? 'pending';
                        ?>
                        <tr>
                            <td>
                                <div class="penyewa-cell">
                                    <div class="penyewa-av" style="background:<?= $c[0] ?>;color:<?= $c[1] ?>;"><?= $init ?></div>
                                    <span class="penyewa-name"><?= htmlspecialchars($p['penyewa']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="barang-cell">
                                    <div class="barang-mini-thumb">
                                        <?php if ($pg): ?>
                                            <img src="<?= htmlspecialchars($pg) ?>" alt="">
                                        <?php else: ?>
                                            <i class="ti ti-box-seam"></i>
                                        <?php endif; ?>
                                    </div>
                                    <span class="barang-mini-name"><?= htmlspecialchars($p['nama_barang']) ?></span>
                                </div>
                            </td>
                            <td class="hide-sm">
                                <span class="date-cell"><?= $tgl ?> –<br><?= $tgl2 ?></span>
                            </td>
                            <td class="hide-sm">
                                <span class="date-cell"><?= $dur ?> hari</span>
                            </td>
                            <td>
                                <span class="total-cell">Rp <?= number_format($tot, 0, ',', '.') ?></span>
                            </td>
                            <td>
                                <?php if ($sp === 'lunas'): ?>
                                    <span class="badge-status badge-lunas"><i class="ti ti-check" style="font-size:11px;"></i> Lunas</span>
                                <?php elseif ($sp === 'gagal'): ?>
                                    <span class="badge-status badge-gagal"><i class="ti ti-x" style="font-size:11px;"></i> Gagal</span>
                                <?php else: ?>
                                    <span class="badge-status badge-pending"><i class="ti ti-clock" style="font-size:11px;"></i> Menunggu</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<script>
function openEdit(b) {
    document.getElementById('edit_id').value        = b.id;
    document.getElementById('edit_nama').value      = b.nama_barang    || '';
    document.getElementById('edit_kategori').value  = b.kategori       || '';
    document.getElementById('edit_harga').value     = b.harga          || '';
    document.getElementById('edit_stok').value      = b.stok           || 0;
    document.getElementById('edit_lokasi').value    = b.lokasi         || '';
    document.getElementById('edit_deskripsi').value = b.deskripsi      || '';

    const wrap = document.getElementById('editFormWrap');
    wrap.classList.add('show');
    wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closeEdit() {
    document.getElementById('editFormWrap').classList.remove('show');
}
</script>
