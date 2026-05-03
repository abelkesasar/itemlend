<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user'])) {
    echo "Harus login!";
    exit;
}

$id = $_GET['id'];
?>

<h2>Sewa Barang</h2>

<form action="actions/sewa.php" method="POST">
    <input type="hidden" name="item_id" value="<?= $id ?>">

    Tanggal Mulai: <br>
    <input type="date" name="start" required><br><br>

    Tanggal Selesai: <br>
    <input type="date" name="end" required><br><br>

    <button type="submit">Sewa</button>
</form>