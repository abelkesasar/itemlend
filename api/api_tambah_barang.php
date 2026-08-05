<?php
/**
 * api/api_tambah_barang.php
 * Endpoint untuk user menambahkan barang baru dari mobile app.
 * Wajib login (pakai token, bukan session) — barang masuk dengan status 'pending'
 * menunggu approval admin.
 *
 * Request: multipart/form-data
 * Header : Authorization: Bearer <token>
 * Fields : nama_barang (wajib), harga (wajib), deskripsi, stok, kategori, lokasi
 * Files  : gambar[] (bisa lebih dari 1 foto, field name harus 'gambar[]' di Flutter)
 */

require 'api_auth_middleware.php'; // udah include db.php + validasi token, hasilnya ada di $user

function respond(array $data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$user_id     = (int) $user['id'];
$nama_barang = trim($_POST['nama_barang'] ?? '');
$deskripsi   = trim($_POST['deskripsi']   ?? '');
$harga       = (int) ($_POST['harga']     ?? 0);
$stok        = (int) ($_POST['stok']      ?? 1);
$kategori    = trim($_POST['kategori']    ?? '');
$lokasi      = trim($_POST['lokasi']      ?? '');

// --- Validasi dasar ---
if (!$nama_barang || !$harga) {
    respond([
        'success' => false,
        'message' => 'Nama barang dan harga wajib diisi.'
    ], 400);
}

// --- Handle upload gambar (bisa lebih dari satu file) ---
$gambar_list = [];

if (!empty($_FILES['gambar']['name'][0])) {

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    foreach ($_FILES['gambar']['name'] as $key => $name) {

        $tmp_name = $_FILES['gambar']['tmp_name'][$key];
        $size     = $_FILES['gambar']['size'][$key];

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            continue;
        }

        if ($size > 5 * 1024 * 1024) {
            continue;
        }

        $new_name = 'item_' . $user_id . '_' . time() . '_' . $key . '.' . $ext;

        move_uploaded_file($tmp_name, "../uploads/" . $new_name);

        $gambar_list[] = $new_name;
    }
}

$gambar = json_encode($gambar_list);

// --- Insert ke DB ---
try {
    $stmt = $conn->prepare("
        INSERT INTO items
            (user_id, nama_barang, kategori, deskripsi, harga, stok, lokasi, gambar, status)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    $stmt->execute([
        $user_id,
        $nama_barang,
        $kategori,
        $deskripsi,
        $harga,
        $stok,
        $lokasi,
        $gambar,
    ]);

    respond([
        'success' => true,
        'message' => 'Barang berhasil didaftarkan! Menunggu approval admin.',
        'data' => [
            'id' => $conn->lastInsertId(),
        ]
    ]);

} catch (PDOException $e) {
    respond([
        'success' => false,
        'message' => 'Terjadi kesalahan server.',
        'debug' => $e->getMessage()
    ], 500);
}