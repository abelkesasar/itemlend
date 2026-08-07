<?php
/**
 * api/api_admin_pencairan_action.php
 * POST — Tandai sudah dicairkan (dengan upload bukti).
 * Fields: rental_id
 * File: bukti_pencairan (multipart)
 *
 * CATATAN: Karena mobile app kirim multipart, kita handle file upload di sini.
 */
require 'api_admin_auth_middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

// Support both JSON and form-data
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    $rental_id = (int) ($input['rental_id'] ?? 0);
} else {
    $rental_id = (int) ($_POST['rental_id'] ?? 0);
}

if ($rental_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID rental tidak valid.']);
    exit;
}

try {
    // Cek laporan pending
    $cekLaporan = $conn->prepare("SELECT COUNT(*) FROM reports WHERE target_id = ? AND status = 'pending'");
    $cekLaporan->execute([$rental_id]);
    if ((int) $cekLaporan->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Pencairan diblokir! Masih ada laporan pending pada rental ini.']);
        exit;
    }

    // Ambil data rental
    $rent = $conn->prepare("
        SELECT r.total_harga, r.tanggal_mulai, r.tanggal_selesai, i.harga,
               r.ganti_rugi_deduction, r.komisi_admin
        FROM rentals r JOIN items i ON r.item_id = i.id WHERE r.id = ?
    ");
    $rent->execute([$rental_id]);
    $rd = $rent->fetch(PDO::FETCH_ASSOC);

    if (!$rd) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Rental tidak ditemukan.']);
        exit;
    }

    $dur = (int) ((strtotime($rd['tanggal_selesai']) - strtotime($rd['tanggal_mulai'])) / 86400);
    $tot = $rd['total_harga'] ?: ($dur * $rd['harga']);
    $komisi = $rd['komisi_admin'] ?: (int) round($tot * 0.05);
    $grDeduction = (int) ($rd['ganti_rugi_deduction'] ?? 0);
    $dicairkan = $tot - $komisi + $grDeduction;

    // Handle bukti upload (jika ada)
    $bukti_file = null;
    if (!empty($_FILES['bukti_pencairan']['name']) && $_FILES['bukti_pencairan']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['bukti_pencairan']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        if (in_array($ext, $allowed)) {
            $bukti_name = 'pencairan_' . $rental_id . '_' . time() . '.' . $ext;
            $dest = '../uploads/bukti/' . $bukti_name;
            if (move_uploaded_file($_FILES['bukti_pencairan']['tmp_name'], $dest)) {
                $bukti_file = $bukti_name;
            }
        }
    }

    // Update status pencairan
    $conn->prepare("
        UPDATE rentals
        SET status_pencairan = 'sudah_dicairkan',
            bukti_pencairan = ?,
            tanggal_pencairan = NOW(),
            komisi_admin = ?,
            jumlah_dicairkan = ?
        WHERE id = ?
    ")->execute([$bukti_file, $komisi, $dicairkan, $rental_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Pencairan berhasil diproses.',
        'data' => [
            'total_sewa' => $tot,
            'komisi' => $komisi,
            'ganti_rugi' => $grDeduction,
            'dicairkan' => $dicairkan,
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan server.', 'debug' => $e->getMessage()]);
}
