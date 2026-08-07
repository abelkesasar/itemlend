<?php
/**
 * api/api_pesanan_saya.php
 * Endpoint GET untuk mengambil daftar pesanan milik user yang login (sebagai penyewa).
 * Wajib login (token).
 *
 * Header: Authorization: Bearer <token>
 * Response: { success, data: { rentals: [...], stats: {...} } }
 */

require 'api_auth_middleware.php';

function respond(array $data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$user_id = (int) $user['id'];

try {
    $stmt = $conn->prepare("
        SELECT
            r.id AS rental_id,
            r.user_id,
            r.item_id,
            r.tanggal_mulai,
            r.tanggal_selesai,
            r.total_harga,
            r.status_pembayaran,
            r.status_pinjam,
            r.paid_at,
            r.created_at,
            i.nama_barang,
            i.harga,
            i.gambar,
            i.lokasi,
            i.kategori,
            u.username AS pemilik,
            u.nomor_wa AS wa_pemilik
        FROM rentals r
        JOIN items i ON r.item_id = i.id
        JOIN users u ON i.user_id = u.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format + stats
    $stats = ['semua' => 0, 'belum_bayar' => 0, 'menunggu_konfirmasi' => 0, 'lunas' => 0, 'sedang_dipinjam' => 0, 'selesai' => 0];

    foreach ($rentals as &$r) {
        $images = json_decode($r['gambar'], true);
        $r['gambar_url'] = null;
        if (!empty($images[0])) {
            $r['gambar_url'] = 'http://10.0.2.2/itemlend/uploads/' . $images[0];
        }
        $r['rental_id'] = (int) $r['rental_id'];
        $r['item_id'] = (int) $r['item_id'];
        $r['harga'] = (int) $r['harga'];
        $r['total_harga'] = (int) $r['total_harga'];

        // Hitung durasi
        $start = strtotime($r['tanggal_mulai']);
        $end = strtotime($r['tanggal_selesai']);
        $r['durasi_hari'] = (int) max(1, ($end - $start) / 86400);

        // Stats
        $sp = $r['status_pembayaran'];
        $spj = $r['status_pinjam'];
        $stats['semua']++;
        if ($sp === 'pending') $stats['belum_bayar']++;
        elseif ($sp === 'menunggu_konfirmasi') $stats['menunggu_konfirmasi']++;
        elseif ($sp === 'lunas' && $spj === 'belum_mulai') $stats['lunas']++;
        elseif ($spj === 'sedang_dipinjam') $stats['sedang_dipinjam']++;
        elseif ($spj === 'selesai') $stats['selesai']++;
    }
    unset($r);

    respond([
        'success' => true,
        'data' => [
            'rentals' => $rentals,
            'stats' => $stats,
        ],
    ]);

} catch (PDOException $e) {
    respond([
        'success' => false,
        'message' => 'Terjadi kesalahan server.',
        'debug' => $e->getMessage()
    ], 500);
}
