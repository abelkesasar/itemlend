<?php
/**
 * api/api_admin_user_action.php
 * POST — Approve atau reject user.
 * Fields: id, action (approved|rejected)
 */
require 'api_admin_auth_middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Support both JSON and form-data
$id = (int) ($input['id'] ?? $_POST['id'] ?? 0);
$action = $input['action'] ?? $_POST['action'] ?? '';

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID user tidak valid.']);
    exit;
}

if (!in_array($action, ['approved', 'rejected'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action harus "approved" atau "rejected".']);
    exit;
}

try {
    // Cek user exists & status pending
    $stmt = $conn->prepare("SELECT id, status FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan.']);
        exit;
    }

    if ($action === 'approved') {
        $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'User berhasil di-approve.']);
    } else {
        // Reject = hapus user
        $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'User berhasil ditolak dan dihapus.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan server.', 'debug' => $e->getMessage()]);
}
