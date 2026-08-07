<?php
/**
 * api/api_admin_reports.php
 * GET — List semua laporan untuk admin.
 * Query: ?status=pending|reviewed|dismissed (default: all)
 */
require 'api_admin_auth_middleware.php';

$filter_status = $_GET['status'] ?? 'all';

try {
    $sql = "
        SELECT
            rp.*,
            us.username AS reporter_nama, us.nomor_wa AS reporter_wa, us.id AS reporter_id,
            us.status AS reporter_status, us.banned_until AS reporter_banned_until,
            us.foto_profil AS reporter_foto,
            rt.user_id AS penyewa_id_q, rt.item_id,
            rt.tanggal_mulai, rt.tanggal_selesai,
            rt.total_harga, rt.status_pinjam, rt.status_pembayaran,
            rt.status_refund, rt.refund_ke, rt.refund_at, rt.catatan_refund,
            rt.status_pencairan, rt.ganti_rugi_deduction,
            it.nama_barang, it.lokasi AS item_lokasi,
            it.status AS item_status, it.gambar AS item_gambar,
            ow.username AS pemilik_nama, ow.nomor_wa AS pemilik_wa,
            ow.id AS pemilik_id, ow.status AS pemilik_status,
            ow.banned_until AS pemilik_banned_until,
            pw.username AS penyewa_nama, pw.nomor_wa AS penyewa_wa,
            pw.id AS penyewa_id, pw.status AS penyewa_status,
            pw.banned_until AS penyewa_banned_until
        FROM reports rp
        JOIN users us ON us.id = rp.reporter_id
        JOIN rentals rt ON rt.id = rp.target_id
        JOIN items it ON it.id = rt.item_id
        LEFT JOIN users ow ON ow.id = it.user_id
        LEFT JOIN users pw ON pw.id = rt.user_id
    ";

    $params = [];
    if (in_array($filter_status, ['pending', 'reviewed', 'dismissed'])) {
        $sql .= " WHERE rp.status = :status";
        $params[':status'] = $filter_status;
    }

    $sql .= " ORDER BY
        CASE WHEN rp.status = 'pending' THEN 1 WHEN rp.status = 'reviewed' THEN 2 ELSE 3 END,
        rp.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $total_all = count($reports);
    $total_pending = 0;
    $total_reviewed = 0;
    $total_dismissed = 0;
    foreach ($reports as $r) {
        if ($r['status'] === 'pending') $total_pending++;
        elseif ($r['status'] === 'reviewed') $total_reviewed++;
        else $total_dismissed++;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'reports' => $reports,
            'stats' => [
                'total' => $total_all,
                'pending' => $total_pending,
                'reviewed' => $total_reviewed,
                'dismissed' => $total_dismissed,
            ],
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan server.', 'debug' => $e->getMessage()]);
}
