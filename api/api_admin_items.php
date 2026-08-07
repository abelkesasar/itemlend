<?php
/**
 * api/api_admin_items.php
 * GET — List semua items untuk admin.
 * Query: ?status=approved|cooldown (default: approved), ?search=..., ?sort=terbaru|az|termurah|termahal
 */
require 'api_admin_auth_middleware.php';

$status = $_GET['status'] ?? 'approved';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'terbaru';

if (!in_array($status, ['approved', 'cooldown'])) {
    $status = 'approved';
}

$where = "WHERE i.status = :status";
$params = [':status' => $status];

if ($search !== '') {
    $where .= " AND (i.nama_barang LIKE :search OR u.username LIKE :search)";
    $params[':search'] = "%$search%";
}

$order = match($sort) {
    'termurah' => 'i.harga ASC',
    'termahal' => 'i.harga DESC',
    'az' => 'i.nama_barang ASC',
    default => 'i.created_at DESC',
};

try {
    $sql = "
        SELECT i.id, i.nama_barang, i.deskripsi, i.harga, i.stok, i.gambar,
               i.status, i.lokasi, i.kategori, i.banned_until, i.created_at,
               u.username AS owner_name
        FROM items i
        LEFT JOIN users u ON i.user_id = u.id
        $where
        ORDER BY $order
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Counts
    $approved_count = (int) $conn->query("SELECT COUNT(*) FROM items WHERE status = 'approved'")->fetchColumn();
    $cooldown_count = (int) $conn->query("SELECT COUNT(*) FROM items WHERE status = 'cooldown'")->fetchColumn();
    $pending_count = (int) $conn->query("SELECT COUNT(*) FROM items WHERE status = 'pending'")->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => [
            'items' => $items,
            'stats' => [
                'approved' => $approved_count,
                'cooldown' => $cooldown_count,
                'pending' => $pending_count,
            ],
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan server.', 'debug' => $e->getMessage()]);
}
