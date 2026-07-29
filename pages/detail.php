<?php
require 'config/db.php';

$id = $_GET['id'] ?? null;

if (!$id) { echo "ID tidak valid"; exit; }

$stmt = $conn->prepare("
    SELECT items.*, users.username, users.nomor_wa, users.foto_profil
    FROM items
    JOIN users ON items.user_id = users.id
    WHERE items.id = ?
");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) { echo "Barang tidak ditemukan"; exit; }

$user_login = $_SESSION['user'] ?? null;

// Gambar
$gambar = (!empty($item['gambar']) && file_exists("uploads/" . $item['gambar']))
    ? "uploads/" . $item['gambar'] : null;

// Avatar warna owner
$av_colors = [
    ['#eef0ff','#3d4bff'], ['#e9f9f0','#1a7a46'],
    ['#fff7e6','#cc7a00'], ['#fce7f3','#9d174d'], ['#e4f7f5','#0d7d72'],
];
$c    = $av_colors[abs(crc32($item['username'] ?? '')) % 5];
$init = strtoupper(substr($item['username'] ?? '?', 0, 2));

// Barang lain dari pemilik yang sama
$stmt2 = $conn->prepare("
    SELECT * FROM items
    WHERE user_id = ? AND id != ? AND status = 'approved'
    ORDER BY created_at DESC LIMIT 3
");
$stmt2->execute([$item['user_id'], $id]);
$other_items = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['nama_barang']) ?> – ItemLend</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root {
            --brand:      #3d4bff;
            --brand-dark: #2c38d4;
            --brand-soft: #eef0ff;
            --text:       #1a1d2e;
            --muted:      #6b7280;
            --border:     #e5e7eb;
            --bg:         #f4f5f7;
            --white:      #ffffff;
            --green:      #16a34a;
            --green-soft: #dcfce7;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg); color: var(--text);
        }
        a { text-decoration: none; color: inherit; }

        /* ── BREADCRUMB ── */
        .breadcrumb {
            max-width: 1100px; margin: 0 auto;
            padding: 20px 24px 0;
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: var(--muted);
        }
        .breadcrumb a { color: var(--muted); transition: color 0.15s; }
        .breadcrumb a:hover { color: var(--brand); }
        .breadcrumb i { font-size: 14px; }

        /* ── MAIN CONTAINER ── */
        .container {
            max-width: 1100px; margin: 0 auto;
            padding: 20px 24px 60px;
        }

        /* ── PRODUCT GRID ── */
        .product-grid {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 28px;
            align-items: start;
        }

        /* ── IMAGE PANEL ── */
        .image-panel {}
        .main-image-wrap {
            border-radius: 20px; overflow: hidden;
            background: #f0f1f5;
            aspect-ratio: 4/3;
            display: flex; align-items: center; justify-content: center;
            position: relative;
        }
        .main-image-wrap img {
            width: 100%; height: 100%; object-fit: cover;
        }
        .no-img-placeholder {
            display: flex; flex-direction: column; align-items: center; gap: 12px;
            color: #c0c4ce;
        }
        .no-img-placeholder i { font-size: 64px; }
        .no-img-placeholder span { font-size: 14px; font-weight: 500; }

        /* Status badge overlay */
        .status-overlay {
            position: absolute; top: 16px; left: 16px;
            background: var(--green); color: #fff;
            font-size: 12px; font-weight: 700;
            padding: 5px 12px; border-radius: 20px;
            display: flex; align-items: center; gap: 5px;
        }
        .status-overlay i { font-size: 13px; }

        /* ── INFO CARD ── */
        .info-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            position: sticky; top: 20px;
        }

        /* Kategori badge */
        .kategori-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--brand-soft); color: var(--brand);
            font-size: 12px; font-weight: 600;
            padding: 4px 12px; border-radius: 20px;
            margin-bottom: 14px;
        }

        .item-title {
            font-size: 26px; font-weight: 800; line-height: 1.2;
            margin-bottom: 16px; color: var(--text);
        }

        /* Price block */
        .price-block {
            background: linear-gradient(135deg, #f0f4ff, #e8eeff);
            border: 1px solid #c7d0ff;
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .price-main {
            font-size: 32px; font-weight: 800; color: var(--brand);
            line-height: 1;
        }
        .price-unit { font-size: 14px; color: var(--muted); font-weight: 500; margin-top: 4px; }

        /* Meta items */
        .meta-list {
            display: flex; flex-direction: column; gap: 10px;
            margin-bottom: 20px;
        }
        .meta-item {
            display: flex; align-items: center; gap: 10px;
            font-size: 13.5px;
        }
        .meta-icon {
            width: 32px; height: 32px; border-radius: 8px;
            background: var(--bg);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .meta-icon i { font-size: 17px; color: var(--muted); }
        .meta-label { color: var(--muted); font-size: 12px; }
        .meta-value { color: var(--text); font-weight: 600; font-size: 13.5px; }

        /* Divider */
        .divider { height: 1px; background: var(--border); margin: 20px 0; }

        /* Owner card */
        .owner-card {
            display: flex; align-items: center; gap: 12px;
            background: var(--bg); border-radius: 12px; padding: 14px;
            margin-bottom: 20px;
        }
        .owner-av {
            width: 44px; height: 44px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; font-weight: 800;
        }
        .owner-info { flex: 1; }
        .owner-name { font-size: 14px; font-weight: 700; }
        .owner-sub  { font-size: 12px; color: var(--muted); margin-top: 2px; }
        .btn-wa {
            display: inline-flex; align-items: center; gap: 6px;
            background: #22c55e; color: #fff;
            font-size: 12px; font-weight: 600;
            padding: 7px 14px; border-radius: 8px;
            transition: background 0.15s; white-space: nowrap;
        }
        .btn-wa:hover { background: #16a34a; }

        /* CTA Buttons */
        .btn-sewa {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 15px;
            background: var(--brand); color: #fff;
            border: none; border-radius: 14px;
            font-family: inherit; font-size: 15px; font-weight: 700;
            cursor: pointer; transition: background 0.15s, transform 0.1s;
        }
        .btn-sewa:hover { background: var(--brand-dark); }
        .btn-sewa:active { transform: scale(0.98); }
        .btn-sewa i { font-size: 20px; }

        .btn-login-cta {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 15px;
            background: var(--brand); color: #fff;
            border-radius: 14px;
            font-size: 15px; font-weight: 700;
            transition: background 0.15s;
        }
        .btn-login-cta:hover { background: var(--brand-dark); }

        .btn-own {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 15px;
            background: var(--bg); color: var(--muted);
            border: 1px solid var(--border); border-radius: 14px;
            font-size: 15px; font-weight: 600; cursor: default;
        }

        .btn-edit {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 11px;
            background: transparent; color: var(--brand);
            border: 1.5px solid var(--brand); border-radius: 12px;
            font-size: 13.5px; font-weight: 600; margin-top: 10px;
            cursor: pointer; transition: background 0.15s;
        }
        .btn-edit:hover { background: var(--brand-soft); }

        /* Tombol Laporkan */
        .btn-report {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 11px;
            background: transparent; color: #dc2626;
            border: 1.5px solid #fecaca; border-radius: 12px;
            font-family: inherit;
            font-size: 13.5px; font-weight: 600; margin-top: 10px;
            cursor: pointer; transition: background 0.15s;
        }
        .btn-report:hover { background: #fff5f5; }
        .btn-report i { font-size: 16px; }

        .safe-note {
            display: flex; align-items: center; gap: 8px; justify-content: center;
            margin-top: 14px; font-size: 12px; color: var(--muted);
        }
        .safe-note i { font-size: 14px; color: var(--green); }

        /* ── DESCRIPTION SECTION ── */
        .section-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            margin-top: 28px;
        }
        .section-title {
            font-size: 17px; font-weight: 700;
            margin-bottom: 16px;
            display: flex; align-items: center; gap: 8px;
        }
        .section-title i { font-size: 20px; color: var(--brand); }
        .description-text {
            font-size: 14.5px; color: #374151; line-height: 1.75;
            white-space: pre-wrap;
        }

        /* ── OTHER ITEMS ── */
        .other-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-top: 14px;
        }
        .other-card {
            border: 1px solid var(--border); border-radius: 14px;
            overflow: hidden; background: var(--white);
            transition: box-shadow 0.18s, transform 0.18s;
        }
        .other-card:hover {
            box-shadow: 0 6px 20px rgba(61,75,255,0.10);
            transform: translateY(-2px);
        }
        .other-thumb {
            aspect-ratio: 4/3; background: #f0f1f5;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }
        .other-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .other-thumb i { font-size: 32px; color: #d1d5db; }
        .other-body { padding: 10px 12px; }
        .other-name { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .other-price { font-size: 12px; color: var(--brand); font-weight: 700; margin-top: 3px; }

        /* ── REPORT MODAL ── */
        .report-modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(26,29,46,0.55);
            z-index: 200;
            align-items: center; justify-content: center;
            padding: 20px;
        }
        .report-modal-overlay.active { display: flex; }
        .report-modal {
            background: #fff; border-radius: 18px;
            width: 100%; max-width: 440px;
            padding: 24px;
            max-height: 90vh; overflow-y: auto;
        }
        .report-modal-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 18px;
        }
        .report-modal-header h3 {
            font-size: 17px; font-weight: 800; color: #dc2626;
            display: flex; align-items: center; gap: 8px;
        }
        .report-close {
            background: var(--bg); border: none; cursor: pointer;
            width: 30px; height: 30px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--muted); font-size: 15px;
        }
        .report-close:hover { background: #e5e7eb; }
        .report-field { margin-bottom: 14px; }
        .report-field label {
            display: block; font-size: 12.5px; font-weight: 700;
            color: var(--text); margin-bottom: 6px;
        }
        .report-field select, .report-field textarea {
            width: 100%; padding: 10px 12px;
            border: 1.5px solid var(--border); border-radius: 10px;
            font-family: inherit; font-size: 13.5px; color: var(--text);
            resize: vertical;
        }
        .report-field select:focus, .report-field textarea:focus {
            outline: none; border-color: #dc2626;
        }
        .btn-submit-report {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 13px;
            background: #dc2626; color: #fff;
            border: none; border-radius: 12px;
            font-family: inherit; font-size: 14px; font-weight: 700;
            cursor: pointer; transition: background 0.15s;
        }
        .btn-submit-report:hover { background: #b91c1c; }

        /* ── RESPONSIVE ── */
        @media (max-width: 860px) {
            .product-grid { grid-template-columns: 1fr; }
            .info-card { position: static; }
        }
        @media (max-width: 600px) {
            .container { padding: 16px 16px 48px; }
            .item-title { font-size: 22px; }
            .price-main { font-size: 26px; }
            .other-grid { grid-template-columns: repeat(2, 1fr); }
            .section-card, .info-card { padding: 20px; }
        }
    </style>
</head>
<body>

    <!-- BREADCRUMB -->
    <div class="breadcrumb">
        <a href="index.php"><i class="ti ti-home"></i></a>
        <i class="ti ti-chevron-right"></i>
        <a href="index.php">Daftar Barang</a>
        <i class="ti ti-chevron-right"></i>
        <span><?= htmlspecialchars($item['nama_barang']) ?></span>
    </div>

    <div class="container">

        <!-- PRODUCT GRID -->
        <div class="product-grid">

            <!-- LEFT: Gambar + Deskripsi -->
            <div>
                <div class="main-image-wrap">
                    <?php if ($gambar): ?>
                        <img src="<?= htmlspecialchars($gambar) ?>" alt="<?= htmlspecialchars($item['nama_barang']) ?>">
                    <?php else: ?>
                        <div class="no-img-placeholder">
                            <i class="ti ti-photo-off"></i>
                            <span>Belum ada foto</span>
                        </div>
                    <?php endif; ?>
                    <div class="status-overlay">
                        <i class="ti ti-circle-check"></i> Tersedia
                    </div>
                </div>

                <!-- Deskripsi -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="ti ti-file-description"></i> Deskripsi Barang
                    </div>
                    <p class="description-text"><?= htmlspecialchars($item['deskripsi'] ?? 'Tidak ada deskripsi.') ?></p>
                </div>

                <!-- Barang lain dari owner -->
                <?php if (!empty($other_items)): ?>
                <div class="section-card">
                    <div class="section-title">
                        <i class="ti ti-box-seam"></i>
                        Barang Lain dari <?= htmlspecialchars($item['username']) ?>
                    </div>
                    <div class="other-grid">
                        <?php foreach ($other_items as $o):
                            $og = (!empty($o['gambar']) && file_exists("uploads/".$o['gambar'])) ? "uploads/".$o['gambar'] : null;
                        ?>
                        <a href="?page=detail&id=<?= $o['id'] ?>" class="other-card">
                            <div class="other-thumb">
                                <?php if ($og): ?>
                                    <img src="<?= htmlspecialchars($og) ?>" alt="">
                                <?php else: ?>
                                    <i class="ti ti-box-seam"></i>
                                <?php endif; ?>
                            </div>
                            <div class="other-body">
                                <div class="other-name"><?= htmlspecialchars($o['nama_barang']) ?></div>
                                <div class="other-price">Rp <?= number_format($o['harga']) ?>/hr</div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT: Info Card -->
            <div class="info-card">

                <!-- Kategori -->
                <?php if (!empty($item['kategori'])): ?>
                <span class="kategori-badge">
                    <i class="ti ti-tag"></i>
                    <?= htmlspecialchars($item['kategori']) ?>
                </span>
                <?php endif; ?>

                <h1 class="item-title"><?= htmlspecialchars($item['nama_barang']) ?></h1>

                <!-- Harga -->
                <div class="price-block">
                    <div class="price-main">Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
                    <div class="price-unit">per hari · belum termasuk deposit</div>
                </div>

                <!-- Meta -->
                <div class="meta-list">
                    <?php if (!empty($item['lokasi'])): ?>
                    <div class="meta-item">
                        <div class="meta-icon"><i class="ti ti-map-pin"></i></div>
                        <div>
                            <div class="meta-label">Lokasi</div>
                            <div class="meta-value"><?= htmlspecialchars($item['lokasi']) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <div class="meta-icon"><i class="ti ti-calendar"></i></div>
                        <div>
                            <div class="meta-label">Didaftarkan</div>
                            <div class="meta-value"><?= date('d M Y', strtotime($item['created_at'])) ?></div>
                        </div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-icon"><i class="ti ti-shield-check"></i></div>
                        <div>
                            <div class="meta-label">Status</div>
                            <div class="meta-value" style="color:var(--green)">Terverifikasi Admin</div>
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

                <!-- Owner -->
                <div class="owner-card">
                    <div class="owner-av" style="background:<?= $c[0] ?>;color:<?= $c[1] ?>;"><?= $init ?></div>
                    <div class="owner-info">
                        <div class="owner-name"><?= htmlspecialchars($item['username']) ?></div>
                        <div class="owner-sub">Pemilik barang</div>
                    </div>
                    <?php if (!empty($item['nomor_wa'])): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $item['nomor_wa']) ?>?text=Halo+<?= urlencode($item['username']) ?>+aku+tertarik+menyewa+<?= urlencode($item['nama_barang']) ?>"
                       target="_blank" class="btn-wa">
                        <i class="ti ti-brand-whatsapp"></i> WA
                    </a>
                    <?php endif; ?>
                    <?php if ($user_login && $item['user_id'] != $user_login): ?>
                    <a href="index.php?page=chat&item_id=<?= $item['id'] ?>&seller_id=<?= $item['user_id'] ?>"
                          class="btn-wa" style="background:#3d4bff;">
                    <i class="ti ti-message-circle"></i> Chat
                    </a>
                    <?php endif; ?>
                </div>

                <!-- CTA -->
                <?php if (!$user_login): ?>
                    <a href="index.php?page=login" class="btn-login-cta">
                        <i class="ti ti-lock"></i> Login untuk Menyewa
                    </a>

                <?php elseif ($item['user_id'] == $user_login): ?>
                    <div class="btn-own">
                        <i class="ti ti-user-check"></i> Ini Barang Milikmu
                    </div>
                    <a href="index.php?page=edit_barang&id=<?= $item['id'] ?>" class="btn-edit">
                        <i class="ti ti-edit"></i> Edit Barang
                    </a>

                <?php else: ?>
                    <a href="index.php?page=sewa&id=<?= $item['id'] ?>">
                        <button class="btn-sewa">
                            <i class="ti ti-shopping-cart"></i> Sewa Sekarang
                        </button>
                    </a>
                <?php endif; ?>

                <!-- Tombol Laporkan (hanya untuk user login yang bukan pemilik) -->
                <?php if ($user_login && $item['user_id'] != $user_login): ?>
                    <button type="button" class="btn-report" onclick="openReportModal()">
                        <i class="ti ti-flag"></i> Laporkan Barang Ini
                    </button>
                <?php endif; ?>

                <div class="safe-note">
                    <i class="ti ti-shield"></i>
                    Transaksi aman & terlindungi ItemLend
                </div>

            </div>
        </div>

    </div>

    <!-- REPORT MODAL -->
    <?php if ($user_login && $item['user_id'] != $user_login): ?>
    <div id="reportModal" class="report-modal-overlay">
        <div class="report-modal">
            <div class="report-modal-header">
                <h3><i class="ti ti-flag"></i> Laporkan Barang</h3>
                <button type="button" class="report-close" onclick="closeReportModal()"><i class="ti ti-x"></i></button>
            </div>
            <form action="actions/report.php" method="POST">
                <input type="hidden" name="type" value="barang">
                <input type="hidden" name="target_id" value="<?= $item['id'] ?>">

                <div class="report-field">
                    <label>Alasan Laporan</label>
                    <select name="reason" required>
                        <option value="">-- Pilih alasan --</option>
                        <option value="Barang tidak sesuai deskripsi">Barang tidak sesuai deskripsi</option>
                        <option value="Barang rusak/cacat">Barang rusak/cacat</option>
                        <option value="Pemilik tidak merespon">Pemilik tidak merespon</option>
                        <option value="Dugaan penipuan">Dugaan penipuan</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="report-field">
                    <label>Detail Tambahan (opsional)</label>
                    <textarea name="detail" rows="4" placeholder="Jelaskan lebih lanjut masalah yang kamu alami..."></textarea>
                </div>

                <button type="submit" class="btn-submit-report">
                    <i class="ti ti-send"></i> Kirim Laporan
                </button>
            </form>
        </div>
    </div>

    <script>
        function openReportModal() {
            document.getElementById('reportModal').classList.add('active');
        }
        function closeReportModal() {
            document.getElementById('reportModal').classList.remove('active');
        }
        document.getElementById('reportModal').addEventListener('click', function(e) {
            if (e.target === this) closeReportModal();
        });
    </script>
    <?php endif; ?>

</body>
</html>