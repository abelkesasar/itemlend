<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php?page=login");
    exit;
}

function detectType(PDO $conn, int $target_id): string {
    $s = $conn->prepare("SELECT id FROM rentals WHERE id = ? LIMIT 1");
    $s->execute([$target_id]);
    if ($s->fetch()) return 'peminjaman';

    $s = $conn->prepare("SELECT id FROM items WHERE id = ? LIMIT 1");
    $s->execute([$target_id]);
    if ($s->fetch()) return 'barang';

    $s = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    $s->execute([$target_id]);
    if ($s->fetch()) return 'user';

    return 'unknown';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int) ($_POST['id'] ?? 0);
    $action   = $_POST['action'] ?? '';
    $sanksi   = $_POST['sanksi_option'] ?? '';
    $admin_id = (int) ($_SESSION['user'] ?? 0);

    $allowed_status = ['reviewed', 'dismissed'];

    if ($id > 0 && in_array($action, $allowed_status)) {
        $stmt = $conn->prepare("
            UPDATE reports
            SET status = :status, reviewed_at = NOW(), reviewed_by = :admin_id
            WHERE id = :id
        ");
        $stmt->execute([':status' => $action, ':admin_id' => $admin_id, ':id' => $id]);

        if ($action === 'reviewed') {
            $stmtReport = $conn->prepare("SELECT type, target_id FROM reports WHERE id = ?");
            $stmtReport->execute([$id]);
            $reportData = $stmtReport->fetch(PDO::FETCH_ASSOC);

            if ($reportData) {
                $target_id  = (int) $reportData['target_id'];
                $validTypes = ['barang', 'peminjaman', 'user'];
                $reportType = in_array($reportData['type'], $validTypes)
                              ? $reportData['type']
                              : detectType($conn, $target_id);

                // Hapus barang + ban pemilik 7 hari
                if ($reportType === 'barang' && $sanksi === 'suspend_barang') {
                    $stmtOwner = $conn->prepare("SELECT user_id FROM items WHERE id = ?");
                    $stmtOwner->execute([$target_id]);
                    $owner_id = (int) $stmtOwner->fetchColumn();

                    if ($owner_id > 0) {
                        $banned_until = date('Y-m-d H:i:s', strtotime('+7 days'));
$conn->prepare("UPDATE users SET banned_until = ?, status = 'cooldown' WHERE id = ?")
     ->execute([$banned_until, $owner_id]);
                    }

                    $conn->prepare("DELETE FROM items WHERE id = ?")
                         ->execute([$target_id]);
                }

                // Refund dana peminjaman
                if ($reportType === 'peminjaman' && $sanksi === 'refund_dana') {
                    $conn->prepare("UPDATE rentals SET status_pembayaran = 'pending', status_pinjam = 'selesai' WHERE id = ?")
                         ->execute([$target_id]);
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

foreach ($reports as &$rep) {
    $rep['owner_name']         = null;
    $rep['owner_phone']        = null;
    $rep['owner_id']           = null;
    $rep['renter_name']        = null;
    $rep['renter_phone']       = null;
    $rep['renter_id']          = null;
    $rep['item_exists']        = false;
    $rep['owner_banned_until'] = null;

    $validTypes = ['barang', 'peminjaman', 'user'];
    if (!in_array($rep['type'], $validTypes)) {
        $rep['type'] = detectType($conn, (int)$rep['target_id']);
    }

    if ($rep['type'] === 'barang') {
        $stmt = $conn->prepare("
            SELECT i.id, i.nama_barang, i.user_id,
                   u.username AS owner_name, u.nomor_wa AS owner_phone,
                   u.banned_until AS owner_banned_until
            FROM items i
            LEFT JOIN users u ON i.user_id = u.id
            WHERE i.id = ?
        ");
        $stmt->execute([$rep['target_id']]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        $rep['target_label']       = $item ? $item['nama_barang'] : 'Barang dihapus (#' . $rep['target_id'] . ')';
        $rep['target_link']        = $item ? '../index.php?page=detail&id=' . $item['id'] : null;
        $rep['owner_name']         = $item['owner_name']         ?? null;
        $rep['owner_phone']        = $item['owner_phone']        ?? null;
        $rep['owner_id']           = $item['user_id']            ?? null;
        $rep['owner_banned_until'] = $item['owner_banned_until'] ?? null;
        $rep['item_exists']        = (bool) $item;

    } elseif ($rep['type'] === 'peminjaman') {
        $stmt = $conn->prepare("
            SELECT rt.id, rt.user_id AS renter_id, i.nama_barang, i.user_id AS owner_id,
                   ow.username AS owner_name, ow.nomor_wa AS owner_phone,
                   rn.username AS renter_name, rn.nomor_wa AS renter_phone
            FROM rentals rt
            JOIN items i ON rt.item_id = i.id
            LEFT JOIN users ow ON i.user_id  = ow.id
            LEFT JOIN users rn ON rt.user_id = rn.id
            WHERE rt.id = ?
        ");
        $stmt->execute([$rep['target_id']]);
        $rental = $stmt->fetch(PDO::FETCH_ASSOC);

        $rep['target_label'] = 'Transaksi #' . $rep['target_id'] . ($rental ? ' - ' . $rental['nama_barang'] : ' (data dihapus)');
        $rep['target_link']  = null;
        $rep['owner_name']   = $rental['owner_name']  ?? null;
        $rep['owner_phone']  = $rental['owner_phone'] ?? null;
        $rep['owner_id']     = $rental['owner_id']    ?? null;
        $rep['renter_name']  = $rental['renter_name'] ?? null;
        $rep['renter_phone'] = $rental['renter_phone']?? null;
        $rep['renter_id']    = $rental['renter_id']   ?? null;
        $rep['item_exists']  = (bool) $rental;

    } elseif ($rep['type'] === 'user') {
        $stmt = $conn->prepare("SELECT id, username, nomor_wa FROM users WHERE id = ?");
        $stmt->execute([$rep['target_id']]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        $rep['target_label'] = $targetUser ? $targetUser['username'] : 'User #' . $rep['target_id'];
        $rep['target_link']  = $targetUser ? 'user_detail.php?id=' . $targetUser['id'] : null;
        $rep['owner_name']   = $targetUser['username'] ?? null;
        $rep['owner_phone']  = $targetUser['nomor_wa'] ?? null;
        $rep['owner_id']     = $targetUser['id']       ?? null;
        $rep['item_exists']  = (bool) $targetUser;
    } else {
        $rep['target_label'] = 'Laporan #' . $rep['id'] . ' (tipe tidak valid)';
        $rep['target_link']  = null;
    }
}
unset($rep);

$total_reports = count($reports);
$total_pending = count(array_filter($reports, fn($r) => $r['status'] === 'pending'));
$total_selesai = count(array_filter($reports, fn($r) => $r['status'] === 'reviewed'));
$total_ditolak = count(array_filter($reports, fn($r) => $r['status'] === 'dismissed'));

function waLink(string $phone, string $nama, string $konteks): string {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($clean, '0')) $clean = '62' . substr($clean, 1);
    $pesan = urlencode("Halo $nama, saya Admin ItemLend ingin menindaklanjuti laporan terkait $konteks.");
    return "https://wa.me/{$clean}?text={$pesan}";
}

function reportStatusBadge($status) {
    return match($status) {
        'reviewed'  => '<span class="badge badge-approved"><i class="ti ti-circle-check"></i> Selesai</span>',
        'dismissed' => '<span class="badge badge-rejected"><i class="ti ti-circle-x"></i> Diabaikan</span>',
        default     => '<span class="badge badge-pending"><i class="ti ti-clock"></i> Pending</span>',
    };
}

function typeBadge($type) {
    return match($type) {
        'barang'     => '<span class="role-pill rp-barang"><i class="ti ti-box-seam"></i> Barang</span>',
        'peminjaman' => '<span class="role-pill rp-pinjam"><i class="ti ti-receipt"></i> Peminjaman</span>',
        'user'       => '<span class="role-pill rp-user"><i class="ti ti-user"></i> User</span>',
        default      => '<span class="role-pill rp-invalid"><i class="ti ti-alert-triangle"></i> Tidak Valid</span>',
    };
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
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f4f5f7; color: #1a1d2e; min-height: 100vh; }
        a { text-decoration: none; color: inherit; }

        .admin-wrap { display: flex; min-height: 100vh; }

        .main { margin-left: 220px; flex: 1; display: flex; flex-direction: column; }

        .topbar { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 0 28px; height: 60px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
        .topbar-left h1 { font-size: 17px; font-weight: 600; }
        .topbar-left p  { font-size: 12px; color: #6b7280; margin-top: 1px; }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .admin-pill { background: #eef0ff; color: #3d4bff; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
        .avatar { width: 32px; height: 32px; border-radius: 50%; background: #3d4bff; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; }

        .content { padding: 24px 28px; }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }
        .stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 18px; display: flex; align-items: center; gap: 14px; }
        .stat-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .stat-icon i { font-size: 21px; }
        .si-blue  { background: #eef0ff; color: #3d4bff; }
        .si-amber { background: #fefce8; color: #a16207; }
        .si-green { background: #e9f9f0; color: #16a34a; }
        .si-red   { background: #fff5f5; color: #dc2626; }
        .stat-label { font-size: 12px; color: #6b7280; margin-bottom: 3px; }
        .stat-value { font-size: 22px; font-weight: 800; line-height: 1; color: #1a1d2e; }

        /* Table card */
        .table-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
        .table-header { padding: 18px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; }
        .table-title { font-size: 15px; font-weight: 700; }
        .table-sub   { font-size: 12px; color: #6b7280; margin-top: 2px; }

        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #f8f9fb; border-bottom: 1px solid #e5e7eb; }
        thead th { padding: 12px 16px; font-size: 11.5px; font-weight: 700; color: #6b7280; text-align: left; letter-spacing: 0.03em; text-transform: uppercase; white-space: nowrap; }
        tbody tr { border-bottom: 1px solid #f0f1f3; transition: background 0.12s; }
        tbody tr:hover { background: #fafbff; }
        tbody tr.row-pending { border-left: 3px solid #f59e0b; }
        tbody tr:last-child { border-bottom: none; }
        tbody td { padding: 14px 16px; font-size: 13px; vertical-align: top; }

        /* Pelapor */
        .reporter-cell { display: flex; align-items: center; gap: 10px; }
        .reporter-av { width: 34px; height: 34px; border-radius: 50%; background: #eef0ff; color: #3d4bff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; }
        .reporter-name { font-size: 13px; font-weight: 600; color: #3d4bff; }
        .reporter-name:hover { text-decoration: underline; }
        .reporter-date { font-size: 11.5px; color: #6b7280; margin-top: 2px; }

        /* Target */
        .target-link  { font-weight: 600; color: #3d4bff; font-size: 13px; display: flex; align-items: center; gap: 4px; }
        .target-link:hover { text-decoration: underline; }
        .target-plain { font-weight: 600; font-size: 13px; color: #374151; }

        /* Pills */
        .role-pill { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 600; }
        .rp-barang  { background: #fff7e6; color: #cc7a00; border: 1px solid #fde68a; }
        .rp-pinjam  { background: #f3e8ff; color: #7c3aed; border: 1px solid #ddd6fe; }
        .rp-user    { background: #eef0ff; color: #3d4bff; border: 1px solid #c7d2fe; }
        .rp-invalid { background: #fff3cd; color: #856404; }

        /* Badges */
        .badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; padding: 5px 10px; border-radius: 20px; white-space: nowrap; }
        .badge i { font-size: 12px; }
        .badge-approved { background: #e9f9f0; color: #1a7a46; border: 1px solid #a7f3d0; }
        .badge-pending  { background: #fff7e6; color: #cc7a00; border: 1px solid #fed7aa; }
        .badge-rejected { background: #f4f5f7; color: #6b7280; border: 1px solid #d1d5db; }

        /* Teks */
        .reason-text { font-size: 13px; font-weight: 600; max-width: 180px; }
        .detail-text { font-size: 12px; color: #6b7280; max-width: 200px; word-break: break-word; margin-top: 3px; }
        .detail-empty { color: #c0c4ce; font-size: 12px; }

        /* Aksi kolom */
        .actions { display: flex; flex-direction: column; gap: 7px; }
        .wa-row  { display: flex; gap: 5px; flex-wrap: wrap; }
        .btn-row { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }

        .btn { border: none; cursor: pointer; border-radius: 8px; padding: 7px 12px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; text-decoration: none; font-family: inherit; transition: all 0.14s; }
        .btn i { font-size: 14px; }

        .btn-approve        { background: #3d4bff; color: #fff; }
        .btn-approve:hover  { background: #2c38d4; }
        .btn-wa-pelapor     { background: #25d366; color: #fff; }
        .btn-wa-pelapor:hover { background: #20ba5a; }
        .btn-wa-pemilik     { background: #e9f9f0; color: #1a7a46; border: 1px solid #a7f3d0; }
        .btn-wa-pemilik:hover { background: #d1fae5; }
        .btn-wa-penyewa     { background: #fff8e1; color: #856404; border: 1px solid #fcd34d; }
        .btn-wa-penyewa:hover { background: #fef3c7; }
        .btn-disabled       { background: #f3f4f6; color: #9ca3af; cursor: default; }

        .select-sanksi { padding: 7px 10px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 12px; font-weight: 600; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; color: #374151; outline: none; min-width: 210px; cursor: pointer; }
        .select-sanksi:focus { border-color: #3d4bff; }

        /* Notif ban & deleted */
        .inline-ban     { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; color: #dc2626; background: #fff5f5; border: 1px solid #fecaca; border-radius: 7px; padding: 4px 9px; margin-top: 4px; }
        .inline-deleted { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; color: #6b7280; background: #f4f5f7; border: 1px solid #e5e7eb; border-radius: 7px; padding: 4px 9px; margin-top: 4px; }
        .inline-warn    { display: flex; align-items: flex-start; gap: 6px; font-size: 11.5px; color: #92400e; background: #fff7e6; border: 1px solid #fde68a; border-radius: 7px; padding: 6px 9px; margin-top: 4px; max-width: 320px; }
        .inline-warn i  { flex-shrink: 0; font-size: 14px; margin-top: 1px; }

        /* Empty */
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

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon si-blue"><i class="ti ti-flag"></i></div>
                    <div><div class="stat-label">Total Laporan</div><div class="stat-value"><?= $total_reports ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon si-amber"><i class="ti ti-hourglass"></i></div>
                    <div><div class="stat-label">Pending</div><div class="stat-value"><?= $total_pending ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon si-green"><i class="ti ti-circle-check"></i></div>
                    <div><div class="stat-label">Selesai</div><div class="stat-value"><?= $total_selesai ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon si-red"><i class="ti ti-circle-x"></i></div>
                    <div><div class="stat-label">Diabaikan</div><div class="stat-value"><?= $total_ditolak ?></div></div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-card">
                <div class="table-header">
                    <div>
                        <div class="table-title">Daftar Laporan</div>
                        <div class="table-sub">Laporan pending ditampilkan paling atas</div>
                    </div>
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
                            <th class="hide-md">Alasan / Detail</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $rep):
                            $is_pending   = $rep['status'] === 'pending';
                            $is_barang    = $rep['type']   === 'barang';
                            $owner_banned = !empty($rep['owner_banned_until']) && strtotime($rep['owner_banned_until']) > time();
                            $item_gone    = $is_barang && !$rep['item_exists'];
                        ?>
                        <tr class="<?= $is_pending ? 'row-pending' : '' ?>">

                            <!-- Pelapor -->
                            <td>
                                <div class="reporter-cell">
                                    <div class="reporter-av"><?= strtoupper(substr($rep['reporter_name'] ?? '?', 0, 2)) ?></div>
                                    <div>
                                        <a href="user_detail.php?id=<?= $rep['reporter_user_id'] ?>" class="reporter-name">
                                            <?= htmlspecialchars($rep['reporter_name']) ?>
                                        </a>
                                        <div class="reporter-date"><?= date('d M Y, H:i', strtotime($rep['created_at'])) ?></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Tipe -->
                            <td><?= typeBadge($rep['type']) ?></td>

                            <!-- Target -->
                            <td>
                                <?php if (!empty($rep['target_link'])): ?>
                                    <a href="<?= htmlspecialchars($rep['target_link']) ?>" class="target-link" target="_blank">
                                        <?= htmlspecialchars($rep['target_label']) ?>
                                        <i class="ti ti-external-link" style="font-size:11px;"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="target-plain"><?= htmlspecialchars($rep['target_label']) ?></span>
                                <?php endif; ?>

                                <?php if ($owner_banned): ?>
                                <div class="inline-ban">
                                    <i class="ti ti-ban" style="font-size:13px;"></i>
                                    Pemilik dibanned s/d <?= date('d M Y', strtotime($rep['owner_banned_until'])) ?>
                                </div>
                                <?php endif; ?>

                                <?php if ($item_gone): ?>
                                <div class="inline-deleted">
                                    <i class="ti ti-trash" style="font-size:13px;"></i>
                                    Barang sudah dihapus
                                </div>
                                <?php endif; ?>
                            </td>

                            <!-- Alasan / Detail -->
                            <td class="hide-md">
                                <div class="reason-text"><?= htmlspecialchars($rep['reason']) ?></div>
                                <?php if (!empty($rep['detail'])): ?>
                                <div class="detail-text"><?= htmlspecialchars($rep['detail']) ?></div>
                                <?php endif; ?>
                            </td>

                            <!-- Status -->
                            <td><?= reportStatusBadge($rep['status']) ?></td>

                            <!-- Aksi -->
                            <td>
                                <div class="actions">
                                <?php if ($is_pending && !$item_gone): ?>

                                    <!-- WA buttons -->
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
                                            <i class="ti ti-brand-whatsapp"></i>
                                            <?= $rep['type'] === 'user' ? 'Terlapor' : 'Pemilik' ?>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (!empty($rep['renter_phone'])): ?>
                                        <a href="<?= waLink($rep['renter_phone'], $rep['renter_name'], $rep['target_label']) ?>"
                                           target="_blank" class="btn btn-wa-penyewa">
                                            <i class="ti ti-brand-whatsapp"></i> Penyewa
                                        </a>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Warning hapus+ban -->
                                    <?php if ($is_barang): ?>
                                    <div class="inline-warn">
                                        <i class="ti ti-alert-triangle"></i>
                                        <span>Opsi "Hapus + Ban" akan <strong>hapus barang permanen</strong> &amp; <strong>ban pemilik 7 hari</strong>.</span>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Form proses -->
                                    <form method="POST" onsubmit="return confirmAksi(this)">
                                        <input type="hidden" name="id" value="<?= $rep['id'] ?>">
                                        <input type="hidden" name="action" value="reviewed" id="action_<?= $rep['id'] ?>">
                                        <div class="btn-row">
                                            <select name="sanksi_option" class="select-sanksi"
                                                    onchange="updateAction(<?= $rep['id'] ?>, this.value)">
                                                <option value="none">Selesaikan tanpa Sanksi</option>
                                                <?php if ($is_barang): ?>
                                                <option value="suspend_barang">Hapus Barang + Ban Pemilik 7 Hari</option>
                                                <?php endif; ?>
                                                <?php if ($rep['type'] === 'peminjaman'): ?>
                                                <option value="refund_dana">Selesai + Refund Dana</option>
                                                <?php endif; ?>
                                                <option value="dismissed">Batalkan / Tolak Laporan</option>
                                            </select>
                                            <button type="submit" class="btn btn-approve">
                                                <i class="ti ti-send"></i> Proses
                                            </button>
                                        </div>
                                    </form>

                                <?php elseif ($item_gone && $is_pending): ?>
                                    <!-- Barang sudah dihapus, tinggal tandai selesai -->
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?= $rep['id'] ?>">
                                        <input type="hidden" name="action" value="reviewed">
                                        <input type="hidden" name="sanksi_option" value="none">
                                        <button type="submit" class="btn btn-approve"
                                                onclick="return confirm('Tandai laporan ini sebagai selesai?')">
                                            <i class="ti ti-check"></i> Tandai Selesai
                                        </button>
                                    </form>

                                <?php else: ?>
                                    <span class="btn btn-disabled">
                                        <i class="ti ti-lock"></i> Terproses
                                    </span>
                                    <?php if (!empty($rep['reviewed_at'])): ?>
                                    <div style="font-size:11.5px;color:#9ca3af;margin-top:3px;">
                                        <?= date('d M Y H:i', strtotime($rep['reviewed_at'])) ?>
                                    </div>
                                    <?php endif; ?>
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
    document.getElementById('action_' + id).value = (value === 'dismissed') ? 'dismissed' : 'reviewed';
}
function confirmAksi(form) {
    const val = form.querySelector('select[name="sanksi_option"]').value;
    const msgs = {
        'dismissed':      'Batalkan/tolak laporan ini?',
        'suspend_barang': 'Yakin? Barang dihapus permanen dan pemilik dibanned 7 hari. Tidak bisa dibatalkan!',
        'refund_dana':    'Selesaikan laporan + refund dana peminjaman?',
    };
    return confirm(msgs[val] ?? 'Selesaikan laporan ini tanpa sanksi?');
}
</script>
</body>
</html>