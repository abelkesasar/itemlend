<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php?page=login");
    exit;
}

$user_id     = (int) $_SESSION['user'];
$nama_barang = trim($_POST['nama_barang'] ?? '');
$deskripsi   = trim($_POST['deskripsi']   ?? '');
$harga       = (int) ($_POST['harga']     ?? 0);
$stok        = (int) ($_POST['stok']      ?? 1);
$kategori    = trim($_POST['kategori']    ?? '');
$lokasi      = trim($_POST['lokasi']      ?? '');

// Validasi dasar
if (!$nama_barang || !$harga) {
    echo "<script>alert('Nama barang dan harga wajib diisi!'); history.back();</script>";
    exit;
}

// Handle upload gambar
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

// Insert ke DB
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

echo "
<script>
    alert('Barang berhasil didaftarkan! Menunggu approval admin.');
    window.location='../index.php?page=barangsaya';
</script>
";a
?>