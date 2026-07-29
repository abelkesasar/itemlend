<?php
require '../config/db.php';

header('Content-Type: application/json');

$stmt = $conn->query("SELECT * FROM items");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "data" => $items
]);