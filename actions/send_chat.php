<?php
session_start();
require '../config/db.php';

$sender_id = $_SESSION['user']['id'];

$receiver_id = $_POST['receiver_id'];

$item_id = $_POST['item_id'];

$pesan = $_POST['pesan'];

$stmt = $conn->prepare("
INSERT INTO chats
(sender_id, receiver_id, item_id, pesan)
VALUES (?, ?, ?, ?)
");

$stmt->execute([
    $sender_id,
    $receiver_id,
    $item_id,
    $pesan
]);

header("Location: ../index.php?page=chat&id=$item_id&user=$receiver_id");
exit;
?>