<?php
/**
 * api/api_detail_barang.php
 * Endpoint GET untuk menampilkan detail 1 barang, lengkap dengan info pemilik
 * dan daftar barang lain dari pemilik yang sama.
 *
 * Tidak wajib login (siapa saja boleh lihat detail barang, sama seperti web).
 * Untuk menentukan apakah barang ini milik user yang sedang login atau bukan,
 * biarkan Flutter yang membandingkan `owner.id` dengan user_id yang tersimpan
 * di local storage — tidak perlu session di sisi API.
 *
 * Contoh pemanggilan: GET http://10.0.2.2/itemlend/api/api_detail_barang.php?id=38
 */

header('Content-Type: application/json');
require '../config/db.php';

// Ganti sesuai lokasi folder upload gambar di server kamu
$uploadBaseUrl = 'http://10.0.2.2/itemlend/uploads/';

function respond(array $data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function resolveGambarUrl($gambarRaw, $uploadBaseUrl) {
    if (empty($gambarRaw)) return null;
    $decoded = json_decode($gambarRaw, true);
    if (is_array($decoded) && !empty($decoded[0])) {
        return $uploadBaseUrl . $decoded[0];
    }
    if (!is_array($decoded) && $gambarRaw) {
        return $uploadBaseUrl . $gambarRaw;
    }
    return null;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    respond(['success' => false, 'message' => 'ID barang tidak valid.'], 400);
}

try {
    $stmt = $conn->prepare("
        SELECT items.*, users.id AS owner_id, users.username, users.nomor_wa, users.foto_profil
        FROM items
        JOIN users ON items.user_id = users.id
        WHERE items.id = ?
    ");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if (!$item) {
        respond(['success' => false, 'message' => 'Barang tidak ditemukan.'], 404);
    }

    // --- Barang lain dari pemilik yang sama ---
    $stmt2 = $conn->prepare("
        SELECT id, nama_barang, harga, gambar
        FROM items
        WHERE user_id = ? AND id != ? AND status = 'approved'
        ORDER BY created_at DESC LIMIT 3
    ");
    $stmt2->execute([$item['owner_id'], $id]);
    $otherRaw = $stmt2->fetchAll();

    $otherItems = array_map(function ($o) use ($uploadBaseUrl) {
        return [
            'id'          => (int) $o['id'],
            'nama_barang' => $o['nama_barang'],
            'harga'       => (int) $o['harga'],
            'gambar_url'  => resolveGambarUrl($o['gambar'], $uploadBaseUrl),
        ];
    }, $otherRaw);

    respond([
        'success' => true,
        'data' => [
            'id'          => (int) $item['id'],
            'nama_barang' => $item['nama_barang'],
            'kategori'    => $item['kategori'],
            'deskripsi'   => $item['deskripsi'],
            'harga'       => (int) $item['harga'],
            'stok'        => (int) $item['stok'],
            'lokasi'      => $item['lokasi'],
            'status'      => $item['status'],
            'created_at'  => $item['created_at'],
            'gambar_url'  => resolveGambarUrl($item['gambar'], $uploadBaseUrl),
            'owner' => [
                'id'              => (int) $item['owner_id'],
                'username'        => $item['username'],
                'nomor_wa'        => $item['nomor_wa'],
                'foto_profil_url' => $item['foto_profil'] ? $uploadBaseUrl . $item['foto_profil'] : null,
            ],
            'other_items' => $otherItems,
        ],
    ]);

} catch (PDOException $e) {
    respond([
        'success' => false,
        'message' => 'Terjadi kesalahan server.',
        'debug' => $e->getMessage()
    ], 500);
}