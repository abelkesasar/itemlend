<?php
require '../config/db.php';

header('Content-Type: application/json');

// Validasi method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Method harus POST"
    ]);
    exit;
}

// Ambil data
$user_id     = (int) ($_POST['user_id'] ?? 0);
$nama_barang = trim($_POST['nama_barang'] ?? '');
$deskripsi   = trim($_POST['deskripsi'] ?? '');
$harga       = (int) ($_POST['harga'] ?? 0);
$stok        = (int) ($_POST['stok'] ?? 1);
$kategori    = trim($_POST['kategori'] ?? '');
$lokasi      = trim($_POST['lokasi'] ?? '');

// Validasi
if ($user_id <= 0 || $nama_barang == '' || $harga <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Data tidak lengkap"
    ]);
    exit;
}

// Upload gambar
$gambar_list = [];

if (isset($_FILES['gambar'])) {

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    // Multiple file
    if (is_array($_FILES['gambar']['name'])) {

        foreach ($_FILES['gambar']['name'] as $key => $name) {

            if ($name == '') continue;

            $tmp = $_FILES['gambar']['tmp_name'][$key];
            $size = $_FILES['gambar']['size'][$key];

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) continue;
            if ($size > 5 * 1024 * 1024) continue;

            $new_name = "item_" . $user_id . "_" . time() . "_" . $key . "." . $ext;

            if (move_uploaded_file($tmp, "../uploads/" . $new_name)) {
                $gambar_list[] = $new_name;
            }
        }

    } else {

        // Single file
        if ($_FILES['gambar']['name'] != '') {

            $name = $_FILES['gambar']['name'];
            $tmp = $_FILES['gambar']['tmp_name'];
            $size = $_FILES['gambar']['size'];

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed) && $size <= 5 * 1024 * 1024) {

                $new_name = "item_" . $user_id . "_" . time() . "." . $ext;

                if (move_uploaded_file($tmp, "../uploads/" . $new_name)) {
                    $gambar_list[] = $new_name;
                }
            }
        }
    }
}

$gambar = json_encode($gambar_list);

// Simpan ke database
$stmt = $conn->prepare("
INSERT INTO items
(user_id, nama_barang, kategori, deskripsi, harga, stok, lokasi, gambar, status)
VALUES
(?, ?, ?, ?, ?, ?, ?, ?, 'pending')
");

$berhasil = $stmt->execute([
    $user_id,
    $nama_barang,
    $kategori,
    $deskripsi,
    $harga,
    $stok,
    $lokasi,
    $gambar
]);

if ($berhasil) {

    echo json_encode([
        "success" => true,
        "message" => "Barang berhasil ditambahkan",
        "item_id" => $conn->lastInsertId()
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Gagal menambahkan barang"
    ]);
}