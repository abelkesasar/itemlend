<?php
/**
 * api/api_sewa.php
 * Endpoint POST untuk membuat rental/booking barang dari mobile app.
 * Wajib login (token, bukan session).
 *
 * Header: Authorization: Bearer <token>
 * Fields: item_id, start (YYYY-MM-DD), end (YYYY-MM-DD)
 */

require 'api_auth_middleware.php'; // sudah include db.php + validasi token, hasilnya ada di $user

function respond(array $data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$user_id = (int) $user['id'];
$item_id = (int) ($_POST['item_id'] ?? 0);
$start   = $_POST['start'] ?? '';
$end     = $_POST['end']   ?? '';

// --- Validasi input dasar ---
if (!$item_id || !$start || !$end) {
    respond(['success' => false, 'message' => 'Data tidak lengkap.'], 400);
}

if ($start < date('Y-m-d')) {
    respond(['success' => false, 'message' => 'Tanggal mulai tidak boleh di masa lalu.'], 400);
}

if ($end <= $start) {
    respond(['success' => false, 'message' => 'Tanggal selesai harus setelah tanggal mulai.'], 400);
}

try {
    // --- Ambil data barang ---
    $stmt = $conn->prepare("SELECT user_id, harga, status FROM items WHERE id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();

    if (!$item) {
        respond(['success' => false, 'message' => 'Barang tidak ditemukan.'], 404);
    }

    // Tambahan dari saya: barang yang tidak/belum approved (pending/rejected/cooldown) tidak boleh disewa
    if ($item['status'] !== 'approved') {
        respond(['success' => false, 'message' => 'Barang ini sedang tidak tersedia untuk disewa.'], 403);
    }

    // Tidak bisa menyewa barang milik sendiri
    if ($item['user_id'] == $user_id) {
        respond(['success' => false, 'message' => 'Tidak bisa menyewa barang milik sendiri.'], 403);
    }

    // --- Hitung total ---
    $durasi = (int) ((strtotime($end) - strtotime($start)) / 86400);
    $total  = $durasi * $item['harga'];

    // --- Insert rental ---
    $stmt = $conn->prepare("
        INSERT INTO rentals
            (user_id, item_id, tanggal_mulai, tanggal_selesai, total_harga, status_pembayaran)
        VALUES
            (?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$user_id, $item_id, $start, $end, $total]);

    $rental_id = $conn->lastInsertId();

    respond([
        'success' => true,
        'message' => 'Rental berhasil dibuat, lanjut ke pembayaran.',
        'data' => [
            'rental_id'   => (int) $rental_id,
            'item_id'     => $item_id,
            'durasi_hari' => $durasi,
            'total_harga' => $total,
        ],
    ]);

} catch (PDOException $e) {
    respond([
        'success' => false,
        'message' => 'Terjadi kesalahan server.',
        'debug' => $e->getMessage()
    ], 500);
}