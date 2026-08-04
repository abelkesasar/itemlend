<?php
/**
 * api/auth/login.php
 * Endpoint login untuk konsumsi Mobile App (Flutter) - ItemLend
 *
 * Berbeda dari login.php versi web:
 * - Tidak pakai $_SESSION, tapi token (disimpan di kolom `api_token` pada tabel users)
 * - Response full JSON (bukan alert() JS)
 * - Backward compatible dengan password lama yang masih di-hash MD5,
 *   otomatis di-upgrade ke password_hash() (bcrypt) saat login sukses
 *
 * CATATAN SETUP:
 * 1. Jalankan dulu migrasi kolom berikut di tabel `users` (kalau belum ada):
 *
 *    ALTER TABLE users ADD COLUMN api_token VARCHAR(64) NULL DEFAULT NULL;
 *    ALTER TABLE users ADD COLUMN token_created_at DATETIME NULL DEFAULT NULL;
 *
 * 2. Letakkan file ini di: api/auth/login.php
 *    (sesuaikan path require '../../config/db.php' di bawah kalau struktur foldermu beda)
 */

header('Content-Type: application/json');

require '../config/db.php'; // dari api/ naik 1 folder ke config/

// --- Ambil input JSON dari body request (Flutter kirim via http.post + jsonEncode) ---
$input = json_decode(file_get_contents('php://input'), true);

$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Username dan password wajib diisi.'
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Username tidak ditemukan.'
        ]);
        exit;
    }

    // --- Cek password: dukung hash lama (MD5) & hash baru (password_hash/bcrypt) ---
    $isValidPassword = false;

    if (password_get_info($user['password'])['algo'] !== null) {
        // Password sudah pakai password_hash() (bcrypt/argon2)
        $isValidPassword = password_verify($password, $user['password']);
    } else {
        // Password masih format lama (MD5)
        $isValidPassword = (md5($password) === $user['password']);

        // Kalau cocok, upgrade otomatis ke password_hash() supaya makin aman ke depannya
        if ($isValidPassword) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->execute([$newHash, $user['id']]);
        }
    }

    if (!$isValidPassword) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Password salah.'
        ]);
        exit;
    }

    // --- Cek status approval & cooldown/ban (logic sama seperti versi web) ---
    if ($user['role'] !== 'admin' && $user['status'] === 'pending') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Akun kamu masih menunggu persetujuan admin. Silakan tunggu ya!'
        ]);
        exit;
    }

    if ($user['role'] !== 'admin' && $user['status'] === 'cooldown') {
        $bannedUntil = $user['banned_until'] ?? null;

        if ($bannedUntil && strtotime($bannedUntil) > time()) {
            // Masih dalam masa cooldown -> tolak login, kasih tau sampai kapan
            $sisaDetik = strtotime($bannedUntil) - time();
            $sisaHari = ceil($sisaDetik / 86400);

            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => "Akun kamu sedang dalam masa cooldown sampai tanggal " .
                              date('d M Y H:i', strtotime($bannedUntil)) .
                              " (sekitar $sisaHari hari lagi).",
                'data' => [
                    'banned_until' => $bannedUntil,
                ]
            ]);
            exit;
        } else {
            // Masa cooldown sudah lewat -> pulihkan otomatis jadi approved
            $restore = $conn->prepare("UPDATE users SET status = 'approved', banned_until = NULL WHERE id = ?");
            $restore->execute([$user['id']]);
            $user['status'] = 'approved';
        }
    }

    // --- Generate token baru untuk sesi mobile ---
    $token = bin2hex(random_bytes(32)); // token acak 64 karakter

    $updToken = $conn->prepare("UPDATE users SET api_token = ?, token_created_at = NOW() WHERE id = ?");
    $updToken->execute([$token, $user['id']]);

    // --- Response sukses ---
    echo json_encode([
        'success' => true,
        'message' => 'Login berhasil.',
        'data' => [
            'token' => $token,
            'user' => [
                'id'       => $user['id'],
                'username' => $user['username'],
                'role'     => $user['role'],
                'status'   => $user['status'],
            ]
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server.',
        // Hapus/jangan tampilkan detail error di production!
        'debug' => $e->getMessage()
    ]);
}