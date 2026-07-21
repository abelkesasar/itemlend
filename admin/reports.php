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
    $action   = $_POST['action'] ?? '';
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

        // 2. JIKA LAPORAN DITERIMA, CEK SANKSI
        if ($action === 'reviewed') {
            $stmtReport = $conn->prepare("SELECT type, target_id FROM reports WHERE id = ?");
            $stmtReport->execute([$id]);
            $reportData = $stmtReport->fetch(PDO::FETCH_ASSOC);

            if ($reportData) {
                $target_id = (int)$reportData['target_id'];

                // Opsi A: Hapus Barang & Cooldown Pemilik
                if ($reportData['type'] === 'barang' && $sanksi === 'suspend_barang') {
                    $stmtItem = $conn->prepare("UPDATE items SET status = 'deleted' WHERE id = ?");
                    $stmtItem->execute([$target_id]);

                    $stmtOwner = $conn->prepare("SELECT user_id FROM items WHERE id = ?");
                    $stmtOwner->execute([$target_id]);
                    $owner_id = $stmtOwner->fetchColumn();

                    if ($owner_id) {
                        $stmtUser = $conn->prepare("UPDATE users SET status = 'pending' WHERE id = ?");
                        $stmtUser->execute([$owner_id]);
                    }
                }

                // Opsi B: Refund Dana Peminjaman
                if ($reportData['type'] === 'peminjaman' && $sanksi === 'refund_dana') {
                    $stmtRental = $conn->prepare("UPDATE rentals SET status_pembayaran = 'pending', status_pinjam = 'selesai' WHERE id = ?");
                    $stmtRental->execute([$target_id]);
                }
            }
        }
    }

    header("Location: reports.php");
    exit;
}

