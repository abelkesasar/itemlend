<?php
session_start();
require '../config/db.php';

$username = $_POST['username'];
$password = md5($_POST['password']);

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    echo "<script>alert('Username tidak ditemukan!'); window.location='../index.php?page=login';</script>";
    exit;
}

if ($user['password'] != $password) {
    echo "<script>alert('Password salah!'); window.location='../index.php?page=login';</script>";
    exit;
}

// ✅ CEK STATUS APPROVAL — admin selalu boleh masuk
if ($user['role'] !== 'admin' && $user['status'] === 'pending') {
    echo "<script>alert('Akun kamu masih menunggu persetujuan admin. Silakan tunggu ya!'); window.location='../index.php?page=login';</script>";
    exit;
}

$_SESSION['user']     = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role']     = $user['role'];

if ($user['role'] == 'admin') {
    header("Location: ../admin/dashboard.php");
} else {
    header("Location: ../index.php?page=home");
}
exit;