<?php
require '../config/db.php';

$username = $_POST['username'];
$password = md5($_POST['password']);

$stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND password=?");
$stmt->execute([$username, $password]);

if ($stmt->fetch()) {
    echo json_encode([
        "success" => true,
        "message" => "Login berhasil"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Username atau password salah"
    ]);
}