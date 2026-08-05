<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php?page=login");
    exit;
}

$my_id = (int) $_SESSION['user'];
$id    = (int) ($_POST['id'] ?? 0);

if (!$id) {
    echo "<script>alert('ID barang tidak valid'); history.back();</script>";
    exit;
}

// Pastikan barang ini benar-benar milik user yang login
$stmt = $conn->prepare("SELECT * FROM items WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $my_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo "<script>alert('Barang tidak ditemukan atau bukan milikmu'); history.back();</script>";
    exit;
}

// Hapus file foto barang (bisa lebih dari satu, disimpan sebagai JSON array)
if (!empty($item['gambar'])) {
    $list = json_decode($item['gambar'], true);
    if (is_array($list)) {
        foreach ($list as $filename) {
            $path = "../uploads/" . $filename;
            if (file_exists($path)) {
                unlink($path);
            }
        }
    } else {
        $path = "../uploads/" . $item['gambar'];
        if (file_exists($path)) {
            unlink($path);
        }
    }
}

// Hapus barang dari DB — otomatis ikut hapus rentals & chats terkait (ON DELETE CASCADE)
$stmt = $conn->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $my_id]);

header("Location: ../index.php?page=barangsaya");
exit;