<?php
/**
 * api/api_register.php
 * Endpoint register untuk konsumsi Mobile App (Flutter) - ItemLend
 *
 * Request harus dikirim sebagai multipart/form-data (bukan JSON),
 * karena ada file upload (KTP/KTM). Di Flutter pakai http.MultipartRequest.
 *
 * Field yang dikirim:
 * - username, email, password, alamat, nomor_wa (wajib)
 * - file 'ktp_user' dan 'ktm' (wajib)
 * - role otomatis 'user' (nggak ada pilihan vendor lagi)
 */

header('Content-Type: application/json');
require '../config/db.php';

function respond(array $data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$alamat   = trim($_POST['alamat'] ?? '');
$nomor_wa = trim($_POST['nomor_wa'] ?? '');

$role = 'user';
$status = 'pending';
$deskripsi_vendor = null;
$foto_profil = 'default.png';

// --- Validasi field wajib ---
if ($username === '' || $email === '' || $password === '') {
    respond([
        'success' => false,
        'message' => 'Username, email, dan password wajib diisi.'
    ], 400);
}

if (empty($_FILES['ktp_user']['name']) || empty($_FILES['ktm']['name'])) {
    respond([
        'success' => false,
        'message' => 'Foto KTP dan KTM wajib diunggah.'
    ], 400);
}

/*
|--------------------------------------------------------------------------
| UPLOAD DOKUMEN (KTP & KTM)
|--------------------------------------------------------------------------
*/
$uploadDir = "../uploads/";

// Kasih prefix unik biar nggak saling menimpa file dengan nama sama
$ktp = uniqid('ktp_') . '_' . basename($_FILES['ktp_user']['name']);
$ktm = uniqid('ktm_') . '_' . basename($_FILES['ktm']['name']);

$tmpKtp = $_FILES['ktp_user']['tmp_name'];
$tmpKtm = $_FILES['ktm']['tmp_name'];

if (!move_uploaded_file($tmpKtp, $uploadDir . $ktp) ||
    !move_uploaded_file($tmpKtm, $uploadDir . $ktm)) {
    respond([
        'success' => false,
        'message' => 'Gagal mengunggah file KTP/KTM.'
    ], 500);
}

/*
|--------------------------------------------------------------------------
| INSERT DATABASE
|--------------------------------------------------------------------------
*/
try {
    // Cek username/email sudah dipakai atau belum
    $cek = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $cek->execute([$username, $email]);
    if ($cek->fetch()) {
        respond([
            'success' => false,
            'message' => 'Username atau email sudah terdaftar.'
        ], 409);
    }

    $hashedPassword = md5($password); // tetap MD5 supaya konsisten dengan sistem lama (bisa di-upgrade otomatis saat login, seperti pada api_login.php)

    $stmt = $conn->prepare("
        INSERT INTO users
        (username, email, password, alamat, nomor_wa, role, status, foto_profil, ktp, ktm, deskripsi_vendor)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ");

    $stmt->execute([
        $username,
        $email,
        $hashedPassword,
        $alamat,
        $nomor_wa,
        $role,
        $status,
        $foto_profil,
        $ktp,
        $ktm,
        $deskripsi_vendor
    ]);

    respond([
        'success' => true,
        'message' => 'Register berhasil! Akun kamu menunggu persetujuan admin.',
        'data' => [
            'id'       => $conn->lastInsertId(),
            'username' => $username,
            'role'     => $role,
            'status'   => $status,
        ]
    ]);

} catch (PDOException $e) {
    respond([
        'success' => false,
        'message' => 'Terjadi kesalahan server.',
        // Hapus/jangan tampilkan detail error di production!
        'debug' => $e->getMessage()
    ], 500);
}