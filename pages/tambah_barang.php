<?php
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user'])) {
    echo "<script>window.location='index.php?page=login';</script>";
    exit;
}

// Cek dulu apakah user sudah punya metode pembayaran lengkap.
// Kalau belum, arahkan ke profile.php buat melengkapinya dulu.
$stmtPay = $conn->prepare("SELECT metode_pembayaran, nama_penyedia, nomor_rekening, nama_pemilik_rekening FROM users WHERE id = ?");
$stmtPay->execute([$_SESSION['user']]);
$payInfo = $stmtPay->fetch(PDO::FETCH_ASSOC);

if (empty($payInfo['metode_pembayaran']) || empty($payInfo['nomor_rekening']) || empty($payInfo['nama_pemilik_rekening'])) {
    echo "<script>window.location='index.php?page=profile&need_payment=1';</script>";
    exit;
}
?>
<style>
    .tb-wrap {
        max-width: 680px; margin: 0 auto; padding: 8px 0 60px;
    }

    /* ── Header ── */
    .tb-header { margin-bottom: 28px; }
    .tb-back {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 13px; color: #6b7280; text-decoration: none;
        margin-bottom: 14px; transition: color 0.15s;
    }
    .tb-back:hover { color: #3d4bff; }
    .tb-title { font-size: 26px; font-weight: 800; color: #1a1d2e; }
    .tb-sub   { font-size: 13px; color: #6b7280; margin-top: 4px; }

    /* ── Card ── */
    .tb-card {
        background: #fff; border: 1px solid #e5e7eb;
        border-radius: 20px; padding: 32px;
    }

    /* ── Section label ── */
    .form-section {
        font-size: 11px; font-weight: 700; color: #9ca3af;
        text-transform: uppercase; letter-spacing: 0.08em;
        margin-bottom: 14px; margin-top: 24px;
        display: flex; align-items: center; gap: 8px;
    }
    .form-section::after {
        content: ''; flex: 1; height: 1px; background: #f0f1f3;
    }
    .form-section:first-child { margin-top: 0; }

    /* ── Form grid ── */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .form-group { display: flex; flex-direction: column; gap: 5px; }
    .form-group.full { grid-column: 1/-1; }

    label {
        font-size: 12.5px; font-weight: 600; color: #374151;
        display: flex; align-items: center; gap: 5px;
    }
    label i { font-size: 14px; color: #9ca3af; }

    input[type=text],
    input[type=number],
    select,
    textarea {
        width: 100%; border: 1.5px solid #e5e7eb; border-radius: 10px;
        padding: 10px 14px; font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 13.5px; color: #1a1d2e; background: #fff; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    input:focus, select:focus, textarea:focus {
        border-color: #3d4bff;
        box-shadow: 0 0 0 3px rgba(61,75,255,0.1);
    }
    textarea { resize: none; }

    /* Input prefix (Rp) */
    .input-prefix-wrap {
        display: flex; align-items: center;
        border: 1.5px solid #e5e7eb; border-radius: 10px;
        overflow: hidden; transition: border-color 0.15s, box-shadow 0.15s;
    }
    .input-prefix-wrap:focus-within {
        border-color: #3d4bff;
        box-shadow: 0 0 0 3px rgba(61,75,255,0.1);
    }
    .input-prefix {
        padding: 10px 12px; background: #f8f9fb;
        font-size: 13px; font-weight: 600; color: #6b7280;
        border-right: 1.5px solid #e5e7eb; white-space: nowrap;
    }
    .input-prefix-wrap input {
        border: none; border-radius: 0; box-shadow: none;
        flex: 1; padding: 10px 12px;
    }
    .input-prefix-wrap input:focus { box-shadow: none; }

    /* File upload */
    .file-drop {
        border: 2px dashed #d1d5db; border-radius: 12px;
        padding: 28px 20px; text-align: center; cursor: pointer;
        transition: border-color 0.15s, background 0.15s;
        position: relative;
    }
    .file-drop:hover { border-color: #3d4bff; background: #fafbff; }
    .file-drop input[type=file] {
        position: absolute; inset: 0; opacity: 0; cursor: pointer;
        width: 100%; height: 100%;
    }
    .file-drop-icon { font-size: 32px; color: #d1d5db; margin-bottom: 8px; }
    .file-drop-text { font-size: 13px; font-weight: 600; color: #374151; }
    .file-drop-sub  { font-size: 12px; color: #9ca3af; margin-top: 4px; }
    .file-preview {
        margin-top: 10px; display: none;
        border-radius: 10px; overflow: hidden;
        border: 1px solid #e5e7eb; max-height: 160px;
    }
    .file-preview img { width: 100%; height: 160px; object-fit: cover; }

    /* Stok helper */
    .stok-hint {
        font-size: 11.5px; color: #6b7280; margin-top: 4px;
        display: flex; align-items: center; gap: 4px;
    }
    .stok-hint i { font-size: 13px; color: #9ca3af; }

    /* Submit */
    .tb-footer { display: flex; gap: 10px; margin-top: 28px; }
    .btn-submit {
        flex: 1; padding: 13px; background: #3d4bff; color: #fff;
        border: none; border-radius: 12px;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 15px; font-weight: 700; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        transition: background 0.15s, transform 0.1s;
    }
    .btn-submit:hover  { background: #2c38d4; }
    .btn-submit:active { transform: scale(0.98); }
    .btn-cancel {
        padding: 13px 24px; background: #fff; color: #6b7280;
        border: 1.5px solid #e5e7eb; border-radius: 12px;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 14px; font-weight: 600; cursor: pointer;
        text-decoration: none; display: flex; align-items: center; gap: 6px;
        transition: background 0.15s;
    }
    .btn-cancel:hover { background: #f4f5f7; }

    .submit-note {
        display: flex; align-items: center; gap: 6px; justify-content: center;
        font-size: 12px; color: #9ca3af; margin-top: 12px; text-align: center;
    }
    .submit-note i { font-size: 14px; color: #f59e0b; }

    @media (max-width: 560px) {
        .tb-card { padding: 20px 16px; }
        .form-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="tb-wrap">

    <div class="tb-header">
        <a href="index.php" class="tb-back">
            <i class="ti ti-arrow-left"></i> Kembali
        </a>
        <div class="tb-title">Daftarkan Barang</div>
        <div class="tb-sub">Isi detail barang yang ingin kamu sewakan</div>
    </div>

    <div class="tb-card">
        <form action="actions/tambah_barang.php" method="POST" enctype="multipart/form-data">

            <!-- SECTION: Info Barang -->
            <div class="form-section">Info Barang</div>
            <div class="form-grid">

                <div class="form-group full">
                    <label><i class="ti ti-tag"></i> Nama Barang</label>
                    <input type="text" name="nama_barang" placeholder="contoh: Sony A7III Camera Kit" required>
                </div>

                <div class="form-group">
                    <label><i class="ti ti-category"></i> Kategori</label>
                    <select name="kategori">
                        <option value="">-- Pilih Kategori --</option>
                        <option value="Elektronik">Elektronik</option>
                        <option value="Kendaraan">Kendaraan</option>
                        <option value="Kamera">Kamera</option>
                        <option value="Alat Rumah">Alat Rumah</option>
                        <option value="Camping">Camping</option>
                        <option value="Olahraga">Olahraga</option>
                        <option value="Pakaian">Pakaian</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="ti ti-map-pin"></i> Lokasi</label>
                    <input type="text" name="lokasi" placeholder="contoh: Bandung, Jawa Barat">
                </div>

                <div class="form-group full">
                    <label><i class="ti ti-align-left"></i> Deskripsi</label>
                    <textarea name="deskripsi" rows="4" placeholder="Jelaskan kondisi, spesifikasi, dan syarat penggunaan barang..."></textarea>
                </div>

            </div>

            <!-- SECTION: Harga & Stok -->
            <div class="form-section">Harga & Stok</div>
            <div class="form-grid">

                <div class="form-group">
                    <label><i class="ti ti-coin"></i> Harga Sewa / Hari</label>
                    <div class="input-prefix-wrap">
                        <span class="input-prefix">Rp</span>
                        <input type="number" name="harga" placeholder="50000" min="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="ti ti-stack-2"></i> Stok</label>
                    <input type="number" name="stok" placeholder="1" min="1" value="1" required>
                    <span class="stok-hint"><i class="ti ti-info-circle"></i> Jumlah unit yang tersedia untuk disewa</span>
                </div>

            </div>

            <!-- SECTION: Foto -->
            <div class="form-section">Foto Barang</div>
            <div class="form-group full">
                <label><i class="ti ti-photo"></i> Upload Foto</label>
                <div class="file-drop" id="fileDrop">
                    <input type="file" name="gambar[]" accept="image/*" id="fileInput"
                           onchange="previewGambar(event)" multiple required>
                    <div id="fileDropContent">
                        <div class="file-drop-icon"><i class="ti ti-cloud-upload"></i></div>
                        <div class="file-drop-text">Klik atau drag foto ke sini</div>
                        <div class="file-drop-sub">JPG, PNG, WEBP · Maks 5MB</div>
                    </div>
                    <div class="file-preview" id="filePreview">
                        <img id="previewImg" src="" alt="">
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="tb-footer">
                <a href="index.php" class="btn-cancel">
                    <i class="ti ti-x"></i> Batal
                </a>
                <button type="submit" class="btn-submit">
                    <i class="ti ti-device-floppy"></i> Daftarkan Barang
                </button>
            </div>

        </form>

        <div class="submit-note">
            <i class="ti ti-shield-check"></i>
            Barang akan ditinjau admin sebelum tampil di marketplace
        </div>
    </div>

</div>

<script>
function previewGambar(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(ev) {
        document.getElementById('previewImg').src = ev.target.result;
        document.getElementById('filePreview').style.display = 'block';
        document.getElementById('fileDropContent').style.display = 'none';
    };
    reader.readAsDataURL(file);
}
</script>