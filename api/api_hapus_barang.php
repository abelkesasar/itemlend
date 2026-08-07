<?php
/**
 * api/api_hapus_barang.php
 * Endpoint POST untuk menghapus barang milik sendiri (hanya status pending).
 * Wajib login (token).
 *
 * Header: Authorization: Bearer <token>
 * Fields: item_id
 */

require 'api_auth_middleware.php';

function respond(array $data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$user_id = (int) $user['id'];
$item_id = (int) ($_POST['item_id'] ?? 0);

if (!$item_id) {
    respond(['success' => false, 'message' => 'Item ID tidak valid.'], 400);
}

try {
    // Pastikan barang milik user ini
    $stmt = $conn->prepare("SELECT id, status, gambar FROM items WHERE id = ? AND user_id = ?");
    $stmt->execute([$item_id, $user_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        respond(['success' => false, 'message' => 'Barang tidak ditemukan atau bukan milik Anda.'], 404);
    }

    // Hanya bisa hapus barang yang masih pending
    if ($item['status'] !== 'pending') {
        respond(['success' => false, 'message' => 'Hanya barang berstatus Menunggu yang bisa dihapus.'], 400);
    }

    // Hapus file gambar
    $images = json_decode($item['gambar'], true);
    if (!empty($images)) {
        foreach ($images as $img) {
            $path = "../uploads/" . $img;
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    // Hapus dari DB
    $conn->prepare("DELETE FROM items WHERE id = ? AND user_id = ?")->execute([$item_id, $user_id]);

    respond([
        'success' => true,
        'message' => 'Barang berhasil dihapus.',
    ]);

} catch (PDOException $e) {
    respond([
        'success' => false,
        'message' => 'Terjadi kesalahan server.',
        'debug' => $e->getMessage()
    ], 500);
}