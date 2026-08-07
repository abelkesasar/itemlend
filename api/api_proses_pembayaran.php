<?php
/**
 * api/api_proses_pembayaran.php
 * Endpoint POST untuk upload bukti pembayaran rental dari mobile app.
 * Wajib login (token, bukan session).
 *
 * Request: multipart/form-data
 * Header : Authorization: Bearer <token>
 * Fields : rental_id, metode (qris/mandiri/bri/bca/gopay/shopee/dana)
 * Files  : bukti (wajib, jpg/jpeg/png/webp, maks 5MB)
 */

require 'api_auth_middleware.php'; // sudah include db.php + validasi token, hasilnya ada di $user

function respond(array $data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$user_id   = (int) $user['id'];
$rental_id = (int) ($_POST['rental_id'] ?? 0);
$metode    = trim($_POST['metode'] ?? '');

$metode_valid = ['qris', 'mandiri', 'bri', 'bca', 'gopay', 'shopee', 'dana'];

if (!$rental_id || !in_array($metode, $metode_valid)) {
    respond(['success' => false, 'message' => 'Data tidak lengkap.'], 400);
}

try {
    // --- Pastikan rental milik user ini ---
    $cek = $conn->prepare("SELECT id FROM rentals WHERE id = ? AND user_id = ?");
    $cek->execute([$rental_id, $user_id]);
    if (!$cek->fetch()) {
        respond(['success' => false, 'message' => 'Akses ditolak.'], 403);
    }

    // --- Validasi upload bukti ---
    if (empty($_FILES['bukti']['name'])) {
        respond(['success' => false, 'message' => 'Bukti pembayaran wajib diupload.'], 400);
    }

    $ext     = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed)) {
        respond(['success' => false, 'message' => 'Format file tidak valid! Gunakan JPG atau PNG.'], 400);
    }

    if ($_FILES['bukti']['size'] > 5 * 1024 * 1024) {
        respond(['success' => false, 'message' => 'Ukuran file maksimal 5MB.'], 400);
    }

    $nama_file = 'bukti_' . $rental_id . '_' . time() . '.' . $ext;
    move_uploaded_file($_FILES['bukti']['tmp_name'], "../uploads/bukti/" . $nama_file);

    // --- Update rental ---
    $stmt = $conn->prepare("
        UPDATE rentals SET
            metode_pembayaran = ?,
            bukti_pembayaran  = ?,
            status_pembayaran = 'menunggu_konfirmasi'
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$metode, $nama_file, $rental_id, $user_id]);

    respond([
        'success' => true,
        'message' => 'Bukti pembayaran berhasil dikirim, menunggu konfirmasi admin.',
        'data' => [
            'rental_id' => $rental_id,
        ],
    ]);

} catch (PDOException $e) {
    respond([
        'success' => false,
        'message' => 'Terjadi kesalahan server.',
        'debug' => $e->getMessage()
    ], 500);
}