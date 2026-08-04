<?php
/**
 * api/api_items.php
 * Endpoint GET untuk menampilkan daftar barang yang sudah di-approve admin,
 * lengkap dengan nama pemilik barang dan statistik ringkas untuk halaman Home.
 *
 * Contoh pemanggilan: GET http://10.0.2.2/itemlend/api/api_items.php
 */

header('Content-Type: application/json');
require '../config/db.php';

// Ganti sesuai lokasi folder upload gambar barang di server kamu
$uploadBaseUrl = 'http://10.0.2.2/itemlend/uploads/';

function respond(array $data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT items.id, items.user_id, items.nama_barang, items.kategori,
               items.deskripsi, items.harga, items.stok, items.status,
               items.created_at, items.lokasi, items.gambar, items.foto,
               users.username AS owner_username
        FROM items
        JOIN users ON items.user_id = users.id
        WHERE items.status = 'approved'
        ORDER BY items.created_at DESC
    ");
    $stmt->execute();
    $items = $stmt->fetchAll();

    $result = [];

    foreach ($items as $item) {
        // Kolom 'gambar' formatnya nggak konsisten: kadang JSON array, kadang string biasa
        $imageFile = null;

        if (!empty($item['gambar'])) {
            $decoded = json_decode($item['gambar'], true);
            if (is_array($decoded) && count($decoded) > 0) {
                $imageFile = $decoded[0];
            } else {
                $imageFile = $item['gambar'];
            }
        } elseif (!empty($item['foto'])) {
            $imageFile = $item['foto'];
        }

        $result[] = [
            'id'             => (int) $item['id'],
            'user_id'        => (int) $item['user_id'],
            'owner_username' => $item['owner_username'],
            'nama_barang'    => $item['nama_barang'],
            'kategori'       => $item['kategori'],
            'deskripsi'      => $item['deskripsi'],
            'harga'          => (int) $item['harga'],
            'stok'           => (int) $item['stok'],
            'lokasi'         => $item['lokasi'],
            'created_at'     => $item['created_at'],
            'gambar_url'     => $imageFile ? $uploadBaseUrl . $imageFile : null,
        ];
    }

    // --- Statistik ringkas untuk hero section di Home ---
    $totalBarang = $conn->query("SELECT COUNT(*) FROM items WHERE status = 'approved'")->fetchColumn();
    $totalPengguna = $conn->query("SELECT COUNT(*) FROM users WHERE status = 'approved'")->fetchColumn();
    $totalTransaksi = $conn->query("SELECT COUNT(*) FROM rentals")->fetchColumn();

    respond([
        'success' => true,
        'data' => $result,
        'stats' => [
            'total_barang'    => (int) $totalBarang,
            'total_pengguna'  => (int) $totalPengguna,
            'total_transaksi' => (int) $totalTransaksi,
        ],
    ]);

} catch (PDOException $e) {
    respond([
        'success' => false,
        'message' => 'Terjadi kesalahan server.',
        'debug' => $e->getMessage()
    ], 500);
}