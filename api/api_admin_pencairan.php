<?php
/**
 * api/api_admin_pencairan.php
 * GET — List data pencairan untuk admin.
 * Query: ?tab=belum|sudah|semua (default: belum), ?search=..., ?sort=...
 */
require 'api_admin_auth_middleware.php';

$tab = $_GET['tab'] ?? 'belum';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'terbaru';

$where = [];
$params = [];

if ($tab === 'belum') {
    $where[] = "r.status_pinjam = 'selesai' AND r.status_pembayaran = 'lunas' AND r.status_pencairan = 'belum_dicairkan'";
} elseif ($tab === 'sudah') {
    $where[] = "r.status_pencairan = 'sudah_dicairkan'";
} else {
    $where[] = "(r.status_pinjam = 'selesai' AND r.status_pembayaran = 'lunas')";
}

if ($search !== '') {
    $where[] = "(i.nama_barang LIKE :q OR u.username LIKE :q OR pu.username LIKE :q)";
    $params[':q'] = "%$search%";
}

$order = match($sort) {
    'terlama' => 'r.created_at ASC',
    'terbesar' => 'r.total_harga DESC',
    'terkecil' => 'r.total_harga ASC',
    default => 'r.created_at DESC',
};

try {
    $whereClause = !empty($where) ? implode(' AND ', $where) : '1=1';
    $sql = "
        SELECT r.id, r.user_id, r.item_id, r.tanggal_mulai, r.tanggal_selesai,
               r.total_harga, r.status_pembayaran, r.status_pinjam, r.status_pencairan,
               r.bukti_pencairan, r.tanggal_pencairan, r.komisi_admin, r.jumlah_dicairkan,
               r.ganti_rugi_deduction, r.created_at,
               i.nama_barang, i.harga, i.gambar, i.lokasi,
               u.username AS penyewa,
               pu.username AS pemilik,
               pu.metode_pembayaran AS pemilik_metode,
               pu.nama_penyedia AS pemilik_penyedia,
               pu.nomor_rekening AS pemilik_rekening,
               pu.nama_pemilik_rekening AS pemilik_nama_rek,
               pu.foto_qris AS pemilik_foto_qris
        FROM rentals r
        JOIN items i ON r.item_id = i.id
        JOIN users u ON r.user_id = u.id
        JOIN users pu ON i.user_id = pu.id
        WHERE $whereClause
        ORDER BY $order
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Hitung pending reports per rental
    $rental_ids = array_column($rows, 'id');
    $rental_pending_map = [];
    if (!empty($rental_ids)) {
        $placeholders = implode(',', array_fill(0, count($rental_ids), '?'));
        $prStmt = $conn->prepare("SELECT target_id, COUNT(*) AS cnt FROM reports WHERE target_id IN ($placeholders) AND status = 'pending' GROUP BY target_id");
        $prStmt->execute($rental_ids);
        foreach ($prStmt->fetchAll(PDO::FETCH_ASSOC) as $pr) {
            $rental_pending_map[$pr['target_id']] = (int) $pr['cnt'];
        }
    }

    // Stats
    $total_belum = (int) $conn->query("
        SELECT COUNT(*) FROM rentals
        WHERE status_pinjam = 'selesai' AND status_pembayaran = 'lunas' AND status_pencairan = 'belum_dicairkan'
    ")->fetchColumn();
    $total_sudah = (int) $conn->query("SELECT COUNT(*) FROM rentals WHERE status_pencairan = 'sudah_dicairkan'")->fetchColumn();
    $total_nilai_belum = (int) $conn->query("
        SELECT COALESCE(SUM(total_harga - COALESCE(komisi_admin, ROUND(total_harga * 0.05)) + COALESCE(ganti_rugi_deduction, 0)), 0)
        FROM rentals WHERE status_pinjam = 'selesai' AND status_pembayaran = 'lunas' AND status_pencairan = 'belum_dicairkan'
    ")->fetchColumn();
    $total_nilai_sudah = (int) $conn->query("
        SELECT COALESCE(SUM(jumlah_dicairkan), 0)
        FROM rentals WHERE status_pencairan = 'sudah_dicairkan'
    ")->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => [
            'rentals' => $rows,
            'pending_reports_map' => $rental_pending_map,
            'stats' => [
                'belum' => $total_belum,
                'sudah' => $total_sudah,
                'nilai_belum' => $total_nilai_belum,
                'nilai_sudah' => $total_nilai_sudah,
            ],
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan server.', 'debug' => $e->getMessage()]);
}
