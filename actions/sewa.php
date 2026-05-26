<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php?page=login");
    exit;
}

// FIX: $_SESSION['user'] langsung integer, bukan array
$user_id = (int) $_SESSION['user'];
$item_id = (int) ($_POST['item_id'] ?? 0);
$start   = $_POST['start'] ?? '';
$end     = $_POST['end']   ?? '';

// Validasi input
if (!$item_id || !$start || !$end) {
    echo "<script>alert('Data tidak lengkap!'); history.back();</script>";
    exit;
}

if ($start < date('Y-m-d')) {
    echo "<script>alert('Tanggal mulai tidak boleh di masa lalu!'); history.back();</script>";
    exit;
}

if ($end <= $start) {
    echo "<script>alert('Tanggal selesai harus setelah tanggal mulai!'); history.back();</script>";
    exit;
}

// Ambil data barang
$stmt = $conn->prepare("SELECT user_id, harga FROM items WHERE id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo "<script>alert('Barang tidak ditemukan!'); history.back();</script>";
    exit;
}

// FIX: validasi SEBELUM insert
if ($item['user_id'] == $user_id) {
    echo "<script>alert('Tidak bisa menyewa barang milik sendiri!'); history.back();</script>";
    exit;
}

// Hitung total
$durasi = (int) ((strtotime($end) - strtotime($start)) / 86400);
$total  = $durasi * $item['harga'];

// Insert rental
$stmt = $conn->prepare("
    INSERT INTO rentals
        (user_id, item_id, tanggal_mulai, tanggal_selesai, total_harga, status_pembayaran)
    VALUES
        (?, ?, ?, ?, ?, 'pending')
");
$stmt->execute([$user_id, $item_id, $start, $end, $total]);

$rental_id = $conn->lastInsertId();

// Redirect ke halaman pembayaran
header("Location: ../index.php?page=pembayaran&rental_id=$rental_id");
exit;
?>