<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php?page=login");
    exit;
}

$owner_id  = (int) $_SESSION['user'];
$rental_id = (int) ($_POST['rental_id'] ?? 0);
$status    = $_POST['status_pinjam'] ?? '';

$allowed = ['belum_mulai', 'sedang_dipinjam', 'selesai'];

if (!$rental_id || !in_array($status, $allowed)) {
    echo "<script>alert('Data tidak valid!'); history.back();</script>";
    exit;
}

// Pastikan rental ini memang untuk barang milik owner yang login
// DAN pembayaran sudah lunas
$stmt = $conn->prepare("
    SELECT r.id, r.status_pembayaran, r.status_pinjam
    FROM rentals r
    JOIN items i ON r.item_id = i.id
    WHERE r.id = ? AND i.user_id = ?
");
$stmt->execute([$rental_id, $owner_id]);
$rental = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rental) {
    echo "<script>alert('Akses ditolak!'); history.back();</script>";
    exit;
}

if ($rental['status_pembayaran'] !== 'lunas') {
    echo "<script>alert('Pembayaran belum dikonfirmasi admin!'); history.back();</script>";
    exit;
}

// Validasi urutan status (tidak bisa balik ke belakang)
$urutan = ['belum_mulai' => 0, 'sedang_dipinjam' => 1, 'selesai' => 2];
$current_urut = $urutan[$rental['status_pinjam']] ?? 0;
$new_urut     = $urutan[$status] ?? 0;

if ($new_urut <= $current_urut) {
    echo "<script>alert('Status tidak bisa mundur!'); history.back();</script>";
    exit;
}

// Update
$conn->prepare("UPDATE rentals SET status_pinjam = ? WHERE id = ?")
     ->execute([$status, $rental_id]);

echo "<script>
    alert('Status berhasil diupdate!');
    window.location='../index.php?page=barangsaya&tab=pesanan';
</script>";
?>