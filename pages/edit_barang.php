<?php
// FIX SESSION (biar gak error)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// FIX PATH DATABASE (anti error path)
require __DIR__ . '/../config/db.php';

// CEK LOGIN
if (!isset($_SESSION['user'])) {
    die("Harus login!");
}

// CEK ID
if (!isset($_GET['id'])) {
    die("ID tidak ditemukan");
}

$id = $_GET['id'];

// AMBIL DATA BARANG
$stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

// CEK DATA ADA
if (!$item) {
    die("Barang tidak ditemukan");
}

// 🚨 CEK OWNER (INI YANG PALING PENTING)
if ($_SESSION['user']['id'] != $item['user_id']) {
    die("Akses ditolak!");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Barang</title>
</head>
<body>

<h2>Edit Barang</h2>

<form action="actions/update_barang.php" method="POST">
    <input type="hidden" name="id" value="<?= $item['id'] ?>">

    <label>Nama Barang:</label><br>
    <input type="text" name="nama_barang" value="<?= htmlspecialchars($item['nama_barang']) ?>" required><br><br>

    <label>Deskripsi:</label><br>
    <textarea name="deskripsi" required><?= htmlspecialchars($item['deskripsi']) ?></textarea><br><br>

    <label>Harga per Hari:</label><br>
    <input type="number" name="harga" value="<?= $item['harga'] ?>" required><br><br>

    <button type="submit">Update</button>
</form>

<br>
<a href="index.php">Kembali</a>

</body>
</html>