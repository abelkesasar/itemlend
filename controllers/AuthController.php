<?php
require_once '../config/database.php';

class AuthController {

    public function register() {
        $conn = Database::connect();

        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!$username || !$password) {
            echo json_encode(["message" => "username/password kosong"]);
            return;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hash);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Register berhasil"]);
        } else {
            echo json_encode(["message" => "Register gagal"]);
        }
    }

    public function login() {
        $conn = Database::connect();

        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!$username || !$password) {
            echo json_encode(["message" => "username/password kosong"]);
            return;
        }

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];

            echo json_encode([
                "message" => "Login berhasil",
                "user" => $user
            ]);
        } else {
            echo json_encode(["message" => "Login gagal"]);
        }
    }
}