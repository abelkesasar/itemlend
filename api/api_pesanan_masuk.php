<?php
/**
 * api/api_pesanan_masuk.php
 * Endpoint GET untuk mengambil daftar pesanan masuk (rental yang masuk ke barang milik pemilik).
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

$owner_id = (int) $user['id'];

try {
    // Ambil semua rental untuk barang milik pemilik ini
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
            i.status AS item_status,
            u.username AS penyewa,
            u.nomor_wa AS wa_penyewa,
            u.foto_profil AS foto_penyewa
        FROM rentals r
        JOIN items i ON r.item_id = i.id
        JOIN users u ON r.user_id = u.id
        WHERE i.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$owner_id]);
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format gambar (decode JSON array, ambil gambar pertama)
    foreach ($rentals as &$rental) {
        $images = json_decode($rental['gambar'], true);
        $rental['gambar_url'] = null;
        if (!empty($images[0])) {
            $rental['gambar_url'] = 'http://10.0.2.2/itemlend/uploads/' . $images[0];
        }
        // Convert integer fields
        $rental['rental_id'] = (int) $rental['rental_id'];
        $rental['user_id'] = (int) $rental['user_id'];
        $rental['item_id'] = (int) $rental['item_id'];
        $rental['harga'] = (int) $rental['harga'];
        $rental['total_harga'] = (int) $rental['total_harga'];
    }
    unset($rental);

    // Hitung statistik
    $total = count($rentals);
    $lunas = 0;
    $belum_bayar = 0;
    $menunggu = 0;
    $sedang_dipinjam = 0;
    $selesai = 0;

    foreach ($rentals as $r) {
        if ($r['status_pembayaran'] === 'lunas') {
            $lunas++;
            if ($r['status_pinjam'] === 'sedang_dipinjam') $sedang_dipinjam++;
            elseif ($r['status_pinjam'] === 'selesai') $selesai++;
        } elseif ($r['status_pembayaran'] === 'pending') {
            $belum_bayar++;
        } elseif ($r['status_pembayaran'] === 'menunggu_konfirmasi') {
            $menunggu++;
        }
    }

    respond([
        'success' => true,
        'data' => [
            'rentals' => $rentals,
            'stats' => [
                'total' => $total,
                'lunas' => $lunas,
                'belum_bayar' => $belum_bayar,
                'menunggu_konfirmasi' => $menunggu,
                'sedang_dipinjam' => $sedang_dipinjam,
                'selesai' => $selesai,
            ],
        ],
    ]);

} catch (PDOException $e) {
    respond([
        'success' => false,
        'message' => 'Terjadi kesalahan server.',
        'debug' => $e->getMessage()
    ], 500);
}