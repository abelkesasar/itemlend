<?php
/**
 * api/api_admin_rental_action.php
 * POST — Konfirmasi atau tolak pembayaran rental.
 * Fields: rental_id, aksi (konfirmasi_bayar|tolak_bayar), catatan (opsional untuk tolak)
 */
require 'api_admin_auth_middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$rental_id = (int) ($input['rental_id'] ?? $_POST['rental_id'] ?? 0);
$aksi = $input['aksi'] ?? $_POST['aksi'] ?? '';
$catatan = trim($input['catatan'] ?? $_POST['catatan'] ?? '');

if ($rental_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID rental tidak valid.']);
    exit;
}

try {
    // Cek rental exists
    $stmt = $conn->prepare("SELECT id, status_pembayaran FROM rentals WHERE id = ?");
    $stmt->execute([$rental_id]);
    $rental = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rental) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Rental tidak ditemukan.']);
        exit;
    }

    if ($rental['status_pembayaran'] !== 'menunggu_konfirmasi') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Rental ini tidak dalam status menunggu konfirmasi.']);
        exit;
    }

    if ($aksi === 'konfirmasi_bayar') {
        $conn->prepare("
            UPDATE rentals SET status_pembayaran = 'lunas', paid_at = NOW()
            WHERE id = ? AND status_pembayaran = 'menunggu_konfirmasi'
        ")->execute([$rental_id]);
        echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil dikonfirmasi.']);

    } elseif ($aksi === 'tolak_bayar') {
        $conn->prepare("
            UPDATE rentals SET status_pembayaran = 'ditolak', catatan_admin = ?
            WHERE id = ? AND status_pembayaran = 'menunggu_konfirmasi'
        ")->execute([$catatan, $rental_id]);
        echo json_encode(['success' => true, 'message' => 'Pembayaran ditolak.']);

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid. Gunakan "konfirmasi_bayar" atau "tolak_bayar".']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan server.', 'debug' => $e->getMessage()]);
}
