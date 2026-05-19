<?php
// SESSION (biar gak error double session)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// KONEKSI DATABASE (AMAN PATH)
require __DIR__ . '/../config/db.php';

// CEK ID ADA ATAU TIDAK
if (!isset($_GET['id'])) {
    die("ID tidak ditemukan");
}

$id = $_GET['id'];

// AMBIL DATA + JOIN USER
$stmt = $conn->prepare("
    SELECT items.*, users.username 
    FROM items 
    JOIN users ON items.user_id = users.id 
    WHERE items.id = ?
");
$stmt->execute([$id]);
$item = $stmt->fetch();

// CEK DATA ADA ATAU TIDAK
if (!$item) {
    die("Barang tidak ditemukan");
}

// CEK APA INI MILIK SENDIRI
$isOwner = isset($_SESSION['user']) && $_SESSION['user']['id'] == $item['user_id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Barang</title>
</head>
<body>

<h2><?= htmlspecialchars($item['nama_barang']) ?></h2>

<p><b>Pemilik:</b> <?= htmlspecialchars($item['username']) ?></p>

<p><?= htmlspecialchars($item['deskripsi']) ?></p>

<p>Harga: Rp <?= htmlspecialchars($item['harga']) ?> / hari</p>

<br>

<?php if ($isOwner): ?>
    <p><b>Ini barang milik kamu</b></p>

    <a href="index.php?page=edit_barang&id=<?= $item['id'] ?>">Edit Barang</a>

<?php else: ?>
    <a href="index.php?page=sewa&id=<?= $item['id'] ?>">Sewa</a>
<?php endif; ?>

<br><br>

<a href="actions/add_wishlist.php?id=<?= $item['id'] ?>">
    Wishlist
</a>
<br><br>
<a href="index.php">Kembali</a>
<br><br>

<a href="
index.php?page=chat
&id=<?= $item['id'] ?>
&user=<?= $item['user_id'] ?>
">
    Chat Vendor
</a>
</body>
</html>