<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php?page=login");
    exit;
}

$reporter_id = (int) $_SESSION['user'];
$target_id   = (int) ($_POST['target_id'] ?? 0);
$reason      = trim($_POST['reason'] ?? '');
$detail      = trim($_POST['detail'] ?? '');

if (!$target_id || $reason === '') {
    echo "<script>alert('Data laporan tidak lengkap!'); history.back();</script>";
    exit;
}

// target_id selalu id peminjaman (rentals.id)
$stmt = $conn->prepare("
    SELECT r.id, r.user_id AS penyewa_id, i.user_id AS pemilik_id
    FROM rentals r
    JOIN items i ON r.item_id = i.id
    WHERE r.id = ?
");
$stmt->execute([$target_id]);
$rental = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rental) {
    echo "<script>alert('Pesanan tidak ditemukan!'); history.back();</script>";
    exit;
}

// Reporter harus penyewa ATAU pemilik dari pesanan ini
$is_penyewa = $reporter_id === (int) $rental['penyewa_id'];
$is_pemilik = $reporter_id === (int) $rental['pemilik_id'];

if (!$is_penyewa && !$is_pemilik) {
    echo "<script>alert('Kamu tidak berhak melaporkan pesanan ini!'); history.back();</script>";
    exit;
}

// Cegah laporan duplikat yang masih pending dari orang yang sama untuk pesanan yang sama
$cek = $conn->prepare("
    SELECT id FROM reports
    WHERE reporter_id = ? AND target_id = ? AND status = 'pending'
");
$cek->execute([$reporter_id, $target_id]);
if ($cek->fetch()) {
    echo "<script>alert('Kamu sudah melaporkan pesanan ini sebelumnya, laporan masih diproses admin.'); history.back();</script>";
    exit;
}

// Upload bukti (opsional)
$bukti = null;
if (!empty($_FILES['bukti']['name']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
    if (in_array($ext, $allowed_ext)) {
        $filename    = 'report_' . $reporter_id . '_' . time() . '.' . $ext;
        $target_path = '../uploads/' . $filename;
        if (move_uploaded_file($_FILES['bukti']['tmp_name'], $target_path)) {
            $bukti = $filename;
        }
    }
}

$stmt = $conn->prepare("
    INSERT INTO reports (reporter_id, target_id, reason, detail, bukti, status, created_at)
    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
");
$stmt->execute([$reporter_id, $target_id, $reason, $detail, $bukti]);

echo "<script>
    alert('Laporan berhasil dikirim. Tim kami akan meninjau laporanmu.');
    history.back();
</script>";