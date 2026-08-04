<?php
$host = "localhost"; // <-- Sudah diubah menjadi localhost
$db   = "itemlend";
$user = "root";
$pass = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>