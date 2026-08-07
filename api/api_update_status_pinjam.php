<?php
/**
 * api/api_update_status_pinjam.php
 * Endpoint POST untuk update status_pinjam (mulai dipinjam / tandai selesai).
 * Wajib login (token).
 *
 * Header: Authorization: Bearer <token>
 * Fields: rental_id, status_pinjam (sedang_dipinjam | selesai)
 */

require 'api_auth_middleware.php';

function respond(array $data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$owner_id  = (int) $user['id'];
$rental_id = (int) ($_POST['rental_id'] ?? 0);
$status    = $_POST['status_pinjam'] ?? '';

$allowed = ['sedang_dipinjam', 'selesai'];

if (!$rental_id || !in_array($status, $allowed)) {
    respond(['success' => false, 'message' => 'Data tidak valid.'], 400);
}

try {
    // Pastikan rental untuk barang milik owner ini + pembayaran lunas
    $stmt = $conn->prepare("
        SELECT r.id, r.status_pembayaran, r.status_pinjam
        FROM rentals r
        JOIN items i ON r.item_id = i.id
        WHERE r.id = ? AND i.user_id = ?
    ");
    $stmt->execute([$rental_id, $owner_id]);
    $rental = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rental) {
        respond(['success' => false, 'message' => 'Pesanan tidak ditemukan atau bukan milik Anda.'], 404);
    }

    if ($rental['status_pembayaran'] !== 'lunas') {
        respond(['success' => false, 'message' => 'Pembayaran belum dikonfirmasi admin.'], 400);
    }

    // Validasi urutan (tidak bisa mundur)
    $urutan = ['belum_mulai' => 0, 'sedang_dipinjam' => 1, 'selesai' => 2];
    $current_urut = $urutan[$rental['status_pinjam']] ?? 0;
    $new_urut     = $urutan[$status] ?? 0;

    if ($new_urut <= $current_urut) {
        respond(['success' => false, 'message' => 'Status tidak bisa mundur.'], 400);
    }

    // Update
    $conn->prepare("UPDATE rentals SET status_pinjam = ? WHERE id = ?")
         ->execute([$status, $rental_id]);

    respond([
        'success' => true,
        'message' => 'Status berhasil diupdate.',
        'data' => [
            'rental_id' => $rental_id,
            'status_pinjam' => $status,
        ],
    ]);

} catch (PDOException $e) {
    respond([
        'success' => false,
        'message' => 'Terjadi kesalahan server.',
        'debug' => $e->getMessage()
    ], 500);
}