<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php?page=login");
    exit;
}

$id       = $_SESSION['user'];
$username = $_POST['username'];
$email    = $_POST['email'];
$nomor_wa = $_POST['nomor_wa'];
$alamat   = $_POST['alamat'];
$deskripsi_vendor = $_POST['deskripsi_vendor'] ?? null;

// Metode pembayaran — semua opsi (QRIS, BRI, Mandiri, BCA, GoPay, ShopeePay, DANA)
// disimpan sebagai kategori 'ewallet', dibedakan lewat nama_penyedia
$nama_penyedia         = trim($_POST['nama_penyedia'] ?? '');
$nomor_rekening        = trim($_POST['nomor_rekening'] ?? '');
$nama_pemilik_rekening = trim($_POST['nama_pemilik_rekening'] ?? '');
$metode_pembayaran     = $nama_penyedia !== '' ? 'ewallet' : null;

// Ambil data lama dulu (buat foto & password default)
$stmt = $conn->prepare("SELECT foto_profil, password FROM users WHERE id = ?");
$stmt->execute([$id]);
$old = $stmt->fetch(PDO::FETCH_ASSOC);

$foto_profil = $old['foto_profil'];
$password    = $old['password'];

// Ganti foto kalau ada upload baru
if (!empty($_FILES['foto_profil']['name'])) {
    $foto_profil = time() . '_' . $_FILES['foto_profil']['name'];
    move_uploaded_file($_FILES['foto_profil']['tmp_name'], "../uploads/" . $foto_profil);
}

// Ganti password kalau diisi
if (!empty($_POST['password'])) {
    $password = md5($_POST['password']);
}

$stmt = $conn->prepare("
    UPDATE users
    SET username = ?, email = ?, nomor_wa = ?, alamat = ?, deskripsi_vendor = ?, foto_profil = ?, password = ?,
        metode_pembayaran = ?, nama_penyedia = ?, nomor_rekening = ?, nama_pemilik_rekening = ?
    WHERE id = ?
");
$stmt->execute([
    $username, $email, $nomor_wa, $alamat, $deskripsi_vendor, $foto_profil, $password,
    $metode_pembayaran, $nama_penyedia, $nomor_rekening, $nama_pemilik_rekening,
    $id
]);

$_SESSION['username'] = $username;

header("Location: ../index.php?page=profile&success=1");
exit;