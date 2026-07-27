<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php?page=login");
    exit;
}

$reporter_id = (int) $_SESSION['user'];
$type        = $_POST['type'] ?? '';
$target_id   = (int) ($_POST['target_id'] ?? 0);
$reason      = trim($_POST['reason'] ?? '');
$detail      = trim($_POST['detail'] ?? '');

// 'transaksi' di form kita mapping ke 'peminjaman' sesuai ENUM di DB
if ($type === 'transaksi') $type = 'peminjaman';

$allowed_types = ['barang', 'peminjaman', 'user'];

if (!in_array($type, $allowed_types) || !$target_id || !$reason) {
    echo "<script>alert('Data laporan tidak lengkap!'); history.back();</script>";
    exit;
}

// Validasi target sesuai tipe
if ($type === 'barang') {
    $stmt = $conn->prepare("SELECT id FROM items WHERE id = ?");
    $stmt->execute([$target_id]);
    if (!$stmt->fetch()) {
        echo "<script>alert('Barang tidak ditemukan!'); history.back();</script>";
        exit;
    }
} elseif ($type === 'peminjaman') {
    $stmt = $conn->prepare("SELECT id FROM rentals WHERE id = ? AND user_id = ?");
    $stmt->execute([$target_id, $reporter_id]);
    if (!$stmt->fetch()) {
        echo "<script>alert('Transaksi tidak ditemukan atau bukan milikmu!'); history.back();</script>";
        exit;
    }
}

// Cegah laporan duplikat — status sesuai ENUM: pending/reviewed/dismissed
$cek = $conn->prepare("
    SELECT id FROM reports
    WHERE reporter_id = ? AND type = ? AND target_id = ? AND status = 'pending'
");
$cek->execute([$reporter_id, $type, $target_id]);
if ($cek->fetch()) {
    echo "<script>alert('Kamu sudah melaporkan ini sebelumnya, laporan masih diproses admin.'); history.back();</script>";
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO reports (reporter_id, type, target_id, reason, detail, status, created_at)
    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
");
$stmt->execute([$reporter_id, $type, $target_id, $reason, $detail]);

echo "<script>
    alert('Laporan berhasil dikirim. Tim kami akan meninjau laporanmu.');
    history.back();
</script>";