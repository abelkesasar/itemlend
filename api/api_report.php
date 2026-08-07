<?php
/**
 * api/api_report.php
 * Endpoint POST untuk melaporkan penyewa/pemilik dari mobile app.
 * Wajib login (token).
 *
 * Header: Authorization: Bearer <token>
 * Fields: target_id (rental_id), reason, detail
 * Files:  bukti (opsional)
 */

require 'api_auth_middleware.php';

function respond(array $data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$reporter_id = (int) $user['id'];
$target_id   = (int) ($_POST['target_id'] ?? 0);
$reason      = trim($_POST['reason'] ?? '');
$detail      = trim($_POST['detail'] ?? '');

if (!$target_id || $reason === '') {
    respond(['success' => false, 'message' => 'Data laporan tidak lengkap.'], 400);
}

try {
    // Cek rental
    $stmt = $conn->prepare("
        SELECT r.id, r.user_id AS penyewa_id, i.user_id AS pemilik_id
        FROM rentals r
        JOIN items i ON r.item_id = i.id
        WHERE r.id = ?
    ");
    $stmt->execute([$target_id]);
    $rental = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rental) {
        respond(['success' => false, 'message' => 'Pesanan tidak ditemukan.'], 404);
    }

    // Reporter harus penyewa atau pemilik
    $is_penyewa = $reporter_id === (int) $rental['penyewa_id'];
    $is_pemilik = $reporter_id === (int) $rental['pemilik_id'];

    if (!$is_penyewa && !$is_pemilik) {
        respond(['success' => false, 'message' => 'Kamu tidak berhak melaporkan pesanan ini.'], 403);
    }

    // Cek laporan duplikat
    $cek = $conn->prepare("SELECT id FROM reports WHERE reporter_id = ? AND target_id = ? AND status = 'pending'");
    $cek->execute([$reporter_id, $target_id]);
    if ($cek->fetch()) {
        respond(['success' => false, 'message' => 'Kamu sudah melaporkan pesanan ini sebelumnya.'], 400);
    }

    // Upload bukti (opsional)
    $bukti = null;
    if (!empty($_FILES['bukti']['name']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $filename = 'report_' . $reporter_id . '_' . time() . '.' . $ext;
            $target_path = "../uploads/" . $filename;
            if (move_uploaded_file($_FILES['bukti']['tmp_name'], $target_path)) {
                $bukti = $filename;
            }
        }
    }

    // Insert laporan
    $stmt = $conn->prepare("
        INSERT INTO reports (reporter_id, target_id, reason, detail, bukti, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$reporter_id, $target_id, $reason, $detail, $bukti]);

    respond([
        'success' => true,
        'message' => 'Laporan berhasil dikirim. Tim kami akan meninjau laporanmu.',
    ]);

} catch (PDOException $e) {
    respond([
        'success' => false,
        'message' => 'Terjadi kesalahan server.',
        'debug' => $e->getMessage()
    ], 500);
}