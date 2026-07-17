<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php?page=login");
    exit;
}

// Handle update status laporan & Eksekusi Aksi Kelanjutan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int) ($_POST['id'] ?? 0);
    $action   = $_POST['action'] ?? ''; // bisa 'reviewed' (diterima) atau 'dismissed' (ditolak/dibatalkan)
    $sanksi   = $_POST['sanksi_option'] ?? ''; 
    $admin_id = (int) ($_SESSION['user'] ?? 0);

    $allowed_status = ['reviewed', 'dismissed'];

    if ($id > 0 && in_array($action, $allowed_status)) {
        // 1. UPDATE STATUS UTAMA LAPORAN
        $stmt = $conn->prepare("
            UPDATE reports
            SET status = :status, reviewed_at = NOW(), reviewed_by = :admin_id
            WHERE id = :id
        ");
        $stmt->execute([
            ':status'   => $action,
            ':admin_id' => $admin_id,
            ':id'       => $id
        ]);

        // 2. JIKA LAPORAN DITERIMA ('reviewed'), CEK SANKSI APA YANG DIPILIH
        if ($action === 'reviewed') {
            $stmtReport = $conn->prepare("SELECT type, target_id FROM reports WHERE id = ?");
            $stmtReport->execute([$id]);
            $reportData = $stmtReport->fetch(PDO::FETCH_ASSOC);

            if ($reportData) {
                $target_id = (int)$reportData['target_id'];

                // Opsi A: Suspend Barang & Cooldown Pemilik (Hanya untuk tipe barang)
                if ($reportData['type'] === 'barang' && $sanksi === 'suspend_barang') {
                    // Batalkan/Reject barang
                    $stmtItem = $conn->prepare("UPDATE items SET status = 'rejected' WHERE id = ?");
                    $stmtItem->execute([$target_id]);

                    // Dapatkan ID pemilik barang
                    $stmtOwner = $conn->prepare("SELECT user_id FROM items WHERE id = ?");
                    $stmtOwner->execute([$target_id]);
                    $owner_id = $stmtOwner->fetchColumn();

                    if ($owner_id) {
                        // Cooldown pemilik dengan mengubah status ke pending
                        $stmtUser = $conn->prepare("UPDATE users SET status = 'pending' WHERE id = ?");
                        $stmtUser->execute([$owner_id]);
                    }
                } 
                
                // Opsi B: Refund Dana Peminjaman (Hanya untuk tipe peminjaman)
                if ($reportData['type'] === 'peminjaman' && $sanksi === 'refund_dana') {
                    // Kembalikan status pembayaran ke pending & pinjam selesai (tanda batalkan transaksi)
                    $stmtRental = $conn->prepare("UPDATE rentals SET status_pembayaran = 'pending', status_pinjam = 'selesai' WHERE id = ?");
                    $stmtRental->execute([$target_id]);
                }
            }
        }
    }

    header("Location: reports.php");
    exit;
}

