<?php

require_once '../controllers/AuthController.php';
require_once '../controllers/BarangController.php';

$url = $_GET['url'] ?? '';

switch ($url) {

    case 'test':
        echo json_encode(["message" => "API jalan"]);
        break;

    case 'register':
        (new AuthController)->register();
        break;

    case 'login':
        (new AuthController)->login();
        break;

    case 'me':
        echo json_encode([
            "user_id" => $_SESSION['user_id'] ?? null
        ]);
        break;

    case 'barang':
        (new BarangController)->index();
        break;

    case 'barang-store':
        (new BarangController)->store();
        break;

    default:
        echo json_encode(["message" => "Route tidak ditemukan"]);
}