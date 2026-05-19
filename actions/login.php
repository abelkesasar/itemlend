<?php
session_start();
require '../config/db.php';

$username = $_POST['username'];
$password = md5($_POST['password']);

echo $username;
echo "<br>";
echo $password;
echo "<br>";

$stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND password=?");
$stmt->execute([$username, $password]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

var_dump($user);

if ($user) {

    if ($user['status'] != 'approved') {
        echo "Akun belum di approve admin";
        exit;
    }

    $_SESSION['user'] = $user;
    $_SESSION['role'] = $user['role'];

    if ($user['role'] == 'admin') {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../index.php");
    }

} else {
    echo "Login gagal";
}
?>