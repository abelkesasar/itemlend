<?php
require '../config/db.php';

$username = $_POST['username'];
$password = md5($_POST['password']);

$conn->query("INSERT INTO users (username, password) 
VALUES ('$username', '$password')");

echo "Register berhasil! Tunggu di-approve admin.<br>";
echo "<a href='../index.php?page=login'>Kembali ke Login</a>";
?>