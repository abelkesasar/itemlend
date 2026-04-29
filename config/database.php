<?php
class Database {
    public static function connect() {
        $conn = new mysqli("localhost", "root", "", "itemlendproject");

        if ($conn->connect_error) {
            die("Koneksi gagal: " . $conn->connect_error);
        }

        return $conn;
    }
}