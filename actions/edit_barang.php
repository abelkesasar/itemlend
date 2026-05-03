<?php
session_start();
require '../config/db.php';

$id = $_POST['id'];
$nama = $_POST['nama'];
$deskripsi = $_POST['deskripsi'];
$harga = $_POST['harga'];

# VALIDASI OWNER
$stmt = $conn->prepare("SELECT user_id FROM items WHERE id=?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if ($_SESSION['user']['id'] != $item['user_id']) {
    die("Akses ditolak!");
}

$stmt = $conn->prepare("
    UPDATE items 
    SET nama_barang=?, deskripsi=?, harga=? 
    WHERE id=?
");
$stmt->execute([$nama, $deskripsi, $harga, $id]);

header("Location: ../index.php?page=detail&id=".$id);