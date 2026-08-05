<?php
// pages/pembayaran.php
// session sudah aktif dari index.php
require 'config/db.php';

if (!isset($_SESSION['user'])) {
    echo "<script>window.location='index.php?page=login';</script>";
    exit;
}

$rental_id = (int) ($_GET['rental_id'] ?? 0);
if (!$rental_id) { echo "Rental tidak valid"; exit; }

// Ambil data rental + item + pemilik
$stmt = $conn->prepare("
    SELECT r.*, i.nama_barang, i.harga, i.gambar, i.lokasi,
           u.username AS pemilik
    FROM rentals r
    JOIN items   i ON r.item_id = i.id
    JOIN users   u ON i.user_id = u.id
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->execute([$rental_id, $_SESSION['user']]);
$rental = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rental) { echo "Data tidak ditemukan"; exit; }

$durasi = (int) ((strtotime($rental['tanggal_selesai']) - strtotime($rental['tanggal_mulai'])) / 86400);
$total  = $rental['total_harga'] ?: ($durasi * $rental['harga']);
$status = $rental['status_pembayaran'] ?? 'pending';

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
$gambar = resolveGambarItem($rental['gambar']);

// ── Metode pembayaran yang tersedia
// Ganti nomor rekening / nama sesuai milik kamu
$metode_list = [
    'qris' => [
        'label' => 'QRIS',
        'icon'  => 'ti-qrcode',
        'color' => '#1a1d2e',
        'info'  => 'Scan QRIS di bawah menggunakan aplikasi apapun',
        'detail'=> null, // akan tampil gambar QR
        'qr'    => 'uploads/qris.png', // GANTI dengan path foto QRIS kamu
    ],
    'mandiri' => [
        'label' => 'Transfer Mandiri',
        'icon'  => 'ti-building-bank',
        'color' => '#003d7a',
        'info'  => 'Transfer ke rekening Mandiri berikut',
        'detail'=> 'No. Rek: <strong>1234567890</strong><br>a.n. <strong>Nama Admin</strong>',
    ],
    'bri' => [
        'label' => 'Transfer BRI',
        'icon'  => 'ti-building-bank',
        'color' => '#005baa',
        'info'  => 'Transfer ke rekening BRI berikut',
        'detail'=> 'No. Rek: <strong>0987654321</strong><br>a.n. <strong>Nama Admin</strong>',
    ],
    'bca' => [
        'label' => 'Transfer BCA',
        'icon'  => 'ti-building-bank',
        'color' => '#0066ae',
        'info'  => 'Transfer ke rekening BCA berikut',
        'detail'=> 'No. Rek: <strong>1122334455</strong><br>a.n. <strong>Nama Admin</strong>',
    ],
    'gopay' => [
        'label' => 'GoPay',
        'icon'  => 'ti-brand-google-play',
        'color' => '#00AED6',
        'info'  => 'Transfer ke GoPay berikut',
        'detail'=> 'No. HP: <strong>08123456789</strong><br>a.n. <strong>Nama Admin</strong>',
    ],
    'shopee' => [
        'label' => 'ShopeePay',
        'icon'  => 'ti-shopping-bag',
        'color' => '#EE4D2D',
        'info'  => 'Transfer ke ShopeePay berikut',
        'detail'=> 'No. HP: <strong>08123456789</strong><br>a.n. <strong>Nama Admin</strong>',
    ],
    'dana' => [
        'label' => 'DANA',
        'icon'  => 'ti-wallet',
        'color' => '#108EE9',
        'info'  => 'Transfer ke DANA berikut',
        'detail'=> 'No. HP: <strong>08123456789</strong><br>a.n. <strong>Nama Admin</strong>',
    ],
];
?>

