<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php?page=login");
    exit;
}

$owner_id    = (int) $_SESSION['user'];
$id          = (int) ($_POST['id']          ?? 0);
$nama_barang = trim($_POST['nama_barang']   ?? '');
$kategori    = trim($_POST['kategori']      ?? '');
$harga       = (int) ($_POST['harga']       ?? 0);
$stok        = (int) ($_POST['stok']        ?? 0);
$lokasi      = trim($_POST['lokasi']        ?? '');
$deskripsi   = trim($_POST['deskripsi']     ?? '');

if (!$id || !$nama_barang || !$harga) {
    echo "<script>alert('Data tidak lengkap!'); history.back();</script>";
    exit;
}

// Pastikan barang ini milik owner yang login
$cek = $conn->prepare("SELECT id, gambar FROM items WHERE id = ? AND user_id = ?");
$cek->execute([$id, $owner_id]);
$existing = $cek->fetch(PDO::FETCH_ASSOC);

if (!$existing) {
    echo "<script>alert('Akses ditolak!'); history.back();</script>";
    exit;
}

// Handle upload foto baru
$gambar = $existing['gambar']; // default: gambar lama
if (!empty($_FILES['gambar']['name'])) {
    $ext        = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
    $nama_file  = 'item_' . $id . '_' . time() . '.' . $ext;
    $target     = '../uploads/' . $nama_file;

    if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target)) {
        // Hapus gambar lama
        if ($gambar && file_exists('../uploads/' . $gambar)) {
            @unlink('../uploads/' . $gambar);
        }
        $gambar = $nama_file;
    }
}

// Update DB
$stmt = $conn->prepare("
    UPDATE items SET
        nama_barang = ?,
        kategori    = ?,
        harga       = ?,
        stok        = ?,
        lokasi      = ?,
        deskripsi   = ?,
        gambar      = ?,
        status      = 'pending'
    WHERE id = ? AND user_id = ?
");
$stmt->execute([
    $nama_barang, $kategori, $harga, $stok,
    $lokasi, $deskripsi, $gambar,
    $id, $owner_id
]);

echo "
<script>
    alert('Barang berhasil diupdate! Menunggu approval admin.');
    window.location='../index.php?page=barangsaya&tab=barang';
</script>
";
?>