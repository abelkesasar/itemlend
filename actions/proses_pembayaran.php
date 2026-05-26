<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php?page=login");
    exit;
}

$user_id   = (int) $_SESSION['user'];
$rental_id = (int) ($_POST['rental_id'] ?? 0);
$metode    = trim($_POST['metode'] ?? '');

$metode_valid = ['qris','mandiri','bri','bca','gopay','shopee','dana'];

if (!$rental_id || !in_array($metode, $metode_valid)) {
    echo "<script>alert('Data tidak lengkap!'); history.back();</script>";
    exit;
}

// Pastikan rental milik user ini
$cek = $conn->prepare("SELECT id FROM rentals WHERE id = ? AND user_id = ?");
$cek->execute([$rental_id, $user_id]);
if (!$cek->fetch()) {
    echo "<script>alert('Akses ditolak!'); history.back();</script>";
    exit;
}

// Upload bukti
if (empty($_FILES['bukti']['name'])) {
    echo "<script>alert('Bukti pembayaran wajib diupload!'); history.back();</script>";
    exit;
}

$ext     = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','webp'];

if (!in_array($ext, $allowed)) {
    echo "<script>alert('Format file tidak valid! Gunakan JPG atau PNG.'); history.back();</script>";
    exit;
}

if ($_FILES['bukti']['size'] > 5 * 1024 * 1024) {
    echo "<script>alert('Ukuran file maksimal 5MB!'); history.back();</script>";
    exit;
}

$nama_file = 'bukti_' . $rental_id . '_' . time() . '.' . $ext;
move_uploaded_file($_FILES['bukti']['tmp_name'], "../uploads/bukti/" . $nama_file);

// Update rental
$stmt = $conn->prepare("
    UPDATE rentals SET
        metode_pembayaran = ?,
        bukti_pembayaran  = ?,
        status_pembayaran = 'menunggu_konfirmasi'
    WHERE id = ? AND user_id = ?
");
$stmt->execute([$metode, $nama_file, $rental_id, $user_id]);

// Redirect ke halaman pembayaran (waiting screen)
header("Location: ../index.php?page=pembayaran&rental_id=$rental_id");
exit;
?>