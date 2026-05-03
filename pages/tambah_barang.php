<?php
session_start();
?>

<h2>Tambah Barang</h2>

<form action="actions/tambah_barang.php" method="POST">
    Nama Barang: <br>
    <input type="text" name="nama_barang" required><br><br>

    Deskripsi: <br>
    <textarea name="deskripsi"></textarea><br><br>

    Harga: <br>
    <input type="number" name="harga" required><br><br>

    <button type="submit">Simpan</button>
</form>