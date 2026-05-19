<?php
session_start();
require 'config/db.php';

$user_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
SELECT items.*
FROM wishlist
JOIN items ON wishlist.item_id = items.id
WHERE wishlist.user_id=?
");

$stmt->execute([$user_id]);

$data = $stmt->fetchAll();
?>

<h1>Wishlist</h1>

<?php foreach($data as $item): ?>

<div>

    <h3><?= $item['nama_barang'] ?></h3>

    <p>Rp <?= $item['harga'] ?></p>

    <a href="index.php?page=detail&id=<?= $item['id'] ?>">
        Detail
    </a>

</div>

<hr>

<?php endforeach; ?>