// Ambil semua laporan + info pelapor
$reports = $conn->query("
    SELECT r.*, u.username AS reporter_name, u.nomor_wa AS reporter_phone, u.id AS reporter_user_id
    FROM reports r
    JOIN users u ON r.reporter_id = u.id
    ORDER BY
        CASE WHEN r.status = 'pending' THEN 1
             WHEN r.status = 'reviewed' THEN 2
             ELSE 3 END,
        r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Lengkapi info target label + link + owner
foreach ($reports as &$rep) {
    $rep['owner_name']  = null;
    $rep['owner_phone'] = null;
    $rep['owner_id']    = null;

    if ($rep['type'] === 'barang') {
        $stmt = $conn->prepare("
            SELECT i.id, i.nama_barang, i.user_id,
                   u.username AS owner_name, u.nomor_wa AS owner_phone
            FROM items i
            LEFT JOIN users u ON i.user_id = u.id
            WHERE i.id = ?
        ");
        $stmt->execute([$rep['target_id']]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        $rep['target_label'] = $item ? $item['nama_barang'] : 'Barang tidak ditemukan (#' . $rep['target_id'] . ')';
        $rep['target_link']  = $item ? '../index.php?page=detail&id=' . $item['id'] : null;
        $rep['owner_name']   = $item['owner_name'] ?? null;
        $rep['owner_phone']  = $item['owner_phone'] ?? null;
        $rep['owner_id']     = $item['user_id'] ?? null;

    } elseif ($rep['type'] === 'peminjaman') {
        $stmt = $conn->prepare("
            SELECT rt.id, rt.user_id AS renter_id,
                   i.nama_barang, i.user_id AS owner_id,
                   u.username AS owner_name, u.nomor_wa AS owner_phone
            FROM rentals rt
            JOIN items i ON rt.item_id = i.id
            LEFT JOIN users u ON i.user_id = u.id
            WHERE rt.id = ?
        ");
        $stmt->execute([$rep['target_id']]);
        $rental = $stmt->fetch(PDO::FETCH_ASSOC);

        $rep['target_label'] = 'Transaksi #' . $rep['target_id'] . ($rental ? ' - ' . $rental['nama_barang'] : ' (data dihapus)');
        $rep['target_link']  = null;
        $rep['owner_name']   = $rental['owner_name'] ?? null;
        $rep['owner_phone']  = $rental['owner_phone'] ?? null;
        $rep['owner_id']     = $rental['owner_id'] ?? null;

    } else {
        // type = user
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
        $stmt->execute([$rep['target_id']]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        $rep['target_label'] = $targetUser ? $targetUser['username'] : 'User #' . $rep['target_id'];
        $rep['target_link']  = $targetUser ? 'user_detail.php?id=' . $targetUser['id'] : null;
    }
}
unset($rep);

$total_reports = count($reports);
$total_pending = count(array_filter($reports, fn($r) => $r['status'] === 'pending'));
$total_selesai = count(array_filter($reports, fn($r) => $r['status'] === 'reviewed'));
$total_ditolak = count(array_filter($reports, fn($r) => $r['status'] === 'dismissed'));

function waLink(string $phone, string $nama, string $konteks): string {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($clean, '0')) {
        $clean = '62' . substr($clean, 1);
    }
    $pesan = urlencode("Halo $nama, saya Admin ItemLend ingin menindaklanjuti laporan terkait $konteks.");
    return "https://wa.me/{$clean}?text={$pesan}";
}

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

        /* Pelapor cell */
        .reporter-cell { display: flex; align-items: center; gap: 10px; }

        .reporter-av {
            width: 34px; height: 34px; border-radius: 50%;
            background: #eef0ff; color: #3d4bff;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; flex-shrink: 0;
        }

        .reporter-name {
            font-size: 13.5px; font-weight: 600;
            color: #3d4bff; text-decoration: none;
        }

        .reporter-name:hover { text-decoration: underline; }

        .reporter-date { font-size: 11.5px; color: #6b7280; margin-top: 2px; }

        /* Target */
        .target-link {
            font-weight: 600;
            color: #3d4bff;
            text-decoration: none;
            font-size: 13.5px;
            max-width: 180px;
            display: inline-block;
        }

        .target-link:hover { text-decoration: underline; }

        .target-plain {
            font-weight: 600;
            font-size: 13.5px;
            max-width: 180px;
            display: inline-block;
            color: #374151;
        }

        .role-pill {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 10px; border-radius: 20px;
            background: #f3f4f6; color: #374151;
            font-size: 11.5px; font-weight: 600;
        }

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
        .badge-rejected { background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; }

        .actions { display: flex; flex-direction: column; gap: 6px; }
        .btn-row { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }

        /* Dua tombol WA berdampingan */
        .wa-row {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .btn {
            border: none; cursor: pointer; border-radius: 8px;
            padding: 7px 12px; font-size: 12px; font-weight: 700;
            display: inline-flex; align-items: center; gap: 5px;
            white-space: nowrap; text-decoration: none;
        }

        .btn-approve { background: #3d4bff; color: #fff; }
        .btn-approve:hover { background: #2c38d4; }

        /* Hijau tua untuk pelapor, hijau muda untuk pemilik */
        .btn-wa-pelapor {
            background: #25d366; color: #fff;
        }
        .btn-wa-pelapor:hover { background: #20ba5a; }

        .btn-wa-pemilik {
            background: #e9f9f0; color: #1a7a46;
            border: 1px solid #a7f3d0;
        }
        .btn-wa-pemilik:hover { background: #d1fae5; }

        .btn-disabled { background: #f3f4f6; color: #9ca3af; cursor: default; border: none; }

        .select-sanksi {
            padding: 7px 10px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 12px; font-weight: 600;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background-color: #fff;
            color: #374151;
            outline: none;
            min-width: 210px;
            cursor: pointer;
        }

        .select-sanksi:focus { border-color: #3d4bff; }

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
                                    <!-- PELAPOR -->
                                    <td>
                                        <div class="reporter-cell">
                                            <div class="reporter-av"><?= reporterInitial($rep['reporter_name']) ?></div>
                                            <div>
                                                <a href="user_detail.php?id=<?= $rep['reporter_user_id'] ?>" class="reporter-name">
                                                    <?= htmlspecialchars($rep['reporter_name']) ?>
                                                </a>
                                                <div class="reporter-date"><?= date('d M Y, H:i', strtotime($rep['created_at'])) ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- TIPE -->
                                    <td><?= typeBadge($rep['type']) ?></td>

                                    <!-- TARGET -->
                                    <td>
                                        <?php if (!empty($rep['target_link'])): ?>
                                            <a href="<?= htmlspecialchars($rep['target_link']) ?>"
                                               class="target-link"
                                               <?= $rep['type'] === 'barang' ? 'target="_blank"' : '' ?>>
                                                <?= htmlspecialchars($rep['target_label']) ?>
                                                <i class="ti ti-external-link" style="font-size:11px;vertical-align:middle;"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="target-plain"><?= htmlspecialchars($rep['target_label']) ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- ALASAN -->
                                    <td class="hide-md">
                                        <div class="reason-text"><?= htmlspecialchars($rep['reason']) ?></div>
                                    </td>

                                    <!-- DETAIL -->
                                    <td class="hide-md">
                                        <?php if (!empty($rep['detail'])): ?>
                                            <div class="detail-text"><?= htmlspecialchars($rep['detail']) ?></div>
                                        <?php else: ?>
                                            <span class="detail-empty">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- STATUS -->
                                    <td><?= reportStatusBadge($rep['status']) ?></td>

                                    <!-- AKSI -->
                                    <td>
                                        <div class="actions">
                                            <?php if ($rep['status'] === 'pending'): ?>

                                                <!-- Tombol WA: Pelapor + Pemilik berdampingan -->
                                                <div class="wa-row">
                                                    <?php if (!empty($rep['reporter_phone'])): ?>
                                                        <a href="<?= waLink($rep['reporter_phone'], $rep['reporter_name'], $rep['target_label']) ?>"
                                                           target="_blank" class="btn btn-wa-pelapor">
                                                            <i class="ti ti-brand-whatsapp"></i> Pelapor
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if (!empty($rep['owner_phone'])): ?>
                                                        <a href="<?= waLink($rep['owner_phone'], $rep['owner_name'], $rep['target_label']) ?>"
                                                           target="_blank" class="btn btn-wa-pemilik">
                                                            <i class="ti ti-brand-whatsapp"></i> Pemilik
                                                        </a>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Form Eksekusi -->
                                                <form method="POST" onsubmit="return confirmAksi(this)">
                                                    <input type="hidden" name="id" value="<?= $rep['id'] ?>">
                                                    <input type="hidden" name="action" value="reviewed" id="action_<?= $rep['id'] ?>">

                                                    <div class="btn-row">
                                                        <select name="sanksi_option" class="select-sanksi" id="sanksi_<?= $rep['id'] ?>"
                                                                onchange="updateAction(<?= $rep['id'] ?>, this.value)">
                                                            <option value="none">Selesaikan tanpa Sanksi</option>

                                                            <?php if ($rep['type'] === 'barang'): ?>
                                                                <option value="suspend_barang">Selesai + Hapus Barang & Cooldown Pemilik</option>
                                                            <?php endif; ?>

                                                            <?php if ($rep['type'] === 'peminjaman'): ?>
                                                                <option value="refund_dana">Selesai + Lakukan Refund Dana</option>
                                                            <?php endif; ?>

                                                            <option value="dismissed">Batalkan / Tolak Laporan ini</option>
                                                        </select>

                                                        <button type="submit" class="btn btn-approve">
                                                            <i class="ti ti-send"></i> Proses
                                                        </button>
                                                    </div>
                                                </form>

                                            <?php else: ?>
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

<script>
function updateAction(id, value) {
    const actionInput = document.getElementById('action_' + id);
    actionInput.value = (value === 'dismissed') ? 'dismissed' : 'reviewed';
}

function confirmAksi(form) {
    const select = form.querySelector('select[name="sanksi_option"]');
    const val    = select.value;

    let msg = '';
    if (val === 'dismissed') {
        msg = 'Batalkan/tolak laporan ini?';
    } else if (val === 'suspend_barang') {
        msg = 'Selesaikan laporan + hapus barang dan cooldown pemilik?';
    } else if (val === 'refund_dana') {
        msg = 'Selesaikan laporan + lakukan refund dana peminjaman?';
    } else {
        msg = 'Selesaikan laporan ini tanpa sanksi?';
    }

    return confirm(msg);
}
</script>

</body>
</html>