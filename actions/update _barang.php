<?php
session_start();
require '../config/db.php';

// ambil data dari form
$id = $_POST['id'];
$nama = $_POST['nama_barang'];
$deskripsi = $_POST['deskripsi'];
$harga = $_POST['harga'];

// ambil data barang dulu
$stmt = $conn->prepare("SELECT * FROM items WHERE id=?");
$stmt->execute([$id]);
$item = $stmt->fetch();

// 🚫 CEK: apakah ini bukan pemilik
if (!$item || $_SESSION['user']['id'] != $item['user_id']) {
    echo "Akses ditolak!";
    exit;
}

// update data
$stmt = $conn->prepare("
    UPDATE items 
    SET nama_barang=?, deskripsi=?, harga=? 
    WHERE id=?
");

$stmt->execute([$nama, $deskripsi, $harga, $id]);

// redirect balik
header("Location: ../index.php");
exit;