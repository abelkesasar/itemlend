<?php
/**
 * api/api_admin_user_detail.php
 * GET — Detail user by ID untuk admin.
 * Query: ?id=<user_id>
 */
require 'api_admin_auth_middleware.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID user tidak valid.']);
    exit;
}

try {
    // Data user
    $stmt = $conn->prepare("SELECT id, username, email, role, status, alamat, nomor_wa, foto_profil, ktm, ktp, banned_until, deskripsi_vendor, created_at FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan.']);
        exit;
    }

    // Barang milik user
    $stmt = $conn->prepare("SELECT id, nama_barang, kategori, harga, stok, status, created_at, gambar, banned_until FROM items WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Transaksi sebagai peminjam
    $stmt = $conn->prepare("
        SELECT r.id, r.tanggal_mulai, r.tanggal_selesai, r.total_harga,
               r.status_pembayaran, r.status_pinjam, r.created_at,
               i.nama_barang,
               u_owner.username AS pemilik
        FROM rentals r
        LEFT JOIN items i ON r.item_id = i.id
        LEFT JOIN users u_owner ON i.user_id = u_owner.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$id]);
    $rentals_borrower = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Transaksi sebagai pemilik
    $stmt = $conn->prepare("
        SELECT r.id, r.tanggal_mulai, r.tanggal_selesai, r.total_harga,
               r.status_pembayaran, r.status_pinjam, r.created_at,
               i.nama_barang,
               u_borrower.username AS peminjam
        FROM rentals r
        LEFT JOIN items i ON r.item_id = i.id
        LEFT JOIN users u_borrower ON r.user_id = u_borrower.id
        WHERE i.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$id]);
    $rentals_owner = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Revenue
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(r.total_harga), 0) AS total_revenue,
               COUNT(*) AS total_transaksi
        FROM rentals r
        LEFT JOIN items i ON r.item_id = i.id
        WHERE i.user_id = ? AND r.status_pembayaran = 'lunas'
        AND (r.status_refund IS NULL OR r.status_refund = 'tidak_ada')
    ");
    $stmt->execute([$id]);
    $revenue = $stmt->fetch(PDO::FETCH_ASSOC);

    // Laporan yang melibatkan user ini
    $stmt = $conn->prepare("
        SELECT rp.id, rp.reason, rp.status, rp.created_at, rp.ganti_rugi_amount,
               rp.tagihan_ganti_rugi,
               rt.id AS rental_id,
               i.nama_barang
        FROM reports rp
        JOIN rentals rt ON rt.id = rp.target_id
        JOIN items i ON i.id = rt.item_id
        WHERE rp.reporter_id = ? OR rt.user_id = ? OR i.user_id = ?
        ORDER BY rp.created_at DESC
    ");
    $stmt->execute([$id, $id, $id]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'user' => $user,
            'items' => $items,
            'rentals_borrower' => $rentals_borrower,
            'rentals_owner' => $rentals_owner,
            'revenue' => $revenue,
            'reports' => $reports,
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan server.', 'debug' => $e->getMessage()]);
}