<style>
    .pay-wrap { max-width: 780px; margin: 0 auto; padding: 8px 0 60px; }

    /* ── Steps ── */
    .steps {
        display: flex; align-items: center; justify-content: center;
        gap: 0; margin-bottom: 28px;
    }
    .step { display: flex; flex-direction: column; align-items: center; gap: 5px; flex: 1; }
    .step-circle {
        width: 34px; height: 34px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 700;
        border: 2px solid #e5e7eb; background: #fff; color: #9ca3af;
    }
    .step.done   .step-circle { background: #e9f9f0; border-color: #16a34a; color: #16a34a; }
    .step.active .step-circle { background: #3d4bff; border-color: #3d4bff; color: #fff; }
    .step-label { font-size: 11px; font-weight: 600; color: #9ca3af; white-space: nowrap; }
    .step.done   .step-label  { color: #16a34a; }
    .step.active .step-label  { color: #3d4bff; }
    .step-line { flex: 1; height: 2px; background: #e5e7eb; margin-bottom: 20px; max-width: 60px; }
    .step-line.done { background: #16a34a; }

    /* ── Layout ── */
    .pay-grid { display: grid; grid-template-columns: 1fr 300px; gap: 20px; align-items: start; }

    /* ── Order summary (kanan) ── */
    .order-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden;
        position: sticky; top: 80px;
    }
    .order-head {
        padding: 14px 18px; background: #f8f9fb;
        border-bottom: 1px solid #e5e7eb;
        font-size: 13px; font-weight: 700; color: #1a1d2e;
        display: flex; align-items: center; gap: 7px;
    }
    .order-head i { color: #3d4bff; font-size: 16px; }
    .order-item {
        padding: 14px 18px; border-bottom: 1px solid #f0f1f3;
        display: flex; gap: 12px; align-items: center;
    }
    .order-thumb {
        width: 48px; height: 48px; border-radius: 9px; flex-shrink: 0;
        background: #f0f1f5; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
    }
    .order-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .order-thumb i   { font-size: 22px; color: #d1d5db; }
    .order-name { font-size: 13px; font-weight: 700; }
    .order-sub  { font-size: 11.5px; color: #6b7280; margin-top: 2px; }
    .order-rows { padding: 12px 18px; display: flex; flex-direction: column; gap: 8px; }
    .order-row  { display: flex; justify-content: space-between; font-size: 12.5px; }
    .order-row span    { color: #6b7280; }
    .order-row strong  { color: #1a1d2e; font-weight: 600; }
    .order-total {
        display: flex; justify-content: space-between; align-items: center;
        padding: 14px 18px;
        background: linear-gradient(135deg, #eef0ff, #e4e8ff);
        border-top: 1px solid #c7d0ff;
    }
    .order-total span   { font-size: 13px; font-weight: 600; }
    .order-total strong { font-size: 20px; font-weight: 800; color: #3d4bff; }

    /* ── Kiri: form pembayaran ── */
    .pay-left {}
    .section-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 22px;
        margin-bottom: 16px;
    }
    .section-title {
        font-size: 14px; font-weight: 700; color: #1a1d2e;
        margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
    }
    .section-title i { font-size: 18px; color: #3d4bff; }

    /* Metode grid */
    .metode-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .metode-opt  { position: relative; }
    .metode-opt input[type=radio] { position: absolute; opacity: 0; width: 0; }
    .metode-label {
        display: flex; align-items: center; gap: 10px;
        border: 1.5px solid #e5e7eb; border-radius: 11px;
        padding: 11px 14px; cursor: pointer;
        transition: all 0.15s; background: #fff;
    }
    .metode-icon {
        width: 34px; height: 34px; border-radius: 8px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: #f4f5f7;
    }
    .metode-icon i { font-size: 18px; }
    .metode-name  { font-size: 13px; font-weight: 600; color: #374151; }
    .metode-opt input:checked + .metode-label {
        border-color: #3d4bff; background: #eef0ff;
    }
    .metode-opt input:checked + .metode-label .metode-name { color: #3d4bff; }
    .metode-opt input:checked + .metode-label .metode-icon { background: #dde0ff; }
    .metode-label:hover { border-color: #a5b0ff; }

    /* Instruksi pembayaran */
    .instruksi-box {
        background: #f8f9fb; border: 1px solid #e5e7eb; border-radius: 12px;
        padding: 16px 18px; margin-top: 14px; display: none;
    }
    .instruksi-box.show { display: block; }
    .instruksi-title {
        font-size: 12px; font-weight: 700; color: #6b7280;
        text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px;
    }
    .instruksi-info { font-size: 13.5px; color: #374151; margin-bottom: 10px; }
    .instruksi-detail { font-size: 14px; color: #1a1d2e; line-height: 1.7; }
    .instruksi-nominal {
        display: flex; align-items: center; justify-content: space-between;
        background: #fff; border: 1px solid #c7d0ff; border-radius: 10px;
        padding: 12px 16px; margin-top: 10px;
    }
    .instruksi-nominal span { font-size: 12px; color: #6b7280; }
    .instruksi-nominal strong { font-size: 18px; font-weight: 800; color: #3d4bff; }
    .copy-btn {
        display: flex; align-items: center; gap: 5px;
        background: #eef0ff; color: #3d4bff;
        border: none; border-radius: 7px; padding: 6px 12px;
        font-size: 12px; font-weight: 600; cursor: pointer; font-family: inherit;
        transition: background 0.15s;
    }
    .copy-btn:hover { background: #dde0ff; }
    .qr-wrap {
        text-align: center; margin-top: 10px;
    }
    .qr-wrap img {
        max-width: 180px; border-radius: 12px;
        border: 2px solid #e5e7eb;
    }
    .qr-wrap p { font-size: 12px; color: #6b7280; margin-top: 8px; }

    /* Upload bukti */
    .file-drop {
        border: 2px dashed #d1d5db; border-radius: 12px;
        padding: 24px; text-align: center; cursor: pointer;
        transition: all 0.15s; position: relative;
    }
    .file-drop:hover { border-color: #3d4bff; background: #fafbff; }
    .file-drop input[type=file] {
        position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
    }
    .file-drop i    { font-size: 32px; color: #d1d5db; display: block; margin-bottom: 8px; }
    .file-drop p    { font-size: 13px; font-weight: 600; color: #374151; }
    .file-drop span { font-size: 12px; color: #9ca3af; }
    .file-preview { display: none; margin-top: 12px; border-radius: 10px; overflow: hidden; }
    .file-preview img { width: 100%; max-height: 200px; object-fit: cover; }

    /* Submit */
    .btn-bayar {
        width: 100%; padding: 14px;
        background: #3d4bff; color: #fff;
        border: none; border-radius: 12px;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 15px; font-weight: 700; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        transition: background 0.15s, transform 0.1s; margin-top: 4px;
    }
    .btn-bayar:hover  { background: #2c38d4; }
    .btn-bayar:active { transform: scale(0.98); }
    .btn-bayar:disabled { background: #9ca3af; cursor: not-allowed; }

    /* ══════════════════════════════
       WAITING SCREEN
    ══════════════════════════════ */
    .waiting-wrap {
        max-width: 520px; margin: 0 auto;
        text-align: center; padding: 20px 0 60px;
    }
    .waiting-anim {
        width: 100px; height: 100px; border-radius: 50%;
        background: linear-gradient(135deg, #eef0ff, #dde0ff);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 24px;
        animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(61,75,255,0.2); }
        50%       { transform: scale(1.04); box-shadow: 0 0 0 14px rgba(61,75,255,0); }
    }
    .waiting-anim i { font-size: 44px; color: #3d4bff; }
    .waiting-title { font-size: 22px; font-weight: 800; color: #1a1d2e; margin-bottom: 8px; }
    .waiting-sub   { font-size: 14px; color: #6b7280; line-height: 1.65; max-width: 380px; margin: 0 auto 28px; }

    .waiting-detail {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
        padding: 20px; text-align: left; margin-bottom: 20px;
    }
    .waiting-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 9px 0; border-bottom: 1px solid #f0f1f3; font-size: 13.5px;
    }
    .waiting-row:last-child { border-bottom: none; }
    .waiting-row span    { color: #6b7280; }
    .waiting-row strong  { color: #1a1d2e; font-weight: 600; }
    .waiting-badge {
        display: inline-flex; align-items: center; gap: 5px;
        background: #fff7e6; color: #cc7a00;
        font-size: 12px; font-weight: 700;
        padding: 4px 12px; border-radius: 20px; border: 1px solid #fed7aa;
    }
    .waiting-total {
        background: linear-gradient(135deg, #eef0ff, #e4e8ff);
        border: 1px solid #c7d0ff; border-radius: 12px;
        padding: 16px; text-align: center; margin-bottom: 20px;
    }
    .waiting-total p      { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
    .waiting-total strong { font-size: 26px; font-weight: 800; color: #3d4bff; }

    .btn-home-wait {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 13px 28px; background: #3d4bff; color: #fff;
        border-radius: 12px; font-size: 14px; font-weight: 700;
        transition: background 0.15s;
    }
    .btn-home-wait:hover { background: #2c38d4; }
    .btn-cek-status {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 13px 28px; background: #fff; color: #1a1d2e;
        border: 1.5px solid #e5e7eb; border-radius: 12px;
        font-size: 14px; font-weight: 600; margin-right: 10px;
        transition: background 0.15s;
    }
    .btn-cek-status:hover { background: #f4f5f7; }
    .order-id-note { font-size: 12px; color: #9ca3af; margin-top: 14px; }
    .order-id-note strong { color: #6b7280; }

    @media (max-width: 640px) {
        .pay-grid { grid-template-columns: 1fr; }
        .order-card { position: static; }
        .metode-grid { grid-template-columns: 1fr; }
    }
</style>

<?php
// ══════════════════════════════════════════════
// WAITING SCREEN — kalau sudah upload bukti
// ══════════════════════════════════════════════
if ($status === 'menunggu_konfirmasi' || $status === 'lunas'):
?>
<div class="waiting-wrap">

    <!-- Steps -->
    <div class="steps">
        <div class="step done"><div class="step-circle"><i class="ti ti-check" style="font-size:15px;"></i></div><span class="step-label">Detail</span></div>
        <div class="step-line done"></div>
        <div class="step done"><div class="step-circle"><i class="ti ti-check" style="font-size:15px;"></i></div><span class="step-label">Jadwal</span></div>
        <div class="step-line done"></div>
        <div class="step done"><div class="step-circle"><i class="ti ti-check" style="font-size:15px;"></i></div><span class="step-label">Bayar</span></div>
        <div class="step-line <?= $status === 'lunas' ? 'done' : '' ?>"></div>
        <div class="step <?= $status === 'lunas' ? 'done' : 'active' ?>">
            <div class="step-circle">
                <?php if ($status === 'lunas'): ?>
                    <i class="ti ti-check" style="font-size:15px;"></i>
                <?php else: ?>4<?php endif; ?>
            </div>
            <span class="step-label">Konfirmasi</span>
        </div>
    </div>

    <?php if ($status === 'lunas'): ?>
        <div class="waiting-anim" style="background:linear-gradient(135deg,#e9f9f0,#bbf7d0);">
            <i class="ti ti-circle-check" style="color:#16a34a;"></i>
        </div>
        <div class="waiting-title">Pembayaran Dikonfirmasi! 🎉</div>
        <div class="waiting-sub">Pembayaranmu sudah dikonfirmasi admin. Selamat menikmati sewamu!</div>
    <?php else: ?>
        <div class="waiting-anim">
            <i class="ti ti-clock"></i>
        </div>
        <div class="waiting-title">Menunggu Konfirmasi</div>
        <div class="waiting-sub">Bukti pembayaranmu sudah kami terima. Admin akan mengkonfirmasi dalam 1×24 jam.</div>
    <?php endif; ?>

    <div class="waiting-total">
        <p>Total Pembayaran</p>
        <strong>Rp <?= number_format($total, 0, ',', '.') ?></strong>
    </div>

    <div class="waiting-detail">
        <div class="waiting-row">
            <span>Barang</span>
            <strong><?= htmlspecialchars($rental['nama_barang']) ?></strong>
        </div>
        <div class="waiting-row">
            <span>Tanggal Sewa</span>
            <strong><?= date('d M', strtotime($rental['tanggal_mulai'])) ?> – <?= date('d M Y', strtotime($rental['tanggal_selesai'])) ?></strong>
        </div>
        <div class="waiting-row">
            <span>Metode Bayar</span>
            <strong><?= htmlspecialchars($metode_list[$rental['metode_pembayaran']]['label'] ?? $rental['metode_pembayaran']) ?></strong>
        </div>
        <div class="waiting-row">
            <span>Status</span>
            <span class="waiting-badge">
                <i class="ti ti-clock" style="font-size:11px;"></i>
                <?= $status === 'lunas' ? 'Lunas' : 'Menunggu Konfirmasi' ?>
            </span>
        </div>
    </div>

    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
        <a href="index.php?page=peminjaman_saya" class="btn-cek-status">
            <i class="ti ti-list"></i> Lihat Semua Pesanan
        </a>
        <a href="index.php" class="btn-home-wait">
            <i class="ti ti-home"></i> Kembali ke Beranda
        </a>
    </div>

    <div class="order-id-note">
        ID Pesanan: <strong>#<?= str_pad($rental_id, 6, '0', STR_PAD_LEFT) ?></strong>
    </div>
</div>

<?php else:
// ══════════════════════════════════════════════
// FORM PEMBAYARAN
// ══════════════════════════════════════════════
?>

<div class="pay-wrap">

    <!-- Steps -->
    <div class="steps">
        <div class="step done"><div class="step-circle"><i class="ti ti-check" style="font-size:15px;"></i></div><span class="step-label">Detail</span></div>
        <div class="step-line done"></div>
        <div class="step done"><div class="step-circle"><i class="ti ti-check" style="font-size:15px;"></i></div><span class="step-label">Jadwal</span></div>
        <div class="step-line done"></div>
        <div class="step active"><div class="step-circle">3</div><span class="step-label">Pembayaran</span></div>
        <div class="step-line"></div>
        <div class="step"><div class="step-circle">4</div><span class="step-label">Konfirmasi</span></div>
    </div>

    <form action="actions/proses_pembayaran.php" method="POST" enctype="multipart/form-data" id="formBayar">
    <input type="hidden" name="rental_id" value="<?= $rental_id ?>">

    <div class="pay-grid">

        <!-- KIRI -->
        <div class="pay-left">

            <!-- Pilih Metode -->
            <div class="section-card">
                <div class="section-title">
                    <i class="ti ti-credit-card"></i> Pilih Metode Pembayaran
                </div>

                <div class="metode-grid">
                    <?php foreach ($metode_list as $key => $m): ?>
                    <div class="metode-opt">
                        <input type="radio" name="metode" id="m_<?= $key ?>" value="<?= $key ?>"
                               onchange="showInstruksi('<?= $key ?>')" required>
                        <label class="metode-label" for="m_<?= $key ?>">
                            <div class="metode-icon">
                                <i class="ti <?= $m['icon'] ?>" style="color:<?= $m['color'] ?>;"></i>
                            </div>
                            <span class="metode-name"><?= $m['label'] ?></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Instruksi dinamis -->
                <?php foreach ($metode_list as $key => $m): ?>
                <div class="instruksi-box" id="instruksi_<?= $key ?>">
                    <div class="instruksi-title">Instruksi Pembayaran</div>
                    <div class="instruksi-info"><?= $m['info'] ?></div>

                    <?php if ($key === 'qris'): ?>
                        <div class="qr-wrap">
                            <?php if (file_exists($m['qr'])): ?>
                                <img src="<?= $m['qr'] ?>" alt="QRIS ItemLend">
                            <?php else: ?>
                                <div style="width:180px;height:180px;background:#f0f1f5;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                    <i class="ti ti-qrcode" style="font-size:64px;color:#d1d5db;"></i>
                                </div>
                            <?php endif; ?>
                            <p>Scan menggunakan aplikasi apapun</p>
                        </div>
                    <?php else: ?>
                        <div class="instruksi-detail"><?= $m['detail'] ?></div>
                    <?php endif; ?>

                    <div class="instruksi-nominal">
                        <div>
                            <span>Nominal Transfer</span><br>
                            <strong>Rp <?= number_format($total, 0, ',', '.') ?></strong>
                        </div>
                        <button type="button" class="copy-btn"
                                onclick="copyNominal(<?= $total ?>)">
                            <i class="ti ti-copy" style="font-size:14px;"></i> Salin
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Upload Bukti -->
            <div class="section-card">
                <div class="section-title">
                    <i class="ti ti-upload"></i> Upload Bukti Pembayaran
                </div>
                <div class="file-drop" id="fileDrop">
                    <input type="file" name="bukti" accept="image/*" required
                           onchange="previewBukti(event)">
                    <i class="ti ti-photo-up"></i>
                    <p>Klik atau drag foto bukti transfer</p>
                    <span>JPG, PNG · Maks 5MB</span>
                </div>
                <div class="file-preview" id="buktiPreview">
                    <img id="buktiImg" src="" alt="">
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-bayar" id="btnBayar">
                <i class="ti ti-send"></i> Kirim Bukti Pembayaran
            </button>

        </div>

        <!-- KANAN: Order summary -->
        <div>
            <div class="order-card">
                <div class="order-head"><i class="ti ti-receipt"></i> Ringkasan Pesanan</div>
                <div class="order-item">
                    <div class="order-thumb">
                        <?php if ($gambar): ?>
                            <img src="<?= htmlspecialchars($gambar) ?>" alt="">
                        <?php else: ?>
                            <i class="ti ti-box-seam"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="order-name"><?= htmlspecialchars($rental['nama_barang']) ?></div>
                        <div class="order-sub">Pemilik: <?= htmlspecialchars($rental['pemilik']) ?></div>
                    </div>
                </div>
                <div class="order-rows">
                    <div class="order-row">
                        <span>Mulai</span>
                        <strong><?= date('d M Y', strtotime($rental['tanggal_mulai'])) ?></strong>
                    </div>
                    <div class="order-row">
                        <span>Selesai</span>
                        <strong><?= date('d M Y', strtotime($rental['tanggal_selesai'])) ?></strong>
                    </div>
                    <div class="order-row">
                        <span>Durasi</span>
                        <strong><?= $durasi ?> hari</strong>
                    </div>
                    <div class="order-row">
                        <span>Harga/hari</span>
                        <strong>Rp <?= number_format($rental['harga'], 0, ',', '.') ?></strong>
                    </div>
                </div>
                <div class="order-total">
                    <span>Total</span>
                    <strong>Rp <?= number_format($total, 0, ',', '.') ?></strong>
                </div>
            </div>

            <div style="margin-top:12px;background:#fff7e6;border:1px solid #fed7aa;border-radius:12px;padding:12px 14px;font-size:12.5px;color:#92400e;display:flex;gap:8px;">
                <i class="ti ti-info-circle" style="font-size:16px;flex-shrink:0;margin-top:1px;"></i>
                Pembayaran masuk ke admin ItemLend terlebih dahulu sebelum diteruskan ke pemilik barang.
            </div>
        </div>

    </div>
    </form>
</div>

<script>
function showInstruksi(key) {
    document.querySelectorAll('.instruksi-box').forEach(el => el.classList.remove('show'));
    const box = document.getElementById('instruksi_' + key);
    if (box) box.classList.add('show');
}

function previewBukti(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
        document.getElementById('buktiImg').src = ev.target.result;
        document.getElementById('buktiPreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function copyNominal(nominal) {
    navigator.clipboard.writeText(nominal).then(() => {
        const btn = event.target.closest('.copy-btn');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-check" style="font-size:14px;"></i> Tersalin!';
        setTimeout(() => btn.innerHTML = orig, 2000);
    });
}
</script>

<?php endif; ?>