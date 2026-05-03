<?php
session_start();
require '../config/db.php';

$username = $_POST['username'];
$password = md5($_POST['password']);

$stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND password=?");
$stmt->execute([$username, $password]);

$user = $stmt->fetch(); // ✅ ini PDO

if ($user) {
    if ($user['status'] == 'approved') {
        $_SESSION['user'] = $user;
        $_SESSION['role'] = $user['role'];

        header("Location: ../index.php");
    } else {
        echo "Akun belum di-approve!";
    }
} else {
    echo "Login gagal!";
}
?>