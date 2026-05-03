<?php
require 'config/db.php';
?>

<h2>Home</h2>

<a href="index.php?page=tambah_barang">+ Tambah Barang</a>
<br><br>

<?php
$result = $conn->query("SELECT * FROM items");

while ($row = $result->fetch_assoc()) {
?>
    <div style="border:1px solid #000; margin:10px; padding:10px;">
        <h3><?= $row['nama_barang'] ?></h3>
        <p><?= $row['deskripsi'] ?></p>
        <p>Rp <?= $row['harga'] ?></p>
        <a href="index.php?page=detail&id=<?= $row['id'] ?>">Detail</a>
    </div>
<?php } ?>