<?php
/**
 * api/api_admin_users.php
 * GET — List semua users + stats untuk admin.
 */
require 'api_admin_auth_middleware.php';

try {
    // Stats
    $total_users = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
    $total_approved = (int) $conn->query("SELECT COUNT(*) FROM users WHERE status = 'approved' AND role != 'admin'")->fetchColumn();
    $total_pending = (int) $conn->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
    $total_cooldown = (int) $conn->query("SELECT COUNT(*) FROM users WHERE status = 'cooldown'")->fetchColumn();

    // List semua users (kecuali admin)
    $users = $conn->query("
        SELECT id, username, role, status, alamat, nomor_wa, foto_profil, ktm, ktp, banned_until
        FROM users
        WHERE role != 'admin'
        ORDER BY
            CASE
                WHEN status = 'pending' THEN 1
                WHEN status = 'approved' THEN 2
                WHEN status = 'cooldown' THEN 3
                ELSE 4
            END,
            id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'stats' => [
                'total' => $total_users,
                'approved' => $total_approved,
                'pending' => $total_pending,
                'cooldown' => $total_cooldown,
            ],
            'users' => $users,
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan server.', 'debug' => $e->getMessage()]);
}
