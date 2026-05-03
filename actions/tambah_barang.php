<?php
session_start();
require '../config/db.php';

$user_id = $_SESSION['user']['id'];

$nama = $_POST['nama_barang'];
$deskripsi = $_POST['deskripsi'];
$harga = $_POST['harga'];

$stmt = $conn->prepare("INSERT INTO items (user_id, nama_barang, deskripsi, harga) VALUES (?, ?, ?, ?)");
$stmt->execute([$user_id, $nama, $deskripsi, $harga]);

header("Location: ../index.php");