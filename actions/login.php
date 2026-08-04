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

// ✅ CEK STATUS — admin selalu boleh masuk
if ($user['role'] !== 'admin') {

    // Status: pending
    if ($user['status'] === 'pending') {
        echo "<script>alert('Akun kamu masih menunggu persetujuan admin. Silakan tunggu ya!'); window.location='../index.php?page=login';</script>";
        exit;
    }

    // Status: cooldown
    if ($user['status'] === 'cooldown') {

        $now = new DateTime();
        $bannedUntil = $user['banned_until'] ? new DateTime($user['banned_until']) : null;

        if ($bannedUntil && $now < $bannedUntil) {
            // Masih dalam masa cooldown → hitung sisa waktunya
            $diff = $now->diff($bannedUntil);

            $sisa = [];
            if ($diff->d > 0) $sisa[] = $diff->d . ' hari';
            if ($diff->h > 0) $sisa[] = $diff->h . ' jam';
            if ($diff->i > 0) $sisa[] = $diff->i . ' menit';
            if (empty($sisa)) $sisa[] = 'kurang dari 1 menit';

            $sisaText = implode(' ', $sisa);

            echo "<script>alert('Akun kamu sedang terkena cooldown. Sisa waktu: {$sisaText} lagi.'); window.location='../index.php?page=login';</script>";
            exit;

        } else {
            // Cooldown sudah lewat → otomatis pulihkan status jadi approved
            $update = $conn->prepare("UPDATE users SET status = 'approved', banned_until = NULL WHERE id = ?");
            $update->execute([$user['id']]);
            $user['status'] = 'approved';
        }
    }
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