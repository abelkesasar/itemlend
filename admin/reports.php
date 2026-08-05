<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php?page=login");
    exit;
}

// ──────────────────────────────────────────────
// Helper: upload bukti
// ──────────────────────────────────────────────
function uploadBukti(array $file, string $prefix): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] <= 0) return null;
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($file['type'], $allowed)) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $name = $prefix . '_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
    move_uploaded_file($file['tmp_name'], '../uploads/' . $name);
    return $name;
}

// ──────────────────────────────────────────────
// POST: proses laporan
// ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = (int) ($_POST['id']           ?? 0);
    $sanksi    = $_POST['sanksi_option']        ?? 'none';
    $refund    = $_POST['refund_option']        ?? 'tidak_ada';
    $catatan   = trim($_POST['catatan_refund']  ?? '');
    $admin_id  = (int) ($_SESSION['user']       ?? 0);

    // Upload bukti refund admin
    $buktiRefundAdmin = null;
    if (!empty($_FILES['bukti_refund_admin']['name'])) {
        $buktiRefundAdmin = uploadBukti($_FILES['bukti_refund_admin'], 'refund_admin_' . $id);
    }

    // Tagihan ganti rugi dari form
    $tagihanDesc  = trim($_POST['tagihan_ganti_rugi'] ?? '');
    $tagihanTotal = (int) ($_POST['amount_ganti_rugi'] ?? 0);

    $allowed_sanksi = [
        'none','penyewa_cooldown','pemilik_cooldown','penyewa_banned','pemilik_banned',
        'keduanya_cooldown','keduanya_banned','barang_cooldown','barang_hapus',
        'barang_hapus_pemilik_banned','dismissed','tagihan_ganti_rugi',
    ];
    $allowed_refund = ['tidak_ada','penyewa','pemilik'];

    if ($id > 0 && in_array($sanksi, $allowed_sanksi) && in_array($refund, $allowed_refund)) {

        // Ambil data rental dari laporan
        $stmtRep = $conn->prepare("
            SELECT r.target_id, rt.user_id AS penyewa_id, i.user_id AS pemilik_id, rt.item_id
            FROM reports r
            JOIN rentals rt ON rt.id = r.target_id
            JOIN items   i  ON i.id  = rt.item_id
            WHERE r.id = ?
        ");
        $stmtRep->execute([$id]);
        $data = $stmtRep->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $penyewa_id = (int) $data['penyewa_id'];
            $pemilik_id = (int) $data['pemilik_id'];
            $item_id    = (int) $data['item_id'];
            $rental_id  = (int) $data['target_id'];

            $banned_7hari   = date('Y-m-d H:i:s', strtotime('+7 days'));
            $banned_forever = '9999-12-31 23:59:59';

            $setCooldownUser = function(int $uid) use ($conn, $banned_7hari) {
                $conn->prepare("UPDATE users SET banned_until = ?, status = 'cooldown' WHERE id = ?")->execute([$banned_7hari, $uid]);
                $conn->prepare("UPDATE items SET status = 'cooldown', banned_until = ? WHERE user_id = ?")->execute([$banned_7hari, $uid]);
            };
            $setBannedUser = function(int $uid) use ($conn, $banned_forever) {
                $conn->prepare("UPDATE users SET banned_until = ?, status = 'cooldown' WHERE id = ?")->execute([$banned_forever, $uid]);
                $conn->prepare("UPDATE items SET status = 'cooldown', banned_until = ? WHERE user_id = ?")->execute([$banned_forever, $uid]);
            };

            switch ($sanksi) {
                case 'penyewa_cooldown':  $setCooldownUser($penyewa_id); break;
                case 'pemilik_cooldown':  $setCooldownUser($pemilik_id); break;
                case 'penyewa_banned':    $setBannedUser($penyewa_id); break;
                case 'pemilik_banned':    $setBannedUser($pemilik_id); break;
                case 'keduanya_cooldown': $setCooldownUser($penyewa_id); $setCooldownUser($pemilik_id); break;
                case 'keduanya_banned':   $setBannedUser($penyewa_id); $setBannedUser($pemilik_id); break;
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

            // ── Proses Refund / Tagihan Ganti Rugi ──
            if ($refund !== 'tidak_ada' && $sanksi !== 'dismissed') {
                $refundKe = $refund;
                $conn->prepare("
                    UPDATE rentals
                    SET status_refund  = 'selesai',
                        refund_ke      = ?,
                        catatan_refund = ?,
                        refund_by      = ?
                    WHERE id = ?
                ")->execute([$refundKe, $catatan ?: null, $admin_id, $rental_id]);
            }

            // ── Simpan data ganti rugi di reports ──
            $updateRpt = [];
            $rptParams = [];
            if ($sanksi === 'tagihan_ganti_rugi' && !empty($tagihanDesc) && $tagihanTotal > 0) {
                $updateRpt[] = 'tagihan_ganti_rugi  = ?';
                $updateRpt[] = 'ganti_rugi_amount   = ?';
                $rptParams[] = $tagihanDesc;
                $rptParams[] = $tagihanTotal;
            }
            if ($buktiRefundAdmin) {
                $updateRpt[] = 'bukti_refund_admin = ?';
                $rptParams[] = $buktiRefundAdmin;
            }
            if (!empty($updateRpt)) {
                $rptParams[] = $id;
                $conn->prepare("UPDATE reports SET " . implode(', ', $updateRpt) . " WHERE id = ?")->execute($rptParams);
            }

            // ── Update status_pencairan di rentals ──
            if ($sanksi === 'tagihan_ganti_rugi' && $tagihanTotal > 0) {
                // Ganti rugi: tambahan untuk pemilik dari penyewa (tidak blok pencairan)
                $conn->prepare("UPDATE rentals SET ganti_rugi_deduction = ganti_rugi_deduction + ? WHERE id = ?")
                     ->execute([$tagihanTotal, $rental_id]);
            } elseif ($sanksi === 'dismissed') {
                // Laporan ditolak → cek apakah masih ada laporan aktif lainnya
                $cekLain = $conn->prepare("SELECT COUNT(*) FROM reports WHERE target_id = ? AND id != ? AND status = 'pending'");
                $cekLain->execute([$rental_id, $id]);
                $adaLain = (int) $cekLain->fetchColumn();
                if ($adaLain === 0) {
                    // Tidak ada laporan lain → buka blokir pencairan
                    $conn->prepare("UPDATE rentals SET status_pencairan = 'belum_dicairkan' WHERE id = ? AND status_pencairan = 'ada_laporan'")->execute([$rental_id]);
                }
            } else {
                // Sanksi lain (bukan ganti rugi, bukan dismissed) → cek laporan aktif
                $cekLain = $conn->prepare("SELECT COUNT(*) FROM reports WHERE target_id = ? AND id != ? AND status = 'pending'");
                $cekLain->execute([$rental_id, $id]);
                $adaLain = (int) $cekLain->fetchColumn();
                if ($adaLain === 0) {
                    $conn->prepare("UPDATE rentals SET status_pencairan = 'belum_dicairkan' WHERE id = ? AND status_pencairan = 'ada_laporan'")->execute([$rental_id]);
                }
            }
        }

        // Update status laporan
        $final_status = ($sanksi === 'dismissed') ? 'dismissed' : 'reviewed';
        $conn->prepare("UPDATE reports SET status = :st, reviewed_at = NOW(), reviewed_by = :admin WHERE id = :id")
             ->execute([':st' => $final_status, ':admin' => $admin_id, ':id' => $id]);
    }

    header("Location: reports.php");
    exit;
}

// ──────────────────────────────────────────────
// GET: ambil semua laporan
// ──────────────────────────────────────────────
$reports = $conn->query("
    SELECT
        rp.*,
        us.username     AS reporter_nama,
        us.nomor_wa      AS reporter_wa,
        us.id            AS reporter_id,
        us.status        AS reporter_status,
        us.banned_until  AS reporter_banned_until,
        us.foto_profil   AS reporter_foto,
        rt.user_id       AS penyewa_id_q,
        rt.item_id,
        rt.tanggal_mulai, rt.tanggal_selesai,
        rt.total_harga, rt.status_pinjam, rt.status_pembayaran,
        rt.status_refund, rt.refund_ke, rt.refund_at, rt.catatan_refund,
        rt.status_pencairan, rt.bukti_refund AS rental_bukti_refund,
        rt.ganti_rugi_deduction,
        it.nama_barang, it.lokasi AS item_lokasi,
        it.status        AS item_status,
        it.gambar        AS item_gambar,
        ow.username      AS pemilik_nama,
        ow.nomor_wa      AS pemilik_wa,
        ow.id            AS pemilik_id,
        ow.status        AS pemilik_status,
        ow.banned_until  AS pemilik_banned_until,
        ow.foto_profil   AS pemilik_foto,
        pw.username      AS penyewa_nama,
        pw.nomor_wa      AS penyewa_wa,
        pw.id            AS penyewa_id,
        pw.status        AS penyewa_status,
        pw.banned_until  AS penyewa_banned_until,
        pw.foto_profil   AS penyewa_foto
    FROM reports rp
    JOIN users   us ON us.id  = rp.reporter_id
    JOIN rentals rt ON rt.id  = rp.target_id
    JOIN items   it ON it.id  = rt.item_id
    LEFT JOIN users ow ON ow.id = it.user_id
    LEFT JOIN users pw ON pw.id = rt.user_id
    ORDER BY
        CASE WHEN rp.status = 'pending' THEN 1 WHEN rp.status = 'reviewed' THEN 2 ELSE 3 END,
        rp.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$total_all     = count($reports);
$total_pending = count(array_filter($reports, fn($r) => $r['status'] === 'pending'));
$total_done    = count(array_filter($reports, fn($r) => $r['status'] === 'reviewed'));
$total_dismiss = count(array_filter($reports, fn($r) => $r['status'] === 'dismissed'));

function waLink(string $phone, string $nama, string $konteks): string {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($clean, '0')) $clean = '62' . substr($clean, 1);
    $pesan = urlencode("Halo $nama, saya Admin ItemLend menghubungi terkait laporan: $konteks.");
    return "https://wa.me/{$clean}?text={$pesan}";
}
function isBanned(?string $until): bool {
    return !empty($until) && strtotime($until) > time();
}
function isBannedPermanent(?string $until): bool {
    return !empty($until) && strtotime($until) > strtotime('+3 years');
}
function statusBadge(string $st): string {
    return match($st) {
        'reviewed'  => '<span class="badge b-green"><i class="ti ti-circle-check"></i>Selesai</span>',
        'dismissed' => '<span class="badge b-gray"><i class="ti ti-circle-x"></i>Ditolak</span>',
        default     => '<span class="badge b-amber"><i class="ti ti-clock"></i>Pending</span>',
    };
}
function refundBadge(?string $status, ?string $ke): string {
    if (!$status || $status === 'tidak_ada') return '';
    $label = match($ke) {
        'penyewa' => 'Penyewa',
        'pemilik' => 'Pemilik',
        default   => '?',
    };
    if ($status === 'menunggu') {
        return '<span class="badge b-orange"><i class="ti ti-coin-euro"></i>Refund Menunggu → ' . $label . '</span>';
    }
    return '<span class="badge b-teal"><i class="ti ti-coin-euro"></i>Refund Selesai → ' . $label . '</span>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Laporan — ItemLend Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f5f7;color:#1a1d2e;min-height:100vh}
a{text-decoration:none;color:inherit}
.admin-wrap{display:flex;min-height:100vh}
.main{margin-left:220px;flex:1;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid #e5e7eb;padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
.topbar h1{font-size:17px;font-weight:700}.topbar p{font-size:12px;color:#6b7280}
.admin-pill{background:#eef0ff;color:#3d4bff;font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px}
.avatar{width:32px;height:32px;border-radius:50%;background:#3d4bff;color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700}
.tb-right{display:flex;align-items:center;gap:12px}
.content{padding:24px 28px}

/* Stats */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
.sc{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;display:flex;align-items:center;gap:14px}
.sc-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:22px}
.ic-blue{background:#eef0ff;color:#3d4bff}.ic-amber{background:#fefce8;color:#a16207}
.ic-green{background:#e9f9f0;color:#16a34a}.ic-gray{background:#f4f5f7;color:#6b7280}
.sc-label{font-size:12px;color:#6b7280}.sc-val{font-size:24px;font-weight:800;color:#1a1d2e}

/* Tabs */
.tabs-bar{display:flex;gap:4px;margin-bottom:20px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:5px;overflow-x:auto}
.tab-btn{flex:1;min-width:160px;padding:10px 18px;border:none;background:transparent;border-radius:9px;font-family:inherit;font-size:13px;font-weight:700;color:#6b7280;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;transition:all .15s;white-space:nowrap}
.tab-btn i{font-size:16px}
.tab-btn .tab-count{background:#f3f4f6;color:#6b7280;font-size:11px;font-weight:800;padding:2px 8px;border-radius:20px;transition:all .15s}
.tab-btn.active{background:#3d4bff;color:#fff}
.tab-btn.active .tab-count{background:rgba(255,255,255,.25);color:#fff}
.tab-btn:not(.active):hover{background:#f9fafb;color:#374151}
.tab-panel{display:none}.tab-panel.active{display:block}

/* Report cards */
.report-list{display:flex;flex-direction:column;gap:16px}
.rcard{background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden}
.rcard.is-pending{border-left:4px solid #f59e0b}.rcard.is-reviewed{border-left:4px solid #16a34a}.rcard.is-dismissed{border-left:4px solid #9ca3af}
.rcard-head{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid #f0f1f3;background:#fafbff;flex-wrap:wrap;gap:8px}
.rcard-id{font-size:12px;font-weight:700;color:#9ca3af}.rcard-date{font-size:12px;color:#9ca3af}
.head-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.rcard-body{display:grid;grid-template-columns:1fr 1fr 1fr;gap:0}
.rcol{padding:18px 20px}.rcol+.rcol{border-left:1px solid #f0f1f3}
.col-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:10px;display:flex;align-items:center;gap:5px}
.col-label i{font-size:13px}
.col-divider{margin-top:16px;padding-top:14px;border-top:1px solid #f0f1f3}

/* Person */
.person{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.av{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;overflow:hidden}
.av img{width:100%;height:100%;object-fit:cover}
.av-blue{background:#eef0ff;color:#3d4bff}.av-purple{background:#f3e8ff;color:#7c3aed}.av-teal{background:#e0fdf4;color:#0d7377}
.p-name{font-size:13px;font-weight:700;color:#1a1d2e}.p-meta{font-size:11.5px;color:#6b7280;margin-top:2px}

.info-row{display:flex;align-items:flex-start;gap:7px;margin-bottom:6px;font-size:12.5px}
.info-row i{font-size:14px;color:#9ca3af;flex-shrink:0;margin-top:1px}
.info-val{color:#374151;line-height:1.4}.info-val.bold{font-weight:600}
.rental-tag{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;padding:4px 9px;border-radius:20px;margin-right:4px}
.rt-status{background:#e9f9f0;color:#16a34a}.rt-pending{background:#fff7e6;color:#a16207}

.bukti-wrap{margin-top:6px}
.bukti-img{width:100%;max-width:220px;border-radius:10px;border:1px solid #e5e7eb;cursor:pointer;transition:transform .15s;display:block}
.bukti-img:hover{transform:scale(1.02)}
.no-bukti{font-size:12px;color:#c0c4ce;font-style:italic}
.reason-txt{font-size:13.5px;font-weight:700;color:#1a1d2e;margin-bottom:5px}
.detail-txt{font-size:12.5px;color:#6b7280;line-height:1.5}

/* Badges */
.badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:5px 11px;border-radius:20px;white-space:nowrap}
.badge i{font-size:12px}
.b-green{background:#e9f9f0;color:#1a7a46;border:1px solid #a7f3d0}.b-amber{background:#fff7e6;color:#a16207;border:1px solid #fed7aa}
.b-gray{background:#f4f5f7;color:#6b7280;border:1px solid #d1d5db}.b-red{background:#fff5f5;color:#dc2626;border:1px solid #fecaca}
.b-orange{background:#fff4e6;color:#c2410c;border:1px solid #fdba74}.b-teal{background:#e0fdf4;color:#0d7377;border:1px solid #5eead4}

.spill{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;padding:3px 8px;border-radius:20px}
.sp-cool{background:#fff7e6;color:#a16207;border:1px solid #fed7aa}.sp-ban{background:#fff5f5;color:#dc2626;border:1px solid #fecaca}
.sp-cd-item{background:#e9f9f0;color:#16a34a;border:1px solid #a7f3d0;margin-top:4px;display:flex}

/* Refund section */
.refund-section{margin-top:14px;border-top:1px dashed #e5e7eb;padding-top:12px}
.refund-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:8px;display:flex;align-items:center;gap:5px}
.refund-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px}
.select-refund{padding:7px 10px;font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;font-weight:600;border:1px solid #d1d5db;border-radius:9px;background:#fff;color:#374151;outline:none;cursor:pointer}
.select-refund:focus{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.12)}
.refund-note{width:100%;padding:7px 10px;font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;border:1px solid #d1d5db;border-radius:9px;resize:vertical;outline:none;color:#374151}
.refund-note:focus{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.12)}
.refund-info-box{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:10px 13px;font-size:12px;color:#92400e;line-height:1.5}
.refund-info-box strong{display:block;margin-bottom:3px}

/* Bukti refund upload */
.bukti-upload-wrap{margin-top:8px}
.bukti-upload-label{font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;display:flex;align-items:center;gap:5px}
.bukti-upload-label i{font-size:14px;color:#9ca3af}
.bukti-upload-input{display:flex;align-items:center;gap:8px}
.bukti-upload-input input[type=file]{font-size:12px;font-family:inherit;color:#374151}
.bukti-upload-input input[type=file]::file-selector-button{background:#f3f4f6;border:1px solid #d1d5db;border-radius:7px;padding:5px 12px;font-family:inherit;font-size:11.5px;font-weight:600;color:#374151;cursor:pointer;transition:all .15s}
.bukti-upload-input input[type=file]::file-selector-button:hover{background:#e5e7eb}
.bukti-upload-preview{margin-top:6px}
.bukti-upload-preview img{max-width:180px;border-radius:8px;border:1px solid #e5e7eb}

/* Ganti rugi form box */
.gantirugi-box{margin-top:8px;background:#fff0f0;border:1px solid #fca5a5;border-radius:10px;padding:12px 14px;display:none}
.gantirugi-box.show{display:block}
.gantirugi-box-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#991b1b;margin-bottom:8px;display:flex;align-items:center;gap:5px}
.gantirugi-box-title i{font-size:13px}
.gantirugi-input{width:100%;padding:7px 10px;font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;border:1px solid #d1d5db;border-radius:9px;outline:none;color:#374151;margin-bottom:6px}
.gantirugi-input:focus{border-color:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.1)}
.gantirugi-amount{display:flex;align-items:center;gap:6px}
.gantirugi-amount span{font-size:13px;font-weight:700;color:#991b1b}
.gantirugi-amount input{width:140px;padding:7px 10px;font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;font-weight:600;border:1px solid #d1d5db;border-radius:9px;outline:none;color:#991b1b}
.gantirugi-amount input:focus{border-color:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.1)}

/* Tagihan display (card sudah diproses) */
.tagihan-box{background:#fff0f0;border:1px solid #fca5a5;border-radius:10px;padding:10px 14px;margin-top:8px}
.tagihan-box-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#991b1b;margin-bottom:5px;display:flex;align-items:center;gap:5px}
.tagihan-box-title i{font-size:12px}
.tagihan-row{display:flex;align-items:center;gap:7px;font-size:12.5px;color:#374151;margin-bottom:3px}
.tagihan-row i{font-size:14px;color:#ef4444;flex-shrink:0}
.tagihan-row .tagihan-amt{font-weight:800;color:#991b1b;font-size:14px}

/* Refund status display */
.refund-status-box{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:10px 14px;margin-top:8px}
.rsb-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a16207;margin-bottom:5px}
.rsb-row{display:flex;align-items:center;gap:7px;font-size:12.5px;color:#374151;margin-bottom:3px}
.rsb-row i{font-size:14px;color:#f59e0b;flex-shrink:0}
.rsb-row.done{color:#1a7a46}.rsb-row.done i{color:#16a34a}
.rsb-row .rsb-bukti{margin-top:4px}
.rsb-row .rsb-bukti img{max-width:160px;border-radius:8px;border:1px solid #e5e7eb;cursor:pointer}

/* Pencairan warning (card pending) */
.pencairan-block{margin-top:10px;background:#fef3c7;border:1px solid #fbbf24;border-radius:10px;padding:10px 14px;display:flex;align-items:flex-start;gap:8px}
.pencairan-block i{font-size:16px;color:#d97706;flex-shrink:0;margin-top:1px}
.pencairan-block div{font-size:12px;color:#92400e;line-height:1.5}
.pencairan-block strong{color:#78350f}

/* Card footer */
.rcard-foot{padding:16px 20px;border-top:1px solid #f0f1f3;background:#fafbff}
.foot-inner{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.foot-wa{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:12px}
.foot-warn{display:flex;align-items:flex-start;gap:7px;font-size:12px;color:#92400e;background:#fff7e6;border:1px solid #fde68a;border-radius:9px;padding:8px 12px;margin-bottom:10px}
.foot-warn i{flex-shrink:0;font-size:15px;margin-top:1px}

.btn{border:none;cursor:pointer;border-radius:9px;padding:8px 14px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;text-decoration:none;font-family:inherit;transition:all .14s}
.btn i{font-size:14px}
.btn-primary{background:#3d4bff;color:#fff}.btn-primary:hover{background:#2c38d4}
.btn-wa-penyewa{background:#25d366;color:#fff}.btn-wa-penyewa:hover{background:#1eba58}
.btn-wa-pemilik{background:#e9f9f0;color:#1a7a46;border:1px solid #a7f3d0}.btn-wa-pemilik:hover{background:#d1fae5}
.btn-disabled{background:#f3f4f6;color:#9ca3af;cursor:default}
.btn-sm{padding:6px 11px;font-size:11.5px}

.select-sanksi{padding:8px 12px;font-family:'Plus Jakarta Sans',sans-serif;font-size:12.5px;font-weight:600;border:1px solid #d1d5db;border-radius:9px;background:#fff;color:#374151;outline:none;min-width:260px;cursor:pointer}
.select-sanksi:focus{border-color:#3d4bff;box-shadow:0 0 0 3px rgba(61,75,255,.1)}
.reviewed-info{font-size:11.5px;color:#9ca3af;margin-top:4px}

.lb-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center}
.lb-overlay.open{display:flex}
.lb-overlay img{max-width:90vw;max-height:88vh;border-radius:12px;object-fit:contain}
.lb-close{position:absolute;top:18px;right:22px;color:#fff;font-size:28px;cursor:pointer;background:none;border:none;line-height:1}

.empty{text-align:center;padding:56px 20px;color:#9ca3af;font-size:14px;background:#fff;border:1px solid #e5e7eb;border-radius:16px}
.empty i{font-size:40px;display:block;margin-bottom:10px}

@media(max-width:1100px){.stats{grid-template-columns:repeat(2,1fr)}.rcard-body{grid-template-columns:1fr 1fr}.rcol:nth-child(3){border-left:none;border-top:1px solid #f0f1f3;grid-column:span 2}}
@media(max-width:700px){.main{margin-left:0}.content{padding:14px}.rcard-body{grid-template-columns:1fr}.rcol+.rcol{border-left:none;border-top:1px solid #f0f1f3}.rcol:nth-child(3){grid-column:span 1}}
</style>
</head>
<body>
<div class="admin-wrap">
<?php include 'sidebar.php'; ?>
<div class="main">
    <div class="topbar">
        <div><h1>Kelola Laporan</h1><p>Tinjau dan tindak laporan dari pengguna</p></div>
        <div class="tb-right">
            <span class="admin-pill">Admin</span>
            <div class="avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
        </div>
    </div>
    <div class="content">
        <div class="stats">
            <div class="sc"><div class="sc-icon ic-blue"><i class="ti ti-flag"></i></div><div><div class="sc-label">Total</div><div class="sc-val"><?= $total_all ?></div></div></div>
            <div class="sc"><div class="sc-icon ic-amber"><i class="ti ti-hourglass"></i></div><div><div class="sc-label">Pending</div><div class="sc-val"><?= $total_pending ?></div></div></div>
            <div class="sc"><div class="sc-icon ic-green"><i class="ti ti-circle-check"></i></div><div><div class="sc-label">Selesai</div><div class="sc-val"><?= $total_done ?></div></div></div>
            <div class="sc"><div class="sc-icon ic-gray"><i class="ti ti-circle-x"></i></div><div><div class="sc-label">Ditolak</div><div class="sc-val"><?= $total_dismiss ?></div></div></div>
        </div>

        <div class="tabs-bar">
            <button class="tab-btn active" onclick="switchTab('pending')"><i class="ti ti-hourglass"></i> Belum Selesai <span class="tab-count"><?= $total_pending ?></span></button>
            <button class="tab-btn" onclick="switchTab('history')"><i class="ti ti-history"></i> Riwayat <span class="tab-count"><?= $total_done + $total_dismiss ?></span></button>
        </div>

        <!-- ═══ TAB: BELUM SELESAI ═══ -->
        <div class="tab-panel active" id="tab-pending">
        <?php
        $pendingReports = array_filter($reports, fn($r) => $r['status'] === 'pending');
        if (empty($pendingReports)): ?>
            <div class="empty"><i class="ti ti-inbox"></i>Tidak ada laporan yang perlu ditinjau.</div>
        <?php else: ?>
        <div class="report-list">
        <?php foreach ($pendingReports as $r):
            $reporterBanned = isBanned($r['reporter_banned_until']);
            $pemilikBanned  = isBanned($r['pemilik_banned_until']);
            $penyewaBanned  = isBanned($r['penyewa_banned_until']);
            $reporterPermBan= isBannedPermanent($r['reporter_banned_until']);
            $pemilikPermBan = isBannedPermanent($r['pemilik_banned_until']);
            $penyewaPermBan = isBannedPermanent($r['penyewa_banned_until']);
            $sudahCair = ($r['status_pencairan'] ?? '') === 'sudah_dicairkan';
            $adaLaporan = ($r['status_pencairan'] ?? '') === 'ada_laporan';
            $itemGambar = null;
            if (!empty($r['item_gambar'])) { $arr = json_decode($r['item_gambar'], true); if (!empty($arr)) $itemGambar = '../uploads/' . $arr[0]; }
            $buktiUrl = !empty($r['bukti']) ? '../uploads/' . $r['bukti'] : null;
            $isReporterOwner = ((int)$r['reporter_id'] === (int)$r['pemilik_id']);
        ?>
        <div class="rcard is-pending">
            <div class="rcard-head">
                <div>
                    <span class="rcard-id">LAPORAN #<?= $r['id'] ?></span>
                    <span class="rcard-date" style="margin-left:10px;"><i class="ti ti-clock" style="font-size:12px;vertical-align:-1px;"></i> <?= date('d M Y, H:i', strtotime($r['created_at'])) ?></span>
                </div>
                <div class="head-right">
                    <?= statusBadge($r['status']) ?>
                    <?= refundBadge($r['status_refund'], $r['refund_ke']) ?>
                </div>
            </div>
            <div class="rcard-body">
                <!-- Kolom 1: Pelapor, Pemilik, Penyewa -->
                <div class="rcol">
                    <div class="col-label"><i class="ti ti-user"></i> Pelapor</div>
                    <div class="person">
                        <div class="av av-blue">
                            <?php if (!empty($r['reporter_foto']) && $r['reporter_foto'] !== 'default.png'): ?><img src="../uploads/<?= htmlspecialchars($r['reporter_foto']) ?>" alt=""><?php else: ?><?= strtoupper(substr($r['reporter_nama'] ?? '?', 0, 2)) ?><?php endif; ?>
                        </div>
                        <div>
                            <a href="user_detail.php?id=<?= $r['reporter_id'] ?>" class="p-name" style="color:#3d4bff;"><?= htmlspecialchars($r['reporter_nama']) ?></a>
                            <div class="p-meta"><?= htmlspecialchars($r['reporter_wa'] ?? '-') ?> <span style="font-size:10px;color:#9ca3af;margin-left:4px;">(<?= $isReporterOwner ? 'Pemilik' : 'Penyewa' ?>)</span></div>
                        </div>
                    </div>
                    <?php if ($reporterBanned): ?><span class="spill <?= $reporterPermBan ? 'sp-ban' : 'sp-cool' ?>"><i class="ti ti-<?= $reporterPermBan ? 'ban' : 'clock' ?>"></i> <?= $reporterPermBan ? 'Banned permanen' : 'Cooldown s/d ' . date('d M Y', strtotime($r['reporter_banned_until'])) ?></span><?php endif; ?>

                    <div class="col-divider"></div>
                    <div class="col-label"><i class="ti ti-building-store"></i> Pemilik Barang</div>
                    <div class="person">
                        <div class="av av-purple">
                            <?php if (!empty($r['pemilik_foto']) && $r['pemilik_foto'] !== 'default.png'): ?><img src="../uploads/<?= htmlspecialchars($r['pemilik_foto']) ?>" alt=""><?php else: ?><?= strtoupper(substr($r['pemilik_nama'] ?? '?', 0, 2)) ?><?php endif; ?>
                        </div>
                        <div>
                            <a href="user_detail.php?id=<?= $r['pemilik_id'] ?>" class="p-name" style="color:#7c3aed;"><?= htmlspecialchars($r['pemilik_nama'] ?? '-') ?></a>
                            <div class="p-meta"><?= htmlspecialchars($r['pemilik_wa'] ?? '-') ?></div>
                        </div>
                    </div>
                    <?php if ($pemilikBanned): ?><span class="spill <?= $pemilikPermBan ? 'sp-ban' : 'sp-cool' ?>"><i class="ti ti-<?= $pemilikPermBan ? 'ban' : 'clock' ?>"></i> <?= $pemilikPermBan ? 'Banned permanen' : 'Cooldown s/d ' . date('d M Y', strtotime($r['pemilik_banned_until'])) ?></span><?php endif; ?>

                    <div class="col-divider"></div>
                    <div class="col-label"><i class="ti ti-user"></i> Penyewa</div>
                    <div class="person">
                        <div class="av av-teal">
                            <?php if (!empty($r['penyewa_foto']) && $r['penyewa_foto'] !== 'default.png'): ?><img src="../uploads/<?= htmlspecialchars($r['penyewa_foto']) ?>" alt=""><?php else: ?><?= strtoupper(substr($r['penyewa_nama'] ?? '?', 0, 2)) ?><?php endif; ?>
                        </div>
                        <div>
                            <a href="user_detail.php?id=<?= $r['penyewa_id'] ?>" class="p-name" style="color:#0d7377;"><?= htmlspecialchars($r['penyewa_nama'] ?? '-') ?></a>
                            <div class="p-meta"><?= htmlspecialchars($r['penyewa_wa'] ?? '-') ?></div>
                        </div>
                    </div>
                    <?php if ($penyewaBanned): ?><span class="spill <?= $penyewaPermBan ? 'sp-ban' : 'sp-cool' ?>"><i class="ti ti-<?= $penyewaPermBan ? 'ban' : 'clock' ?>"></i> <?= $penyewaPermBan ? 'Banned permanen' : 'Cooldown s/d ' . date('d M Y', strtotime($r['penyewa_banned_until'])) ?></span><?php endif; ?>
                </div>

                <!-- Kolom 2 -->
                <div class="rcol">
                    <div class="col-label"><i class="ti ti-receipt"></i> Peminjaman #<?= $r['target_id'] ?></div>
                    <div class="info-row"><i class="ti ti-calendar"></i><span class="info-val"><?= date('d M Y', strtotime($r['tanggal_mulai'])) ?> &rarr; <?= date('d M Y', strtotime($r['tanggal_selesai'])) ?></span></div>
                    <div class="info-row"><i class="ti ti-coin"></i><span class="info-val bold">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></span></div>
                    <div class="info-row"><i class="ti ti-circle-dot"></i><span class="info-val">
                        <span class="rental-tag <?= $r['status_pinjam'] === 'selesai' ? 'rt-status' : 'rt-pending' ?>"><?= match($r['status_pinjam']) { 'selesai' => 'Selesai', 'sedang_dipinjam' => 'Sedang Dipinjam', default => 'Belum Mulai' } ?></span>
                        <?php if ($sudahCair): ?><span class="rental-tag" style="background:#e0f2fe;color:#0369a1;"><i class="ti ti-check" style="font-size:10px;"></i> Dana Cair</span><?php endif; ?>
                    </span></div>
                    <div style="margin-top:14px;"></div>
                    <div class="col-label"><i class="ti ti-box-seam"></i> Barang</div>
                    <div class="info-row"><i class="ti ti-tag"></i><span class="info-val bold"><?= htmlspecialchars($r['nama_barang'] ?? '-') ?></span></div>
                    <div class="info-row"><i class="ti ti-map-pin"></i><span class="info-val"><?= htmlspecialchars($r['item_lokasi'] ?? 'Lokasi tidak diisi') ?></span></div>
                    <?php if ($r['item_status'] === 'cooldown'): ?><span class="spill sp-cool sp-cd-item"><i class="ti ti-pause"></i> Barang sedang cooldown</span><?php endif; ?>
                    <?php if ($itemGambar): ?><div style="margin-top:8px;"><img src="<?= htmlspecialchars($itemGambar) ?>" alt="Foto barang" class="bukti-img" onclick="openLb(this.src)" style="max-width:140px;border-radius:8px;"></div><?php endif; ?>

                    <?php if ($adaLaporan): ?>
                    <div class="pencairan-block">
                        <i class="ti ti-alert-triangle"></i>
                        <div><strong>Pencairan ditahan</strong> — Laporan ini memblokir pencairan dana ke pemilik. Selesaikan laporan terlebih dahulu.</div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Kolom 3 -->
                <div class="rcol">
                    <div class="col-label"><i class="ti ti-flag"></i> Laporan</div>
                    <div class="reason-txt"><?= htmlspecialchars($r['reason']) ?></div>
                    <?php if (!empty($r['detail'])): ?><div class="detail-txt" style="margin-top:6px;"><?= nl2br(htmlspecialchars($r['detail'])) ?></div><?php endif; ?>
                    <div style="margin-top:14px;"></div>
                    <div class="col-label"><i class="ti ti-photo"></i> Bukti</div>
                    <?php if ($buktiUrl): ?>
                    <div class="bukti-wrap"><img src="<?= htmlspecialchars($buktiUrl) ?>" alt="Bukti laporan" class="bukti-img" onclick="openLb(this.src)"><div style="font-size:11px;color:#9ca3af;margin-top:4px;">Klik untuk perbesar</div></div>
                    <?php else: ?><div class="no-bukti">Tidak ada bukti dilampirkan</div><?php endif; ?>
                </div>
            </div>

            <div class="rcard-foot">
                <div class="foot-wa">
                    <?php if (!empty($r['pemilik_wa'])): ?><a href="<?= waLink($r['pemilik_wa'], $r['pemilik_nama'] ?? '', 'peminjaman #' . $r['target_id']) ?>" target="_blank" class="btn btn-wa-pemilik btn-sm"><i class="ti ti-brand-whatsapp"></i> WA Pemilik</a><?php endif; ?>
                    <?php if (!empty($r['penyewa_wa'])): ?><a href="<?= waLink($r['penyewa_wa'], $r['penyewa_nama'] ?? '', 'peminjaman #' . $r['target_id']) ?>" target="_blank" class="btn btn-wa-penyewa btn-sm"><i class="ti ti-brand-whatsapp"></i> WA Penyewa</a><?php endif; ?>
                </div>
                <div class="foot-warn"><i class="ti ti-alert-triangle"></i><div><strong>Perhatian:</strong> Sanksi ban permanen, hapus barang, dan cooldown user bersifat langsung dan tidak dapat dibatalkan. Tagihan ganti rugi akan ditambahkan ke jumlah pencairan ke pemilik.</div></div>

                <form method="POST" enctype="multipart/form-data" onsubmit="return konfirmSanksi(this)">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <div class="foot-inner" style="margin-bottom:12px;">
                        <select name="sanksi_option" class="select-sanksi" id="sanksi_<?= $r['id'] ?>">
                            <optgroup label="── Tanpa Sanksi ──">
                                <option value="none">Selesaikan tanpa Sanksi</option>
                                <option value="dismissed">Tolak / Batalkan Laporan</option>
                            </optgroup>
                            <optgroup label="── Sanksi Penyewa ──">
                                <option value="penyewa_cooldown">Cooldown Penyewa (7 hari)</option>
                                <option value="penyewa_banned">Ban Permanen Penyewa</option>
                            </optgroup>
                            <optgroup label="── Sanksi Pemilik ──">
                                <option value="pemilik_cooldown">Cooldown Pemilik + Semua Barangnya (7 hari)</option>
                                <option value="pemilik_banned">Ban Permanen Pemilik + Semua Barangnya</option>
                            </optgroup>
                            <optgroup label="── Sanksi Keduanya ──">
                                <option value="keduanya_cooldown">Cooldown Keduanya (7 hari)</option>
                                <option value="keduanya_banned">Ban Permanen Keduanya</option>
                            </optgroup>
                            <optgroup label="── Sanksi Barang ──">
                                <option value="barang_cooldown">Cooldown Barang Saja (7 hari)</option>
                                <option value="barang_hapus">Hapus Barang Permanen</option>
                                <option value="barang_hapus_pemilik_banned">Hapus Barang + Ban Permanen Pemilik</option>
                            </optgroup>
                            <optgroup label="── Tagihan ──">
                                <option value="tagihan_ganti_rugi">Tagihan Ganti Rugi (Pemilik terima dari Penyewa)</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="gantirugi-box" id="grbox_<?= $r['id'] ?>">
                        <div class="gantirugi-box-title"><i class="ti ti-file-invoice"></i> Tagihan Ganti Rugi untuk Pemilik</div>
                        <textarea name="tagihan_ganti_rugi" class="gantirugi-input" rows="2" placeholder="Jelaskan kerusakan (misal: layar retak, lecet, dsb.) — tagihan ini ditagihkan ke penyewa"></textarea>
                        <div class="gantirugi-amount"><span>Rp</span><input type="number" name="amount_ganti_rugi" min="0" placeholder="0"></div>
                    </div>

                    <div class="refund-section">
                        <div class="refund-title"><i class="ti ti-coin-euro" style="font-size:13px;"></i> Refund (Opsional)</div>
                        <?php if ($sudahCair): ?>
                        <div class="refund-info-box" style="background:#fff0f0;border-color:#fca5a5;color:#991b1b;"><strong><i class="ti ti-alert-circle"></i> Dana sudah dicairkan ke pemilik</strong>Refund ke penyewa harus dilakukan manual oleh admin.</div>
                        <?php else: ?>
                        <div class="refund-info-box"><strong><i class="ti ti-info-circle"></i> Mekanisme refund</strong>Dana masih di admin → refund 100% langsung bisa diproses.</div>
                        <?php endif; ?>
                        <div style="height:8px;"></div>
                        <div class="refund-row">
                            <select name="refund_option" class="select-refund" id="refund_<?= $r['id'] ?>">
                                <option value="tidak_ada">Tidak ada refund</option>
                                <option value="penyewa">Refund ke Penyewa (<?= htmlspecialchars($r['penyewa_nama'] ?? '-') ?>)</option>
                                <option value="pemilik">Refund ke Pemilik (<?= htmlspecialchars($r['pemilik_nama'] ?? '-') ?>)</option>
                            </select>
                        </div>
                        <textarea name="catatan_refund" class="refund-note" rows="2" placeholder="Catatan refund (opsional)"></textarea>
                        <div class="bukti-upload-wrap">
                            <div class="bukti-upload-label"><i class="ti ti-photo"></i> Bukti Refund (opsional)</div>
                            <div class="bukti-upload-input"><input type="file" name="bukti_refund_admin" accept="image/*" onchange="previewBukti(this, 'bprev_<?= $r['id'] ?>')"></div>
                            <div class="bukti-upload-preview" id="bprev_<?= $r['id'] ?>"></div>
                        </div>
                    </div>
                    <div style="margin-top:12px;"><button type="submit" class="btn btn-primary"><i class="ti ti-send"></i> Terapkan</button></div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
        </div>

        <!-- ═══ TAB: RIWAYAT ═══ -->
        <div class="tab-panel" id="tab-history">
        <?php
        $historyReports = array_filter($reports, fn($r) => $r['status'] !== 'pending');
        if (empty($historyReports)): ?>
            <div class="empty"><i class="ti ti-inbox"></i>Belum ada riwayat laporan.</div>
        <?php else: ?>
        <div class="report-list">
        <?php foreach ($historyReports as $r):
            $cardClass = ($r['status'] === 'reviewed') ? 'is-reviewed' : 'is-dismissed';
            $reporterBanned = isBanned($r['reporter_banned_until']);
            $pemilikBanned  = isBanned($r['pemilik_banned_until']);
            $penyewaBanned  = isBanned($r['penyewa_banned_until']);
            $reporterPermBan= isBannedPermanent($r['reporter_banned_until']);
            $pemilikPermBan = isBannedPermanent($r['pemilik_banned_until']);
            $penyewaPermBan = isBannedPermanent($r['penyewa_banned_until']);
            $sudahCair = ($r['status_pencairan'] ?? '') === 'sudah_dicairkan';
            $itemGambar = null;
            if (!empty($r['item_gambar'])) { $arr = json_decode($r['item_gambar'], true); if (!empty($arr)) $itemGambar = '../uploads/' . $arr[0]; }
            $buktiUrl = !empty($r['bukti']) ? '../uploads/' . $r['bukti'] : null;
            $buktiRefundUrl = !empty($r['bukti_refund_admin']) ? '../uploads/' . $r['bukti_refund_admin'] : null;
            $isReporterOwner = ((int)$r['reporter_id'] === (int)$r['pemilik_id']);
            $refundDone = ($r['status_refund'] ?? '') === 'selesai';
            $refundKe   = match($r['refund_ke'] ?? '') { 'penyewa' => 'Penyewa (' . htmlspecialchars($r['penyewa_nama'] ?? '-') . ')', 'pemilik' => 'Pemilik (' . htmlspecialchars($r['pemilik_nama'] ?? '-') . ')', default => '-' };
            $hasGR = !empty($r['ganti_rugi_amount']) && $r['ganti_rugi_amount'] > 0;
        ?>
        <div class="rcard <?= $cardClass ?>">
            <div class="rcard-head">
                <div>
                    <span class="rcard-id">LAPORAN #<?= $r['id'] ?></span>
                    <span class="rcard-date" style="margin-left:10px;"><i class="ti ti-clock" style="font-size:12px;vertical-align:-1px;"></i> <?= date('d M Y, H:i', strtotime($r['created_at'])) ?></span>
                </div>
                <div class="head-right">
                    <?= statusBadge($r['status']) ?>
                    <?= refundBadge($r['status_refund'], $r['refund_ke']) ?>
                    <?php if ($hasGR): ?><span class="badge b-red"><i class="ti ti-file-invoice"></i>Ganti Rugi Rp <?= number_format($r['ganti_rugi_amount'], 0, ',', '.') ?></span><?php endif; ?>
                </div>
            </div>
            <div class="rcard-body">
                <!-- Kolom 1 -->
                <div class="rcol">
                    <div class="col-label"><i class="ti ti-user"></i> Pelapor</div>
                    <div class="person">
                        <div class="av av-blue"><?php if (!empty($r['reporter_foto']) && $r['reporter_foto'] !== 'default.png'): ?><img src="../uploads/<?= htmlspecialchars($r['reporter_foto']) ?>" alt=""><?php else: ?><?= strtoupper(substr($r['reporter_nama'] ?? '?', 0, 2)) ?><?php endif; ?></div>
                        <div><a href="user_detail.php?id=<?= $r['reporter_id'] ?>" class="p-name" style="color:#3d4bff;"><?= htmlspecialchars($r['reporter_nama']) ?></a><div class="p-meta"><?= htmlspecialchars($r['reporter_wa'] ?? '-') ?> <span style="font-size:10px;color:#9ca3af;margin-left:4px;">(<?= $isReporterOwner ? 'Pemilik' : 'Penyewa' ?>)</span></div></div>
                    </div>
                    <?php if ($reporterBanned): ?><span class="spill <?= $reporterPermBan ? 'sp-ban' : 'sp-cool' ?>"><i class="ti ti-<?= $reporterPermBan ? 'ban' : 'clock' ?>"></i> <?= $reporterPermBan ? 'Banned permanen' : 'Cooldown' ?></span><?php endif; ?>

                    <div class="col-divider"></div>
                    <div class="col-label"><i class="ti ti-building-store"></i> Pemilik Barang</div>
                    <div class="person">
                        <div class="av av-purple"><?php if (!empty($r['pemilik_foto']) && $r['pemilik_foto'] !== 'default.png'): ?><img src="../uploads/<?= htmlspecialchars($r['pemilik_foto']) ?>" alt=""><?php else: ?><?= strtoupper(substr($r['pemilik_nama'] ?? '?', 0, 2)) ?><?php endif; ?></div>
                        <div><a href="user_detail.php?id=<?= $r['pemilik_id'] ?>" class="p-name" style="color:#7c3aed;"><?= htmlspecialchars($r['pemilik_nama'] ?? '-') ?></a><div class="p-meta"><?= htmlspecialchars($r['pemilik_wa'] ?? '-') ?></div></div>
                    </div>

                    <div class="col-divider"></div>
                    <div class="col-label"><i class="ti ti-user"></i> Penyewa</div>
                    <div class="person">
                        <div class="av av-teal"><?php if (!empty($r['penyewa_foto']) && $r['penyewa_foto'] !== 'default.png'): ?><img src="../uploads/<?= htmlspecialchars($r['penyewa_foto']) ?>" alt=""><?php else: ?><?= strtoupper(substr($r['penyewa_nama'] ?? '?', 0, 2)) ?><?php endif; ?></div>
                        <div><a href="user_detail.php?id=<?= $r['penyewa_id'] ?>" class="p-name" style="color:#0d7377;"><?= htmlspecialchars($r['penyewa_nama'] ?? '-') ?></a><div class="p-meta"><?= htmlspecialchars($r['penyewa_wa'] ?? '-') ?></div></div>
                    </div>
                </div>

                <!-- Kolom 2 -->
                <div class="rcol">
                    <div class="col-label"><i class="ti ti-receipt"></i> Peminjaman #<?= $r['target_id'] ?></div>
                    <div class="info-row"><i class="ti ti-calendar"></i><span class="info-val"><?= date('d M Y', strtotime($r['tanggal_mulai'])) ?> &rarr; <?= date('d M Y', strtotime($r['tanggal_selesai'])) ?></span></div>
                    <div class="info-row"><i class="ti ti-coin"></i><span class="info-val bold">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></span></div>
                    <div class="info-row"><i class="ti ti-circle-dot"></i><span class="info-val">
                        <span class="rental-tag <?= $r['status_pinjam'] === 'selesai' ? 'rt-status' : 'rt-pending' ?>"><?= match($r['status_pinjam']) { 'selesai' => 'Selesai', 'sedang_dipinjam' => 'Sedang Dipinjam', default => 'Belum Mulai' } ?></span>
                        <?php if ($sudahCair): ?><span class="rental-tag" style="background:#e0f2fe;color:#0369a1;"><i class="ti ti-check" style="font-size:10px;"></i> Dana Cair</span><?php endif; ?>
                    </span></div>
                    <div style="margin-top:14px;"></div>
                    <div class="col-label"><i class="ti ti-box-seam"></i> Barang</div>
                    <div class="info-row"><i class="ti ti-tag"></i><span class="info-val bold"><?= htmlspecialchars($r['nama_barang'] ?? '-') ?></span></div>
                    <div class="info-row"><i class="ti ti-map-pin"></i><span class="info-val"><?= htmlspecialchars($r['item_lokasi'] ?? 'Lokasi tidak diisi') ?></span></div>
                    <?php if ($itemGambar): ?><div style="margin-top:8px;"><img src="<?= htmlspecialchars($itemGambar) ?>" alt="Foto barang" class="bukti-img" onclick="openLb(this.src)" style="max-width:140px;border-radius:8px;"></div><?php endif; ?>

                    <?php if (!empty($r['status_refund']) && $r['status_refund'] !== 'tidak_ada'): ?>
                    <div class="refund-status-box">
                        <div class="rsb-label"><i class="ti ti-coin-euro"></i> Refund</div>
                        <div class="rsb-row <?= $refundDone ? 'done' : '' ?>"><i class="ti ti-<?= $refundDone ? 'circle-check' : 'clock' ?>"></i><span><?= $refundDone ? 'Selesai' : 'Menunggu diproses' ?> → <?= $refundKe ?></span></div>
                        <?php if (!empty($r['catatan_refund'])): ?><div class="rsb-row" style="margin-top:4px;"><i class="ti ti-notes"></i><span><?= htmlspecialchars($r['catatan_refund']) ?></span></div><?php endif; ?>
                        <?php if ($buktiRefundUrl): ?><div class="rsb-row rsb-bukti"><i class="ti ti-photo"></i><span><img src="<?= htmlspecialchars($buktiRefundUrl) ?>" alt="Bukti refund" onclick="openLb(this.src)"></span></div><?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($hasGR): ?>
                    <div class="tagihan-box">
                        <div class="tagihan-box-title"><i class="ti ti-file-invoice"></i> Tagihan Ganti Rugi untuk Pemilik</div>
                        <div class="tagihan-row"><i class="ti ti-notes"></i><span><?= nl2br(htmlspecialchars($r['tagihan_ganti_rugi'] ?? '')) ?></span></div>
                        <div class="tagihan-row" style="margin-top:4px;"><i class="ti ti-coin"></i><span class="tagihan-amt">Rp <?= number_format($r['ganti_rugi_amount'], 0, ',', '.') ?></span></div>
                        <?php if (!empty($r['ganti_rugi_deduction']) && $r['ganti_rugi_deduction'] > 0): ?>
                        <div class="tagihan-row" style="margin-top:6px;padding-top:6px;border-top:1px solid #fecaca;"><i class="ti ti-arrow-down-circle"></i><span style="font-size:11px;color:#6b7280;">Tambahan untuk pemilik: <strong style="color:#991b1b;">Rp <?= number_format($r['ganti_rugi_deduction'], 0, ',', '.') ?></strong></span></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Kolom 3 -->
                <div class="rcol">
                    <div class="col-label"><i class="ti ti-flag"></i> Laporan</div>
                    <div class="reason-txt"><?= htmlspecialchars($r['reason']) ?></div>
                    <?php if (!empty($r['detail'])): ?><div class="detail-txt" style="margin-top:6px;"><?= nl2br(htmlspecialchars($r['detail'])) ?></div><?php endif; ?>
                    <div style="margin-top:14px;"></div>
                    <div class="col-label"><i class="ti ti-photo"></i> Bukti</div>
                    <?php if ($buktiUrl): ?>
                    <div class="bukti-wrap"><img src="<?= htmlspecialchars($buktiUrl) ?>" alt="Bukti laporan" class="bukti-img" onclick="openLb(this.src)"><div style="font-size:11px;color:#9ca3af;margin-top:4px;">Klik untuk perbesar</div></div>
                    <?php else: ?><div class="no-bukti">Tidak ada bukti dilampirkan</div><?php endif; ?>
                </div>
            </div>
            <div class="rcard-foot">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <span class="btn btn-disabled btn-sm"><i class="ti ti-lock"></i> Terproses</span>
                    <?php if (!empty($r['reviewed_at'])): ?><span class="reviewed-info">Diproses <?= date('d M Y, H:i', strtotime($r['reviewed_at'])) ?><?php if (!empty($r['reviewed_by'])): ?> oleh admin #<?= $r['reviewed_by'] ?><?php endif; ?></span><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
        </div>

    </div>
</div>
</div>

<div class="lb-overlay" id="lb" onclick="closeLb()"><button class="lb-close" onclick="closeLb()">&times;</button><img id="lb-img" src="" alt="Preview"></div>

<script>
function openLb(s){document.getElementById('lb-img').src=s;document.getElementById('lb').classList.add('open')}
function closeLb(){document.getElementById('lb').classList.remove('open')}
function switchTab(t){document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));if(t==='pending'){document.querySelector('.tab-btn:first-child').classList.add('active');document.getElementById('tab-pending').classList.add('active')}else{document.querySelector('.tab-btn:last-child').classList.add('active');document.getElementById('tab-history').classList.add('active')}}
function previewBukti(i,p){const b=document.getElementById(p);if(i.files&&i.files[0]){const r=new FileReader();r.onload=function(e){b.innerHTML='<img src="'+e.target.result+'" alt="Preview">'};r.readAsDataURL(i.files[0])}else{b.innerHTML=''}}
document.querySelectorAll('.select-sanksi').forEach(s=>{s.addEventListener('change',function(){const id=this.id.replace('sanksi_','');const g=document.getElementById('grbox_'+id);if(g){if(this.value==='tagihan_ganti_rugi'){g.classList.add('show')}else{g.classList.remove('show')}}})});
const sanksiMsg={none:'Selesaikan laporan ini tanpa memberikan sanksi?',dismissed:'Tolak laporan ini? (tidak ada sanksi dan tidak ada refund)',penyewa_cooldown:'Berikan cooldown 7 hari kepada penyewa?',penyewa_banned:'Ban PERMANEN penyewa? Tindakan ini tidak dapat dibatalkan.',pemilik_cooldown:'Berikan cooldown 7 hari kepada pemilik + semua barangnya akan ikut cooldown?',pemilik_banned:'Ban PERMANEN pemilik? Semua barangnya akan ikut dinonaktifkan permanen.',keduanya_cooldown:'Berikan cooldown 7 hari kepada KEDUA pihak?',keduanya_banned:'Ban PERMANEN KEDUA pihak? Tindakan ini tidak dapat dibatalkan.',barang_cooldown:'Cooldown barang yang dilaporkan selama 7 hari?',barang_hapus:'HAPUS PERMANEN barang yang dilaporkan?',barang_hapus_pemilik_banned:'HAPUS barang permanen + Ban PERMANEN pemiliknya?',tagihan_ganti_rugi:'Buat tagihan ganti rugi untuk pemilik? Pemilik akan menerima tambahan ini dari penyewa.'};
const refundMsg={tidak_ada:'',penyewa:' + Refund akan dikirim ke PENYEWA.',pemilik:' + Refund akan dikembalikan ke PEMILIK.'};
function konfirmSanksi(f){const s=f.querySelector('select[name="sanksi_option"]').value;const r=f.querySelector('select[name="refund_option"]').value;const b=sanksiMsg[s]??'Proses laporan ini?';const m=refundMsg[r]??'';return confirm(b+(r!=='tidak_ada'?'\n\n'+m:''))}
</script>
</body>
</html>
