<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php?page=login");
    exit;
}

// Handle update status laporan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int) ($_POST['id'] ?? 0);
    $action   = $_POST['action'] ?? '';
    $admin_id = (int) ($_SESSION['user'] ?? 0);

    $allowed_status = ['ditinjau', 'selesai', 'ditolak'];

    if ($id > 0 && in_array($action, $allowed_status)) {
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
    }

    header("Location: reports.php");
    exit;
}

// Ambil semua laporan + info pelapor
$reports = $conn->query("
    SELECT r.*, u.username AS reporter_name
    FROM reports r
    JOIN users u ON r.reporter_id = u.id
    ORDER BY
        CASE WHEN r.status = 'pending' THEN 1 ELSE 2 END,
        r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Lengkapi info target (nama barang / info transaksi)
foreach ($reports as &$rep) {
    if ($rep['type'] === 'barang') {
        $stmt = $conn->prepare("SELECT nama_barang FROM items WHERE id = ?");
        $stmt->execute([$rep['target_id']]);
        $nama = $stmt->fetchColumn();
        $rep['target_label'] = $nama ?: 'Barang tidak ditemukan (#' . $rep['target_id'] . ')';
    } elseif ($rep['type'] === 'transaksi') {
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
        $rep['target_label'] = '-';
    }
}
unset($rep);

$total_reports = count($reports);
$total_pending = count(array_filter($reports, fn($r) => $r['status'] === 'pending'));
$total_selesai = count(array_filter($reports, fn($r) => $r['status'] === 'selesai'));
$total_ditolak = count(array_filter($reports, fn($r) => $r['status'] === 'ditolak'));

function reportStatusBadge($status) {
    if ($status === 'selesai') {
        return '<span class="badge badge-approved"><i class="ti ti-circle-check"></i> Selesai</span>';
    } elseif ($status === 'ditinjau') {
        return '<span class="badge badge-review"><i class="ti ti-eye"></i> Ditinjau</span>';
    } elseif ($status === 'ditolak') {
        return '<span class="badge badge-rejected"><i class="ti ti-circle-x"></i> Ditolak</span>';
    }
    return '<span class="badge badge-pending"><i class="ti ti-clock"></i> Pending</span>';
}

function typeBadge($type) {
    if ($type === 'barang') {
        return '<span class="role-pill"><i class="ti ti-box-seam"></i> Barang</span>';
    }
    return '<span class="role-pill"><i class="ti ti-receipt"></i> Transaksi</span>';
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

        .actions { display: flex; gap: 8px; flex-wrap: wrap; }

        .btn {
            border: none; cursor: pointer; border-radius: 8px;
            padding: 7px 12px; font-size: 12px; font-weight: 700;
            display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
        }
        .btn-review  { background: #eff6ff; color: #2563eb; border: 1px solid #93c5fd; }
        .btn-review:hover { background: #dbeafe; }
        .btn-approve { background: #3d4bff; color: #fff; }
        .btn-approve:hover { background: #2c38d4; }
        .btn-reject  { background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; }
        .btn-reject:hover { background: #fee2e2; }
        .btn-disabled { background: #f3f4f6; color: #9ca3af; cursor: default; }

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
                                            <?php if ($rep['status'] === 'pending'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?= $rep['id'] ?>">
                                                    <input type="hidden" name="action" value="ditinjau">
                                                    <button type="submit" class="btn btn-review">
                                                        <i class="ti ti-eye"></i> Tinjau
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if (in_array($rep['status'], ['pending', 'ditinjau'])): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Tandai laporan ini selesai?')">
                                                    <input type="hidden" name="id" value="<?= $rep['id'] ?>">
                                                    <input type="hidden" name="action" value="selesai">
                                                    <button type="submit" class="btn btn-approve">
                                                        <i class="ti ti-check"></i> Selesai
                                                    </button>
                                                </form>

                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Tolak laporan ini?')">
                                                    <input type="hidden" name="id" value="<?= $rep['id'] ?>">
                                                    <input type="hidden" name="action" value="ditolak">
                                                    <button type="submit" class="btn btn-reject">
                                                        <i class="ti ti-x"></i> Tolak
                                                    </button>
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
</body>
</html>