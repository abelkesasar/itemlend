<?php
session_start();
require 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Kamu harus login dulu']);
    exit;
}

$sender_id   = $_SESSION['user'];
$receiver_id = $_POST['receiver_id'] ?? null;
$item_id     = $_POST['item_id'] ?? null;
$pesan       = trim($_POST['pesan'] ?? '');

if (!$receiver_id || !$item_id || $pesan === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'receiver_id, item_id, dan pesan wajib diisi']);
    exit;
}

if ((int)$sender_id === (int)$receiver_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tidak bisa mengirim pesan ke diri sendiri']);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO chats (sender_id, receiver_id, item_id, pesan, created_at) VALUES (?, ?, ?, ?, NOW())"
);
$ok = $stmt->execute([$sender_id, $receiver_id, $item_id, $pesan]);

if ($ok) {
    echo json_encode([
        'success' => true,
        'data' => [
            'id'          => $conn->lastInsertId(),
            'sender_id'   => $sender_id,
            'receiver_id' => $receiver_id,
            'item_id'     => $item_id,
            'pesan'       => htmlspecialchars($pesan),
            'created_at'  => date('Y-m-d H:i:s'),
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengirim pesan']);
}