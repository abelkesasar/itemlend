<?php
/**
 * api/api_update_profile.php
 * Endpoint POST untuk update profil user, termasuk metode pembayaran
 * (wajib diisi sebelum user boleh menambahkan barang).
 *
 * Request: multipart/form-data
 * Header : Authorization: Bearer <token>
 * Fields : username, email, nomor_wa, alamat, deskripsi_vendor,
 *          nama_penyedia, nomor_rekening, nama_pemilik_rekening,
 *          password (opsional, isi kalau mau ganti)
 * Files  : foto_profil (opsional), foto_qris (opsional)
 */

require 'api_auth_middleware.php'; // sudah include db.php + validasi token, hasilnya ada di $user

header('Content-Type: application/json');

function respond(array $data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$id       = (int) $user['id'];
$username = trim($_POST['username'] ?? $user['username']);
$email    = trim($_POST['email'] ?? $user['email']);
$nomor_wa = trim($_POST['nomor_wa'] ?? $user['nomor_wa']);
$alamat   = trim($_POST['alamat'] ?? $user['alamat']);
$deskripsi_vendor = $_POST['deskripsi_vendor'] ?? $user['deskripsi_vendor'];

$nama_penyedia         = trim($_POST['nama_penyedia'] ?? '');
$nomor_rekening        = trim($_POST['nomor_rekening'] ?? '');
$nama_pemilik_rekening = trim($_POST['nama_pemilik_rekening'] ?? '');
$metode_pembayaran     = $nama_penyedia !== '' ? 'ewallet' : null; // sama seperti logic web

$foto_profil = $user['foto_profil'];
$password    = $user['password'];
$foto_qris   = $user['foto_qris'];

// --- Upload foto profil baru (kalau ada) ---
if (!empty($_FILES['foto_profil']['name'])) {
    $foto_profil = time() . '_' . basename($_FILES['foto_profil']['name']);
    move_uploaded_file($_FILES['foto_profil']['tmp_name'], "../uploads/" . $foto_profil);
}

// --- Upload foto QRIS baru (kalau ada) ---
if (!empty($_FILES['foto_qris']['name'])) {
    $foto_qris = time() . '_qris_' . basename($_FILES['foto_qris']['name']);
    move_uploaded_file($_FILES['foto_qris']['tmp_name'], "../uploads/" . $foto_qris);
}

// --- Ganti password kalau diisi ---
if (!empty($_POST['password'])) {
    $password = md5($_POST['password']); // konsisten dengan sistem lama (auto-upgrade ke bcrypt saat login)
}

try {
    $stmt = $conn->prepare("
        UPDATE users
        SET username = ?, email = ?, nomor_wa = ?, alamat = ?, deskripsi_vendor = ?, foto_profil = ?, password = ?,
            metode_pembayaran = ?, nama_penyedia = ?, nomor_rekening = ?, nama_pemilik_rekening = ?, foto_qris = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $username, $email, $nomor_wa, $alamat, $deskripsi_vendor, $foto_profil, $password,
        $metode_pembayaran, $nama_penyedia, $nomor_rekening, $nama_pemilik_rekening, $foto_qris,
        $id
    ]);

    respond([
        'success' => true,
        'message' => 'Profil berhasil diperbarui.',
        'data' => [
            'metode_pembayaran_lengkap' => !empty($nama_penyedia),
        ],
    ]);

} catch (PDOException $e) {
    respond([
        'success' => false,
        'message' => 'Terjadi kesalahan server.',
        'debug' => $e->getMessage()
    ], 500);
}