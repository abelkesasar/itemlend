<?php
require_once '../config/database.php';

class BarangController {

    public function index() {
        $conn = Database::connect();
        $result = $conn->query("SELECT * FROM barang");

        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    }

    public function store() {
        $conn = Database::connect();

        $nama = $_POST['nama'] ?? null;
        $deskripsi = $_POST['deskripsi'] ?? null;

        if (!$nama || !$deskripsi) {
            echo json_encode(["message" => "data kosong"]);
            return;
        }

        $stmt = $conn->prepare("INSERT INTO barang (nama, deskripsi) VALUES (?, ?)");
        $stmt->bind_param("ss", $nama, $deskripsi);
        $stmt->execute();

        echo json_encode(["message" => "Barang ditambahkan"]);
    }
}