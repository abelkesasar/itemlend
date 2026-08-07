<?php
/**
 * api/api_barang_saya.php
 * Endpoint GET untuk mengambil daftar barang milik user yang login.
 * Wajib login (token).
 *
 * Header: Authorization: Bearer <token>
 * Response: { success, data: { items: [...], stats: {...} } }
 */

require 'api_auth_middleware.php';

function respond(array $data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$owner_id = (int) $user['id'];

try {
    // Ambil semua barang milik user ini
    $stmt = $conn->prepare("
        SELECT
            i.*,
            COUNT(r.id) AS total_pesanan,
            SUM(CASE WHEN r.status_pembayaran = 'lunas' THEN 1 ELSE 0 END) AS pesanan_lunas,
            COALESCE(SUM(CASE WHEN r.status_pembayaran = 'lunas' THEN r.total_harga ELSE 0 END), 0) AS total_pendapatan
        FROM items i
        LEFT JOIN rentals r ON r.item_id = i.id
        WHERE i.user_id = ?
        GROUP BY i.id
        ORDER BY i.created_at DESC
    ");
    $stmt->execute([$owner_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format gambar + cast tipe
    foreach ($items as &$item) {
        $images = json_decode($item['gambar'], true);
        $item['gambar_url'] = null;
        $item['gambar_list'] = [];
        if (!empty($images)) {
            $item['gambar_list'] = $images;
            $item['gambar_url'] = 'http://10.0.2.2/itemlend/uploads/' . $images[0];
        }
        $item['id'] = (int) $item['id'];
        $item['harga'] = (int) $item['harga'];
        $item['stok'] = (int) $item['stok'];
        $item['total_pesanan'] = (int) ($item['total_pesanan'] ?? 0);
        $item['pesanan_lunas'] = (int) ($item['pesanan_lunas'] ?? 0);
        $item['total_pendapatan'] = (int) ($item['total_pendapatan'] ?? 0);
    }
    unset($item);

    // Statistik
    $total = count($items);
    $approved = 0;
    $pending = 0;
    $rejected = 0;
    $total_pendapatan = 0;

    foreach ($items as $item) {
        if ($item['status'] === 'approved') $approved++;
        elseif ($item['status'] === 'pending') $pending++;
        elseif ($item['status'] === 'rejected') $rejected++;
        $total_pendapatan += $item['total_pendapatan'];
    }

    respond([
        'success' => true,
        'data' => [
            'items' => $items,
            'stats' => [
                'total' => $total,
                'approved' => $approved,
                'pending' => $pending,
                'rejected' => $rejected,
                'total_pendapatan' => $total_pendapatan,
            ],
        ],
    ]);

} catch (PDOException $e) {
    respond([
        'success' => false,
        'message' => 'Terjadi kesalahan server.',
        'debug' => $e->getMessage()
    ], 500);
}