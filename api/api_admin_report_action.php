<?php
/**
 * api/api_admin_report_action.php
 * POST — Proses laporan (sanksi, refund, ganti rugi).
 * Fields: id, sanksi_option, refund_option, catatan_refund, tagihan_ganti_rugi, amount_ganti_rugi
 */
require 'api_admin_auth_middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = (int) ($input['id'] ?? $_POST['id'] ?? 0);
$sanksi = $input['sanksi_option'] ?? 'none';
$refund = $input['refund_option'] ?? 'tidak_ada';
$catatan = trim($input['catatan_refund'] ?? '');
$tagihanDesc = trim($input['tagihan_ganti_rugi'] ?? '');
$tagihanTotal = (int) ($input['amount_ganti_rugi'] ?? 0);
$admin_id = (int) $admin['id'];

$allowed_sanksi = [
    'none', 'penyewa_cooldown', 'pemilik_cooldown', 'penyewa_banned', 'pemilik_banned',
    'keduanya_cooldown', 'keduanya_banned', 'barang_cooldown', 'barang_hapus',
    'barang_hapus_pemilik_banned', 'dismissed', 'tagihan_ganti_rugi',
];
$allowed_refund = ['tidak_ada', 'penyewa', 'pemilik'];

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID laporan tidak valid.']);
    exit;
}
if (!in_array($sanksi, $allowed_sanksi)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Sanksi tidak valid.']);
    exit;
}
if (!in_array($refund, $allowed_refund)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Refund option tidak valid.']);
    exit;
}

