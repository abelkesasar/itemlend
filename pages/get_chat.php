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
$receiver_id = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;

if (!$receiver_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'receiver_id wajib diisi']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT id, sender_id, receiver_id, item_id, pesan, type, created_at FROM chats
     WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
     ORDER BY created_at ASC, id ASC"
);
$stmt->execute([$my_id, $receiver_id, $receiver_id, $my_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($messages as &$m) {
    $m['pesan'] = htmlspecialchars($m['pesan']);
}

echo json_encode(['success' => true, 'data' => $messages]);