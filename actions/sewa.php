<?php
session_start();
require '../config/db.php';

$user_id = $_SESSION['user']['id'];
$item_id = $_POST['item_id'];
$start = $_POST['start'];
$end = $_POST['end'];

# VALIDASI
if ($start < date('Y-m-d')) {
    die("Tanggal tidak boleh di masa lalu!");
}

if ($end <= $start) {
    die("Tanggal selesai harus setelah tanggal mulai!");
}

$stmt = $conn->prepare("INSERT INTO rentals (user_id, item_id, tanggal_mulai, tanggal_selesai) VALUES (?, ?, ?, ?)");
$stmt->execute([$user_id, $item_id, $start, $end]);
$stmt = $conn->prepare("SELECT user_id FROM items WHERE id=?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if ($item['user_id'] == $user_id) {
    die("Tidak bisa menyewa barang sendiri!");
}
echo "Berhasil sewa!<br>";
echo "<a href='../index.php'>Kembali ke Home</a>";