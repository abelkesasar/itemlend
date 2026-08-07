<?php
/**
 * api/api_admin_rentals.php
 * GET — List semua rental untuk admin.
 * Query: ?tab=semua|pending|menunggu_konfirmasi|lunas|ditolak|sedang_dipinjam|selesai, ?search=..., ?sort=...
 */
require 'api_admin_auth_middleware.php';

$tab = $_GET['tab'] ?? 'semua';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'terbaru';

$where = ["1=1"];
$params = [];

if ($search !== '') {
    $where[] = "(i.nama_barang LIKE :q OR u.username LIKE :q OR pu.username LIKE :q)";
    $params[':q'] = "%$search%";
}

$tab_where = match($tab) {
    'menunggu_konfirmasi' => "r.status_pembayaran = 'menunggu_konfirmasi'",
    'lunas' => "r.status_pembayaran = 'lunas'",
    'ditolak' => "r.status_pembayaran = 'ditolak'",
    'sedang_dipinjam' => "r.status_pinjam = 'sedang_dipinjam'",
    'selesai' => "r.status_pinjam = 'selesai'",
    'pending' => "r.status_pembayaran = 'pending'",
    default => "1=1",
};
$where[] = $tab_where;

$order = match($sort) {
    'terlama' => 'r.created_at ASC',
    'terbesar' => 'r.total_harga DESC',
    'terkecil' => 'r.total_harga ASC',
    default => 'r.created_at DESC',
};

try {
    $sql = "
        SELECT r.id, r.user_id, r.item_id, r.tanggal_mulai, r.tanggal_selesai,
               r.total_harga, r.status_pembayaran, r.status_pinjam, r.status_refund,
               r.bukti_pembayaran, r.paid_at, r.catatan_admin, r.metode_pembayaran,
               r.created_at,
               i.nama_barang, i.harga, i.gambar, i.lokasi,
               u.username AS penyewa,
               pu.username AS pemilik
        FROM rentals r
        JOIN items i ON r.item_id = i.id
        JOIN users u ON r.user_id = u.id
        JOIN users pu ON i.user_id = pu.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $order
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tab counts
    function countTab(PDO $conn, string $cond): int {
        return (int) $conn->query("SELECT COUNT(*) FROM rentals r JOIN items i ON r.item_id = i.id WHERE $cond")->fetchColumn();
    }
    $total_rentals = (int) $conn->query("SELECT COUNT(*) FROM rentals")->fetchColumn();
    $menunggu = (int) $conn->query("SELECT COUNT(*) FROM rentals WHERE status_pembayaran = 'menunggu_konfirmasi'")->fetchColumn();
    $total_revenue = (int) $conn->query(
        "SELECT COALESCE(SUM(total_harga),0) FROM rentals WHERE status_pembayaran='lunas' AND (status_refund IS NULL OR status_refund = 'tidak_ada')"
    )->fetchColumn();

    $tab_counts = [
        'semua' => $total_rentals,
        'pending' => countTab($conn, "r.status_pembayaran='pending'"),
        'menunggu_konfirmasi' => $menunggu,
        'lunas' => countTab($conn, "r.status_pembayaran='lunas'"),
        'ditolak' => countTab($conn, "r.status_pembayaran='ditolak'"),
        'sedang_dipinjam' => countTab($conn, "r.status_pinjam='sedang_dipinjam'"),
        'selesai' => countTab($conn, "r.status_pinjam='selesai'"),
    ];

    echo json_encode([
        'success' => true,
        'data' => [
            'rentals' => $rentals,
            'stats' => [
                'total' => $total_rentals,
                'menunggu_konfirmasi' => $menunggu,
                'revenue' => $total_revenue,
            ],
            'tab_counts' => $tab_counts,
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan server.', 'debug' => $e->getMessage()]);
}