try {
    // Ambil data report + rental
    $stmtRep = $conn->prepare("
        SELECT rp.target_id AS rental_id, rt.user_id AS penyewa_id, i.user_id AS pemilik_id, rt.item_id
        FROM reports rp
        JOIN rentals rt ON rt.id = rp.target_id
        JOIN items i ON i.id = rt.item_id
        WHERE rp.id = ?
    ");
    $stmtRep->execute([$id]);
    $data = $stmtRep->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Laporan tidak ditemukan.']);
        exit;
    }

    $penyewa_id = (int) $data['penyewa_id'];
    $pemilik_id = (int) $data['pemilik_id'];
    $item_id = (int) $data['item_id'];
    $rental_id = (int) $data['rental_id'];

    $banned_7hari = date('Y-m-d H:i:s', strtotime('+7 days'));
    $banned_forever = '9999-12-31 23:59:59';

    // Helper functions
    $setCooldownUser = function(int $uid) use ($conn, $banned_7hari) {
        $conn->prepare("UPDATE users SET banned_until = ?, status = 'cooldown' WHERE id = ?")->execute([$banned_7hari, $uid]);
        $conn->prepare("UPDATE items SET status = 'cooldown', banned_until = ? WHERE user_id = ?")->execute([$banned_7hari, $uid]);
    };
    $setBannedUser = function(int $uid) use ($conn, $banned_forever) {
        $conn->prepare("UPDATE users SET banned_until = ?, status = 'cooldown' WHERE id = ?")->execute([$banned_forever, $uid]);
        $conn->prepare("UPDATE items SET status = 'cooldown', banned_until = ? WHERE user_id = ?")->execute([$banned_forever, $uid]);
    };

    // Apply sanksi
    switch ($sanksi) {
        case 'penyewa_cooldown': $setCooldownUser($penyewa_id); break;
        case 'pemilik_cooldown': $setCooldownUser($pemilik_id); break;
        case 'penyewa_banned': $setBannedUser($penyewa_id); break;
        case 'pemilik_banned': $setBannedUser($pemilik_id); break;
        case 'keduanya_cooldown': $setCooldownUser($penyewa_id); $setCooldownUser($pemilik_id); break;
        case 'keduanya_banned': $setBannedUser($penyewa_id); $setBannedUser($pemilik_id); break;
        case 'barang_cooldown':
            $conn->prepare("UPDATE items SET status = 'cooldown', banned_until = ? WHERE id = ?")->execute([$banned_7hari, $item_id]);
            break;
        case 'barang_hapus':
            $conn->prepare("DELETE FROM items WHERE id = ?")->execute([$item_id]);
            break;
        case 'barang_hapus_pemilik_banned':
            $conn->prepare("DELETE FROM items WHERE id = ?")->execute([$item_id]);
            $setBannedUser($pemilik_id);
            break;
        case 'tagihan_ganti_rugi':
        case 'dismissed':
            break;
    }

    // Proses Refund
    if ($refund !== 'tidak_ada' && $sanksi !== 'dismissed') {
        $conn->prepare("
            UPDATE rentals
            SET status_refund = 'selesai', status_pencairan = 'sudah_dicairkan',
                refund_ke = ?, catatan_refund = ?, refund_by = ?, refund_at = NOW()
            WHERE id = ?
        ")->execute([$refund, $catatan ?: null, $admin_id, $rental_id]);
    }

    // Simpan data ganti rugi
    $updateRpt = [];
    $rptParams = [];
    if ($sanksi === 'tagihan_ganti_rugi' && !empty($tagihanDesc) && $tagihanTotal > 0) {
        $updateRpt[] = 'tagihan_ganti_rugi = ?';
        $updateRpt[] = 'ganti_rugi_amount = ?';
        $rptParams[] = $tagihanDesc;
        $rptParams[] = $tagihanTotal;
    }
    if (!empty($updateRpt)) {
        $rptParams[] = $id;
        $conn->prepare("UPDATE reports SET " . implode(', ', $updateRpt) . " WHERE id = ?")->execute($rptParams);
    }

    // Update ganti_rugi_deduction di rentals
    if ($sanksi === 'tagihan_ganti_rugi' && $tagihanTotal > 0) {
        $conn->prepare("UPDATE rentals SET ganti_rugi_deduction = ganti_rugi_deduction + ? WHERE id = ?")->execute([$tagihanTotal, $rental_id]);
    } elseif ($sanksi === 'dismissed') {
        // Cek apakah masih ada laporan pending lain
        $cekLain = $conn->prepare("SELECT COUNT(*) FROM reports WHERE target_id = ? AND id != ? AND status = 'pending'");
        $cekLain->execute([$rental_id, $id]);
        if ((int) $cekLain->fetchColumn() === 0) {
            $conn->prepare("UPDATE rentals SET status_pencairan = 'belum_dicairkan' WHERE id = ? AND status_pencairan = 'ada_laporan'")->execute([$rental_id]);
        }
    } else {
        $cekLain = $conn->prepare("SELECT COUNT(*) FROM reports WHERE target_id = ? AND id != ? AND status = 'pending'");
        $cekLain->execute([$rental_id, $id]);
        if ((int) $cekLain->fetchColumn() === 0) {
            $conn->prepare("UPDATE rentals SET status_pencairan = 'belum_dicairkan' WHERE id = ? AND status_pencairan = 'ada_laporan'")->execute([$rental_id]);
        }
    }

    // Tutup rental: set status_pinjam = selesai
    if ($sanksi !== 'dismissed') {
        $conn->prepare("UPDATE rentals SET status_pinjam = 'selesai' WHERE id = ? AND status_pinjam != 'selesai'")->execute([$rental_id]);
    }

    // Update status laporan
    $final_status = ($sanksi === 'dismissed') ? 'dismissed' : 'reviewed';
    $conn->prepare("UPDATE reports SET status = :st, reviewed_at = NOW(), reviewed_by = :admin WHERE id = :id")
         ->execute([':st' => $final_status, ':admin' => $admin_id, ':id' => $id]);

    echo json_encode(['success' => true, 'message' => 'Laporan berhasil diproses.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan server.', 'debug' => $e->getMessage()]);
}
