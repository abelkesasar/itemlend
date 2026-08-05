<?php
// JANGAN session_start() di sini — sudah dipanggil di index.php
require 'config/db.php';

if (!isset($_SESSION['user'])) {
    echo "<script>window.location='index.php?page=login';</script>";
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { echo "ID tidak valid"; exit; }

$stmt = $conn->prepare("
    SELECT items.*, users.username, users.nomor_wa
    FROM items
    JOIN users ON items.user_id = users.id
    WHERE items.id = ?
");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) { echo "Barang tidak ditemukan"; exit; }

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
$gambar = resolveGambarItem($item['gambar']);

$harga = $item['harga'] ?? 0;
?>

<style>
    .sewa-wrap {
        max-width: 900px;
        margin: 0 auto;
        padding: 8px 0 48px;
    }

    /* ── Breadcrumb ── */
    .sewa-breadcrumb {
        display: flex; align-items: center; gap: 8px;
        font-size: 13px; color: #6b7280; margin-bottom: 24px;
    }
    .sewa-breadcrumb a { color: #6b7280; text-decoration: none; }
    .sewa-breadcrumb a:hover { color: #3d4bff; }
    .sewa-breadcrumb i { font-size: 14px; }

    /* ── Grid ── */
    .sewa-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        align-items: start;
    }

    /* ── Item Preview Card ── */
    .item-preview {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        overflow: hidden;
    }
    .item-preview-thumb {
        width: 100%; aspect-ratio: 4/3;
        background: #f0f1f5;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
    }
    .item-preview-thumb img {
        width: 100%; height: 100%; object-fit: cover;
    }
    .item-preview-thumb i { font-size: 56px; color: #d1d5db; }
    .item-preview-body { padding: 20px; }
    .item-preview-name {
        font-size: 18px; font-weight: 800; color: #1a1d2e;
        margin-bottom: 6px;
    }
    .item-preview-desc {
        font-size: 13px; color: #6b7280; line-height: 1.6;
        display: -webkit-box; -webkit-line-clamp: 3;
        -webkit-box-orient: vertical; overflow: hidden;
        margin-bottom: 14px;
    }
    .item-preview-meta {
        display: flex; flex-direction: column; gap: 8px;
    }
    .preview-meta-row {
        display: flex; align-items: center; gap: 8px; font-size: 13px;
    }
    .preview-meta-row i { font-size: 16px; color: #9ca3af; }
    .preview-meta-row span { color: #374151; }
    .preview-meta-row strong { color: #1a1d2e; }

    .price-highlight {
        background: linear-gradient(135deg, #eef0ff, #e4e8ff);
        border: 1px solid #c7d0ff;
        border-radius: 12px;
        padding: 14px 16px;
        margin-top: 16px;
        display: flex; align-items: baseline; gap: 6px;
    }
    .price-highlight .ph-num {
        font-size: 26px; font-weight: 800; color: #3d4bff;
    }
    .price-highlight .ph-unit {
        font-size: 13px; color: #6b7280; font-weight: 500;
    }

    /* ── Form Card ── */
    .form-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 28px;
        position: sticky;
        top: 20px;
    }
    .form-card-title {
        font-size: 20px; font-weight: 800; color: #1a1d2e;
        margin-bottom: 6px;
        display: flex; align-items: center; gap: 8px;
    }
    .form-card-title i { font-size: 22px; color: #3d4bff; }
    .form-card-sub {
        font-size: 13px; color: #6b7280; margin-bottom: 24px;
    }

    /* Form inputs */
    .sewa-form-group { margin-bottom: 16px; }
    .sewa-form-group label {
        display: block; font-size: 12.5px; font-weight: 600;
        color: #374151; margin-bottom: 6px;
    }
    .sewa-form-group input[type=date] {
        width: 100%;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        padding: 11px 14px;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 14px; color: #1a1d2e;
        background: #fff; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .sewa-form-group input[type=date]:focus {
        border-color: #3d4bff;
        box-shadow: 0 0 0 3px rgba(61,75,255,0.1);
    }

    /* Summary box */
    .summary-box {
        background: #f8f9fb;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        margin: 20px 0;
        display: none;
    }
    .summary-box.show { display: block; }
    .summary-row {
        display: flex; justify-content: space-between; align-items: center;
        font-size: 13px; padding: 5px 0;
        border-bottom: 1px dashed #e5e7eb;
    }
    .summary-row:last-child { border-bottom: none; }
    .summary-row span { color: #6b7280; }
    .summary-row strong { color: #1a1d2e; font-weight: 600; }
    .summary-total {
        display: flex; justify-content: space-between; align-items: center;
        margin-top: 10px; padding-top: 10px;
        border-top: 2px solid #3d4bff;
    }
    .summary-total span { font-size: 13px; font-weight: 600; color: #1a1d2e; }
    .summary-total strong { font-size: 18px; font-weight: 800; color: #3d4bff; }

    /* Submit button */
    .btn-sewa-submit {
        width: 100%; padding: 14px;
        background: #3d4bff; color: #fff;
        border: none; border-radius: 12px;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 15px; font-weight: 700;
        cursor: pointer; display: flex; align-items: center;
        justify-content: center; gap: 8px;
        transition: background 0.15s, transform 0.1s;
    }
    .btn-sewa-submit:hover { background: #2c38d4; }
    .btn-sewa-submit:active { transform: scale(0.98); }
    .btn-sewa-submit i { font-size: 20px; }

    .safe-note {
        display: flex; align-items: center; gap: 6px; justify-content: center;
        margin-top: 12px; font-size: 12px; color: #9ca3af;
    }
    .safe-note i { font-size: 14px; color: #16a34a; }

    /* ── Responsive ── */
    @media (max-width: 720px) {
        .sewa-grid { grid-template-columns: 1fr; }
        .form-card { position: static; }
    }
    @media (max-width: 480px) {
        .sewa-wrap { padding: 0 0 40px; }
    }
</style>

<div class="sewa-wrap">

    <!-- Breadcrumb -->
    <div class="sewa-breadcrumb">
        <a href="index.php"><i class="ti ti-home"></i></a>
        <i class="ti ti-chevron-right"></i>
        <a href="index.php">Daftar Barang</a>
        <i class="ti ti-chevron-right"></i>
        <a href="index.php?page=detail&id=<?= $id ?>"><?= htmlspecialchars($item['nama_barang']) ?></a>
        <i class="ti ti-chevron-right"></i>
        <span>Sewa</span>
    </div>

    <div class="sewa-grid">

        <!-- KIRI: Preview Barang -->
        <div class="item-preview">
            <div class="item-preview-thumb">
                <?php if ($gambar): ?>
                    <img src="<?= htmlspecialchars($gambar) ?>" alt="">
                <?php else: ?>
                    <i class="ti ti-box-seam"></i>
                <?php endif; ?>
            </div>
            <div class="item-preview-body">
                <div class="item-preview-name"><?= htmlspecialchars($item['nama_barang']) ?></div>
                <div class="item-preview-desc"><?= htmlspecialchars($item['deskripsi'] ?? '') ?></div>
                <div class="item-preview-meta">
                    <?php if (!empty($item['lokasi'])): ?>
                    <div class="preview-meta-row">
                        <i class="ti ti-map-pin"></i>
                        <span><?= htmlspecialchars($item['lokasi']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="preview-meta-row">
                        <i class="ti ti-user"></i>
                        <span>Pemilik: <strong><?= htmlspecialchars($item['username']) ?></strong></span>
                    </div>
                    <?php if (!empty($item['kategori'])): ?>
                    <div class="preview-meta-row">
                        <i class="ti ti-tag"></i>
                        <span><?= htmlspecialchars($item['kategori']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="price-highlight">
                    <span class="ph-num">Rp <?= number_format($harga, 0, ',', '.') ?></span>
                    <span class="ph-unit">/ hari</span>
                </div>
            </div>
        </div>

        <!-- KANAN: Form Sewa -->
        <div class="form-card">
            <div class="form-card-title">
                <i class="ti ti-calendar-event"></i> Atur Jadwal Sewa
            </div>
            <div class="form-card-sub">Pilih tanggal mulai dan selesai sewamu</div>

            <form action="actions/sewa.php" method="POST" id="sewaForm">
                <input type="hidden" name="item_id" value="<?= $id ?>">

                <div class="sewa-form-group">
                    <label><i class="ti ti-calendar" style="font-size:13px;vertical-align:-1px;"></i> Tanggal Mulai</label>
                    <input type="date" name="start" id="startDate"
                           min="<?= date('Y-m-d') ?>" required
                           onchange="hitungHarga()">
                </div>

                <div class="sewa-form-group">
                    <label><i class="ti ti-calendar-due" style="font-size:13px;vertical-align:-1px;"></i> Tanggal Selesai</label>
                    <input type="date" name="end" id="endDate"
                           min="<?= date('Y-m-d') ?>" required
                           onchange="hitungHarga()">
                </div>

                <!-- Summary otomatis -->
                <div class="summary-box" id="summaryBox">
                    <div class="summary-row">
                        <span>Harga per hari</span>
                        <strong>Rp <?= number_format($harga, 0, ',', '.') ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Durasi</span>
                        <strong id="summaryDurasi">–</strong>
                    </div>
                    <div class="summary-total">
                        <span>Total Estimasi</span>
                        <strong id="summaryTotal">–</strong>
                    </div>
                </div>

                <button type="submit" class="btn-sewa-submit">
                    <i class="ti ti-shopping-cart"></i> Konfirmasi Sewa
                </button>
            </form>

            <div class="safe-note">
                <i class="ti ti-shield-check"></i>
                Transaksi aman & terlindungi ItemLend
            </div>
        </div>

    </div>
</div>

<script>
const hargaPerHari = <?= (int) $harga ?>;

function hitungHarga() {
    const start = document.getElementById('startDate').value;
    const end   = document.getElementById('endDate').value;

    if (!start || !end) return;

    const d1   = new Date(start);
    const d2   = new Date(end);
    const diff = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));

    if (diff <= 0) {
        document.getElementById('endDate').setCustomValidity('Tanggal selesai harus setelah tanggal mulai');
        document.getElementById('summaryBox').classList.remove('show');
        return;
    }

    document.getElementById('endDate').setCustomValidity('');

    const total = diff * hargaPerHari;
    document.getElementById('summaryDurasi').textContent = diff + ' hari';
    document.getElementById('summaryTotal').textContent  =
        'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('summaryBox').classList.add('show');

    // set min endDate = startDate + 1
    const minEnd = new Date(d1);
    minEnd.setDate(minEnd.getDate() + 1);
    document.getElementById('endDate').min =
        minEnd.toISOString().split('T')[0];
}
</script>