// Ambil semua laporan + info pelapor beserta nomor WhatsApp dari tabel users
$reports = $conn->query("
    SELECT r.*, u.username AS reporter_name, u.nomor_wa AS reporter_phone
    FROM reports r
    JOIN users u ON r.reporter_id = u.id
    ORDER BY
        CASE WHEN r.status = 'pending' THEN 1 
             WHEN r.status = 'reviewed' THEN 2 
             ELSE 3 END,
        r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Lengkapi info target label
foreach ($reports as &$rep) {
    if ($rep['type'] === 'barang') {
        $stmt = $conn->prepare("SELECT nama_barang FROM items WHERE id = ?");
        $stmt->execute([$rep['target_id']]);
        $nama = $stmt->fetchColumn();
        $rep['target_label'] = $nama ?: 'Barang tidak ditemukan (#' . $rep['target_id'] . ')';
    } elseif ($rep['type'] === 'peminjaman') {
        $stmt = $conn->prepare("
            SELECT i.nama_barang
            FROM rentals rt
            JOIN items i ON rt.item_id = i.id
            WHERE rt.id = ?
        ");
        $stmt->execute([$rep['target_id']]);
        $nama = $stmt->fetchColumn();
        $rep['target_label'] = 'Transaksi #' . $rep['target_id'] . ($nama ? ' - ' . $nama : ' (data dihapus)');
    } else {
        $rep['target_label'] = 'User ID #' . $rep['target_id'];
    }
}
unset($rep);

$total_reports = count($reports);
$total_pending = count(array_filter($reports, fn($r) => $r['status'] === 'pending'));
$total_selesai = count(array_filter($reports, fn($r) => $r['status'] === 'reviewed'));
$total_ditolak = count(array_filter($reports, fn($r) => $r['status'] === 'dismissed'));

function reportStatusBadge($status) {
    if ($status === 'reviewed') {
        return '<span class="badge badge-approved"><i class="ti ti-circle-check"></i> Selesai</span>';
    } elseif ($status === 'dismissed') {
        return '<span class="badge badge-rejected"><i class="ti ti-circle-x"></i> Ditolak</span>';
    }
    return '<span class="badge badge-pending"><i class="ti ti-clock"></i> Pending</span>';
}

function typeBadge($type) {
    if ($type === 'barang') {
        return '<span class="role-pill"><i class="ti ti-box-seam"></i> Barang</span>';
    } elseif ($type === 'peminjaman') {
        return '<span class="role-pill"><i class="ti ti-receipt"></i> Peminjaman</span>';
    }
    return '<span class="role-pill"><i class="ti ti-user"></i> User</span>';
}

function reporterInitial($name) {
    return strtoupper(substr($name ?? '?', 0, 2));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Laporan - ItemLend Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f4f5f7;
            color: #1a1d2e;
            min-height: 100vh;
        }

        .admin-wrap {
            display: flex;
            min-height: 100vh;
        }

        .main {
            margin-left: 220px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 0 28px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-left h1 { font-size: 17px; font-weight: 600; }
        .topbar-left p  { font-size: 12px; color: #6b7280; margin-top: 1px; }

        .topbar-right { display: flex; align-items: center; gap: 12px; }

        .admin-pill {
            background: #eef0ff; color: #3d4bff;
            font-size: 12px; font-weight: 600;
            padding: 4px 12px; border-radius: 20px;
        }

        .avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: #3d4bff; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 600;
        }

        .content { padding: 24px 28px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 18px;
        }

        .stat-label { font-size: 12px; color: #6b7280; margin-bottom: 6px; }
        .stat-value { font-size: 28px; font-weight: 700; line-height: 1; }

        .table-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
        }

        .table-header {
            padding: 18px 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .table-title { font-size: 15px; font-weight: 700; }
        .table-sub   { font-size: 12px; color: #6b7280; margin-top: 2px; }

        table { width: 100%; border-collapse: collapse; }

        thead tr { background: #f8f9fb; border-bottom: 1px solid #e5e7eb; }

        thead th {
            padding: 12px 16px;
            font-size: 11.5px; font-weight: 700; color: #6b7280;
            text-align: left; letter-spacing: 0.03em; text-transform: uppercase;
            white-space: nowrap;
        }

        tbody tr { border-bottom: 1px solid #f0f1f3; transition: background 0.12s; }
        tbody tr:hover { background: #fafbff; }
        tbody tr:last-child { border-bottom: none; }

        tbody td { padding: 14px 16px; font-size: 13.5px; vertical-align: top; }

        .reporter-cell { display: flex; align-items: center; gap: 10px; }

        .reporter-av {
            width: 34px; height: 34px; border-radius: 50%;
            background: #eef0ff; color: #3d4bff;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; flex-shrink: 0;
        }

        .reporter-name { font-size: 13.5px; font-weight: 600; }
        .reporter-date { font-size: 11.5px; color: #6b7280; margin-top: 2px; }

        .role-pill {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 10px; border-radius: 20px;
            background: #f3f4f6; color: #374151;
            font-size: 11.5px; font-weight: 600;
        }

        .target-label { font-weight: 600; max-width: 180px; }
        .reason-text { max-width: 200px; }
        .detail-text {
            max-width: 220px; color: #6b7280; font-size: 12.5px;
            white-space: normal; word-break: break-word;
        }
        .detail-empty { color: #c0c4ce; font-size: 12px; }

        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11px; font-weight: 700;
            padding: 5px 10px; border-radius: 20px; white-space: nowrap;
        }
        .badge-approved { background: #e9f9f0; color: #1a7a46; border: 1px solid #a7f3d0; }
        .badge-pending  { background: #fff7e6; color: #cc7a00; border: 1px solid #fed7aa; }
        .badge-review   { background: #eff6ff; color: #2563eb; border: 1px solid #93c5fd; }
        .badge-rejected { background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; }

        .actions { display: flex; flex-direction: column; gap: 6px; }
        .btn-row { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }

        .btn {
            border: none; cursor: pointer; border-radius: 8px;
            padding: 7px 12px; font-size: 12px; font-weight: 700;
            display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
            text-decoration: none;
        }
        .btn-review  { background: #eff6ff; color: #2563eb; border: 1px solid #93c5fd; }
        .btn-review:hover { background: #dbeafe; }
        .btn-approve { background: #3d4bff; color: #fff; }
        .btn-approve:hover { background: #2c38d4; }
        .btn-reject  { background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; }
        .btn-reject:hover { background: #fee2e2; }
        .btn-wa { background: #25d366; color: #fff; margin-bottom: 4px; }
        .btn-wa:hover { background: #20ba5a; }
        .btn-disabled { background: #f3f4f6; color: #9ca3af; cursor: default; }

        /* Style Baru Dropdown Pilihan Kelanjutan */
        .select-sanksi {
            padding: 7px 10px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background-color: #fff;
            color: #374151;
            outline: none;
            min-width: 210px;
            cursor: pointer;
        }
        .select-sanksi:focus {
            border-color: #3d4bff;
        }

        .empty-state { text-align: center; padding: 48px 20px; color: #9ca3af; font-size: 13px; }

        @media (max-width: 1100px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .hide-md { display: none; }
        }

        @media (max-width: 600px) {
            .main { margin-left: 0; }
            .content { padding: 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .table-card { overflow-x: auto; }
        }
    </style>
</head>
<body>
<div class="admin-wrap">

    <?php include 'sidebar.php'; ?>

    <div class="main">

        <div class="topbar">
            <div class="topbar-left">
                <h1>Kelola Laporan</h1>
                <p>Laporan dari user terkait barang atau transaksi bermasalah</p>
            </div>
            <div class="topbar-right">
                <span class="admin-pill">Admin</span>
                <div class="avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
            </div>
        </div>

        <div class="content">

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Laporan</div>
                    <div class="stat-value"><?= $total_reports ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending</div>
                    <div class="stat-value"><?= $total_pending ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Selesai</div>
                    <div class="stat-value"><?= $total_selesai ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Ditolak</div>
                    <div class="stat-value"><?= $total_ditolak ?></div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">Daftar Laporan</div>
                    <div class="table-sub">Laporan pending ditampilkan paling atas</div>
                </div>

                <?php if (empty($reports)): ?>
                    <div class="empty-state">Belum ada laporan masuk.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Pelapor</th>
                                <th>Tipe</th>
                                <th>Target</th>
                                <th class="hide-md">Alasan</th>
                                <th class="hide-md">Detail</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $rep): ?>
                                <tr>
                                    <td>
                                        <div class="reporter-cell">
                                            <div class="reporter-av"><?= reporterInitial($rep['reporter_name']) ?></div>
                                            <div>
                                                <div class="reporter-name"><?= htmlspecialchars($rep['reporter_name']) ?></div>
                                                <div class="reporter-date"><?= date('d M Y, H:i', strtotime($rep['created_at'])) ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <td><?= typeBadge($rep['type']) ?></td>

                                    <td>
                                        <div class="target-label"><?= htmlspecialchars($rep['target_label']) ?></div>
                                    </td>

                                    <td class="hide-md">
                                        <div class="reason-text"><?= htmlspecialchars($rep['reason']) ?></div>
                                    </td>

                                    <td class="hide-md">
                                        <?php if (!empty($rep['detail'])): ?>
                                            <div class="detail-text"><?= htmlspecialchars($rep['detail']) ?></div>
                                        <?php else: ?>
                                            <span class="detail-empty">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= reportStatusBadge($rep['status']) ?></td>

                                    <td>
                                        <div class="actions">
                                            <!-- LINK TOMBOL HUBUNGI VIA WHATSAPP (AKTIF SAAT PENDING) -->
                                            <?php if ($rep['status'] === 'pending'): ?>
                                                <?php 
                                                    $clean_phone = preg_replace('/[^0-9]/', '', $rep['reporter_phone'] ?? '');
                                                    if (substr($clean_phone, 0, 2) === '08') {
                                                        $clean_phone = '628' . substr($clean_phone, 2);
                                                    }
                                                    $pesan_wa = urlencode("Halo " . $rep['reporter_name'] . ", saya Admin ItemLend ingin menindaklanjuti laporan Anda terkait " . $rep['type'] . " (" . $rep['target_label'] . ") dengan alasan: " . $rep['reason']);
                                                    $link_wa = "https://wa.me/" . $clean_phone . "?text=" . $pesan_wa;
                                                ?>
                                                <?php if(!empty($clean_phone)): ?>
                                                    <div>
                                                        <a href="<?= $link_wa ?>" target="_blank" class="btn btn-wa">
                                                            <i class="ti ti-brand-whatsapp"></i> Hubungi Pelapor
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <!-- OPSI EKSEKUSI FORM SATU PINTU (HANYA MUNCUL JIKA PENDING) -->
                                            <?php if ($rep['status'] === 'pending'): ?>
                                                <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengeksekusi tindakan kelanjutan laporan ini?')">
                                                    <input type="hidden" name="id" value="<?= $rep['id'] ?>">
                                                    
                                                    <div class="btn-row">
                                                        <!-- Dropdown Pilihan Kelanjutan Keluhan -->
                                                        <select name="sanksi_option" class="select-sanksi" required onchange="this.form.action.value = (this.value === 'dismissed' ? 'dismissed' : 'reviewed')">
                                                            <option value="none">Selesaikan tanpa Sanksi</option>
                                                            
                                                            <?php if ($rep['type'] === 'barang'): ?>
                                                                <option value="suspend_barang">Selesai + Suspend Barang & Cooldown</option>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($rep['type'] === 'peminjaman'): ?>
                                                                <option value="refund_dana">Selesai + Lakukan Refund Dana</option>
                                                            <?php endif; ?>
                                                            
                                                            <option value="dismissed">Batalkan / Tolak Laporan ini</option>
                                                        </select>

                                                        <!-- Input Hidden untuk Aksi Utama Status di Database -->
                                                        <input type="hidden" name="action" value="reviewed">
                                                        
                                                        <button type="submit" class="btn btn-approve">
                                                            <i class="ti ti-send"></i> Proses
                                                        </button>
                                                    </div>
                                                </form>
                                            <?php else: ?>
                                                <!-- Jika status sudah diproses (reviewed/dismissed) -->
                                                <span class="btn btn-disabled">
                                                    <i class="ti ti-lock"></i> Terproses
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            </div>

        </div>
    </div>
</div>
</body>
</html>