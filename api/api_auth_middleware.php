<?php
/**
 * api/auth_middleware.php
 * Dipakai di setiap endpoint API lain yang butuh user sudah login.
 * Cara pakai: require ini di paling atas endpoint, lalu $user akan
 * berisi data user yang sedang login (hasil validasi token).
 *
 * Contoh header yang dikirim dari Flutter:
 *   Authorization: Bearer <token>
 */

header('Content-Type: application/json');
require '../config/db.php'; // dari api/ naik 1 folder ke config/

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
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token tidak valid atau sudah kadaluarsa.']);
    exit;
}

// Opsional: kasih batas umur token, misal 30 hari
$tokenAge = time() - strtotime($user['token_created_at']);
if ($tokenAge > 30 * 24 * 60 * 60) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesi kamu sudah habis, silakan login ulang.']);
    exit;
}

// $user sekarang bisa dipakai di endpoint yang me-require file ini