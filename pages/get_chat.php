<?php
session_start();
require 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

$my_id       = $_SESSION['user'];
$item_id     = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
$receiver_id = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;

if (!$item_id || !$receiver_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'item_id dan receiver_id wajib diisi']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT id, sender_id, receiver_id, pesan, created_at FROM chats
     WHERE item_id = ?
       AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
     ORDER BY created_at ASC"
);
$stmt->execute([$item_id, $my_id, $receiver_id, $receiver_id, $my_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($messages as &$m) {
    $m['pesan'] = htmlspecialchars($m['pesan']);
}

echo json_encode(['success' => true, 'data' => $messages]);