<?php
/**
 * api/api_admin_item_action.php
 * POST — Approve atau reject item.
 * Fields: id, action (approved|rejected)
 * Juga handle approval items pending.
 */
require 'api_admin_auth_middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = (int) ($input['id'] ?? $_POST['id'] ?? 0);
$action = $input['action'] ?? $_POST['action'] ?? '';

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID item tidak valid.']);
    exit;
}

if (!in_array($action, ['approved', 'rejected'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action harus "approved" atau "rejected".']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id, status FROM items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan.']);
        exit;
    }

    // Update status item
    $conn->prepare("UPDATE items SET status = ? WHERE id = ?")->execute([$action, $id]);

    $msg = $action === 'approved' ? 'Barang berhasil di-approve.' : 'Barang berhasil ditolak.';
    echo json_encode(['success' => true, 'message' => $msg]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan server.', 'debug' => $e->getMessage()]);
}
