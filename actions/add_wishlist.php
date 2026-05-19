<?php
session_start();
require '../config/db.php';

$user_id = $_SESSION['user']['id'];
$item_id = $_GET['id'];

$stmt = $conn->prepare("
INSERT INTO wishlist (user_id, item_id)
VALUES (?, ?)
");

$stmt->execute([$user_id, $item_id]);

header("Location: ../index.php?page=detail&id=$item_id");
exit;
?>