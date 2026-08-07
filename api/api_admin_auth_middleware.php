<?php
/**
 * api/api_admin_auth_middleware.php
 * Middleware autentikasi khusus admin.
 * Validasi token + pastikan role == 'admin'.
 */

header('Content-Type: application/json');
require '../config/db.php';

function getBearerToken(): ?string
{
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $matches[1];
    }
    return null;
}

$token = getBearerToken();

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token tidak ditemukan.']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE api_token = ?");
$stmt->execute([$token]);
$admin = $stmt->fetch();

if (!$admin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token tidak valid atau sudah kadaluarsa.']);
    exit;
}

if ($admin['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya admin yang bisa mengakses.']);
    exit;
}

// Token age check (30 hari)
$tokenAge = time() - strtotime($admin['token_created_at']);
if ($tokenAge > 30 * 24 * 60 * 60) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesi admin sudah habis, silakan login ulang.']);
    exit;
}

// $admin sekarang bisa dipakai — data user admin yang sedang login
