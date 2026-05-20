<?php
require '../config/db.php';

$id = $_POST['id'];
$nama = $_POST['nama_barang'];
$deskripsi = $_POST['deskripsi'];
$harga = $_POST['harga'];

if ($_FILES['gambar']['name']) {

    $gambar = time() . '_' . $_FILES['gambar']['name'];

    move_uploaded_file(
        $_FILES['gambar']['tmp_name'],
        '../uploads/' . $gambar
    );

    $stmt = $conn->prepare("
        UPDATE items 
        SET nama_barang=?, deskripsi=?, harga=?, gambar=?
        WHERE id=?
    ");

    $stmt->execute([
        $nama,
        $deskripsi,
        $harga,
        $gambar,
        $id
    ]);

} else {

    $stmt = $conn->prepare("
        UPDATE items 
        SET nama_barang=?, deskripsi=?, harga=?
        WHERE id=?
    ");

    $stmt->execute([
        $nama,
        $deskripsi,
        $harga,
        $id
    ]);
}

header("Location: ../index.php");