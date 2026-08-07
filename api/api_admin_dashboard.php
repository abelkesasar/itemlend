<?php
/**
 * api/api_admin_dashboard.php
 * GET — Data dashboard admin (stats, revenue, pending counts).
 */
require 'api_admin_auth_middleware.php'; // validasi admin

try {
    // Stats utama
    $total_users = (int) $conn->query("SELECT COUNT(*) FROM users WHERE status != 'cooldown' OR role = 'admin'")->fetchColumn();
    $pending_users = (int) $conn->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
    $cooldown_users = (int) $conn->query("SELECT COUNT(*) FROM users WHERE status = 'cooldown'")->fetchColumn();

    $total_items = (int) $conn->query("SELECT COUNT(*) FROM items")->fetchColumn();
    $pending_items = (int) $conn->query("SELECT COUNT(*) FROM items WHERE status = 'pending'")->fetchColumn();
    $cooldown_items = (int) $conn->query("SELECT COUNT(*) FROM items WHERE status = 'cooldown'")->fetchColumn();

    $total_rentals = (int) $conn->query("SELECT COUNT(*) FROM rentals")->fetchColumn();
    $pending_reports = (int) $conn->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();

    $pending_pembayaran = (int) $conn->query(
        "SELECT COUNT(*) FROM rentals WHERE status_pembayaran = 'menunggu_konfirmasi' AND bukti_pembayaran IS NOT NULL"
    )->fetchColumn();

    $pending_pencairan = (int) $conn->query(
        "SELECT COUNT(*) FROM rentals WHERE status_pencairan = 'belum_dicairkan' AND status_pembayaran = 'lunas' AND status_pinjam = 'selesai'"
    )->fetchColumn();

    // Revenue
    $revenue_minggu = (int) $conn->query(
        "SELECT COALESCE(SUM(total_harga), 0) FROM rentals
         WHERE status_pembayaran = 'lunas'
         AND (status_refund IS NULL OR status_refund = 'tidak_ada')
         AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
    )->fetchColumn();

    $revenue_total = (int) $conn->query(
        "SELECT COALESCE(SUM(total_harga), 0) FROM rentals
         WHERE status_pembayaran = 'lunas'
         AND (status_refund IS NULL OR status_refund = 'tidak_ada')"
    )->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => [
            'stats' => [
                'total_users' => $total_users,
                'pending_users' => $pending_users,
                'cooldown_users' => $cooldown_users,
                'total_items' => $total_items,
                'pending_items' => $pending_items,
                'cooldown_items' => $cooldown_items,
                'total_rentals' => $total_rentals,
                'pending_reports' => $pending_reports,
                'pending_pembayaran' => $pending_pembayaran,
                'pending_pencairan' => $pending_pencairan,
            ],
            'revenue' => [
                'minggu' => $revenue_minggu,
                'total' => $revenue_total,
            ],
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan server.', 'debug' => $e->getMessage()]);
}
