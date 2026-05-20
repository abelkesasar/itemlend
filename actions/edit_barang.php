<?php
session_start();
require '../config/db.php';

$id = $_POST['id'];
$nama = $_POST['nama_barang'];
$deskripsi = $_POST['deskripsi'];
$harga = $_POST['harga'];

$stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$id]);

$item = $stmt->fetch(PDO::FETCH_ASSOC);

$gambar = $item['gambar'];

if(!empty($_FILES['gambar']['name'])){

    $namaFile = time() . '_' . $_FILES['gambar']['name'];

    move_uploaded_file(
        $_FILES['gambar']['tmp_name'],
        "../uploads/" . $namaFile
    );

    $gambar = $namaFile;
}

$update = $conn->prepare("
    UPDATE items
    SET nama_barang = ?, deskripsi = ?, harga = ?, gambar = ?
    WHERE id = ?
");

$update->execute([
    $nama,
    $deskripsi,
    $harga,
    $gambar,
    $id
]);

header("Location: ../index.php?page=detail&id=$id");