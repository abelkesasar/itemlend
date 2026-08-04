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

$nama_penyedia         = trim($_POST['nama_penyedia'] ?? '');
$nomor_rekening        = trim($_POST['nomor_rekening'] ?? '');
$nama_pemilik_rekening = trim($_POST['nama_pemilik_rekening'] ?? '');
$metode_pembayaran     = $nama_penyedia !== '' ? 'ewallet' : null;

$stmt = $conn->prepare("SELECT foto_profil, password, foto_qris FROM users WHERE id = ?");
$stmt->execute([$id]);
$old = $stmt->fetch(PDO::FETCH_ASSOC);

$foto_profil = $old['foto_profil'];
$password    = $old['password'];
$foto_qris   = $old['foto_qris'];

if (!empty($_FILES['foto_profil']['name'])) {
    $foto_profil = time() . '_' . $_FILES['foto_profil']['name'];
    move_uploaded_file($_FILES['foto_profil']['tmp_name'], "../uploads/" . $foto_profil);
}

// Ganti foto QRIS kalau ada upload baru
if (!empty($_FILES['foto_qris']['name'])) {
    $foto_qris = time() . '_qris_' . $_FILES['foto_qris']['name'];
    move_uploaded_file($_FILES['foto_qris']['tmp_name'], "../uploads/" . $foto_qris);
}

if (!empty($_POST['password'])) {
    $password = md5($_POST['password']);
}

$stmt = $conn->prepare("
    UPDATE users
    SET username = ?, email = ?, nomor_wa = ?, alamat = ?, deskripsi_vendor = ?, foto_profil = ?, password = ?,
        metode_pembayaran = ?, nama_penyedia = ?, nomor_rekening = ?, nama_pemilik_rekening = ?, foto_qris = ?
    WHERE id = ?
");
$stmt->execute([
    $username, $email, $nomor_wa, $alamat, $deskripsi_vendor, $foto_profil, $password,
    $metode_pembayaran, $nama_penyedia, $nomor_rekening, $nama_pemilik_rekening, $foto_qris,
    $id
]);

$_SESSION['username'] = $username;

header("Location: ../index.php?page=profile&success=1");
exit;