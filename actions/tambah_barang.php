<?php
session_start();
require '../config/db.php';

$nama = $_POST['nama_barang'];
$deskripsi = $_POST['deskripsi'];
$harga = $_POST['harga'];

$user_id = $_SESSION['user']; // FIX DI SINI

$gambar = $_FILES['gambar']['name'];
$tmp = $_FILES['gambar']['tmp_name'];

move_uploaded_file($tmp, "../uploads/" . $gambar);

$stmt = $conn->prepare("
INSERT INTO items
(nama_barang, deskripsi, harga, gambar, user_id)
VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([
    $nama,
    $deskripsi,
    $harga,
    $gambar,
    $user_id
]);

header("Location: ../index.php");
exit;