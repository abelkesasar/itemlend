<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php?page=login");
    exit;
}

// Handle approve / reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id > 0 && in_array($action, ['approved', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE users SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $action,
            ':id' => $id
        ]);
    }

    header("Location: users.php");
    exit;
}

// Stats untuk sidebar
$pending_users = $conn->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();

// Ambil semua user
$users = $conn->query("
    SELECT id, username, role, status, alamat, foto_profil, ktm, ktp
    FROM users
    ORDER BY 
        CASE 
            WHEN status = 'pending' THEN 1
            WHEN status = 'approved' THEN 2
            WHEN status = 'rejected' THEN 3
            ELSE 4
        END,
        id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$total_users = count($users);
$total_approved = $conn->query("SELECT COUNT(*) FROM users WHERE status='approved'")->fetchColumn();
$total_pending = $conn->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$total_rejected = $conn->query("SELECT COUNT(*) FROM users WHERE status='rejected'")->fetchColumn();

function statusBadge($status) {
    if ($status === 'approved') {
        return '<span class="badge badge-approved"><i class="ti ti-circle-check"></i> Approved</span>';
    } elseif ($status === 'pending') {
        return '<span class="badge badge-pending"><i class="ti ti-clock"></i> Pending</span>';
    } elseif ($status === 'rejected') {
        return '<span class="badge badge-rejected"><i class="ti ti-circle-x"></i> Rejected</span>';
    }

    return '<span class="badge badge-unknown">Unknown</span>';
}

function userInitial($name) {
    return strtoupper(substr($name ?? '?', 0, 2));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Users - ItemLend Admin</title>

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

        .topbar-left h1 {
            font-size: 17px;
            font-weight: 600;
        }

        .topbar-left p {
            font-size: 12px;
            color: #6b7280;
            margin-top: 1px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-pill {
            background: #eef0ff;
            color: #3d4bff;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #3d4bff;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }

        .content {
            padding: 24px 28px;
        }

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

        .stat-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
        }

        .table-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
        }

        .table-header {
            padding: 18px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .table-title {
            font-size: 15px;
            font-weight: 700;
        }

        .table-sub {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            background: #f8f9fb;
            border-bottom: 1px solid #e5e7eb;
        }

        thead th {
            padding: 12px 16px;
            font-size: 11.5px;
            font-weight: 700;
            color: #6b7280;
            text-align: left;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid #f0f1f3;
            transition: background 0.12s;
        }

        tbody tr:hover {
            background: #fafbff;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody td {
            padding: 14px 16px;
            font-size: 13.5px;
            vertical-align: middle;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-av {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #eef0ff;
            color: #3d4bff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .user-name {
            font-size: 13.5px;
            font-weight: 600;
        }

        .user-id {
            font-size: 11.5px;
            color: #6b7280;
            margin-top: 2px;
        }

        .role-pill {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            background: #f3f4f6;
            color: #374151;
            font-size: 11.5px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .alamat {
            color: #6b7280;
            max-width: 220px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 700;
            padding: 5px 10px;
            border-radius: 20px;
            white-space: nowrap;
        }

        .badge-approved {
            background: #e9f9f0;
            color: #1a7a46;
            border: 1px solid #a7f3d0;
        }

        .badge-pending {
            background: #fff7e6;
            color: #cc7a00;
            border: 1px solid #fed7aa;
        }

        .badge-rejected {
            background: #fff5f5;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .badge-unknown {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #e5e7eb;
        }

        .docs {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .doc-pill {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 20px;
            background: #eef0ff;
            color: #3d4bff;
            text-decoration: none;
        }

        .doc-empty {
            font-size: 11px;
            color: #9ca3af;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            border: none;
            cursor: pointer;
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .btn-approve {
            background: #3d4bff;
            color: #fff;
        }

        .btn-approve:hover {
            background: #2c38d4;
        }

        .btn-reject {
            background: #fff5f5;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn-reject:hover {
            background: #fee2e2;
        }

        .btn-disabled {
            background: #f3f4f6;
            color: #9ca3af;
            cursor: default;
        }

        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: #9ca3af;
            font-size: 13px;
        }

        @media (max-width: 1000px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .hide-md {
                display: none;
            }
        }

        @media (max-width: 600px) {
            .main {
                margin-left: 0;
            }

            .content {
                padding: 16px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .table-card {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
<div class="admin-wrap">

    <?php include 'sidebar.php'; ?>

    <div class="main">

        <div class="topbar">
            <div class="topbar-left">
                <h1>Kelola Users</h1>
                <p>Daftar user terdaftar dan user yang menunggu approval</p>
            </div>
            <div class="topbar-right">
                <span class="admin-pill">Admin</span>
                <div class="avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
            </div>
        </div>

        <div class="content">

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value"><?= $total_users ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Approved</div>
                    <div class="stat-value"><?= $total_approved ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending</div>
                    <div class="stat-value"><?= $total_pending ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Rejected</div>
                    <div class="stat-value"><?= $total_rejected ?></div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <div>
                        <div class="table-title">Daftar Users</div>
                        <div class="table-sub">User pending ditampilkan paling atas</div>
                    </div>
                </div>

                <?php if (empty($users)): ?>
                    <div class="empty-state">Belum ada user terdaftar.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="hide-md">Alamat</th>
                                <th class="hide-md">Dokumen</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-av"><?= userInitial($u['username']) ?></div>
                                            <div>
                                                <div class="user-name"><?= htmlspecialchars($u['username']) ?></div>
                                                <div class="user-id">ID: <?= $u['id'] ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="role-pill"><?= htmlspecialchars($u['role']) ?></span>
                                    </td>

                                    <td>
                                        <?= statusBadge($u['status']) ?>
                                    </td>

                                    <td class="hide-md">
                                        <div class="alamat">
                                            <?= !empty($u['alamat']) ? htmlspecialchars($u['alamat']) : '-' ?>
                                        </div>
                                    </td>

                                    <td class="hide-md">
                                        <div class="docs">
                                            <?php if (!empty($u['foto_profil'])): ?>
                                                <a class="doc-pill" href="../uploads/<?= htmlspecialchars($u['foto_profil']) ?>" target="_blank">Profil</a>
                                            <?php endif; ?>

                                            <?php if (!empty($u['ktm'])): ?>
                                                <a class="doc-pill" href="../uploads/<?= htmlspecialchars($u['ktm']) ?>" target="_blank">KTM</a>
                                            <?php endif; ?>

                                            <?php if (!empty($u['ktp'])): ?>
                                                <a class="doc-pill" href="../uploads/<?= htmlspecialchars($u['ktp']) ?>" target="_blank">KTP</a>
                                            <?php endif; ?>

                                            <?php if (empty($u['foto_profil']) && empty($u['ktm']) && empty($u['ktp'])): ?>
                                                <span class="doc-empty">Belum ada</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="actions">
                                            <?php if ($u['status'] === 'pending'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="action" value="approved">
                                                    <button type="submit" class="btn btn-approve">
                                                        <i class="ti ti-check"></i> Approve
                                                    </button>
                                                </form>

                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Tolak user ini?')">
                                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="action" value="rejected">
                                                    <button type="submit" class="btn btn-reject">
                                                        <i class="ti ti-x"></i> Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="btn btn-disabled">
                                                    <i class="ti ti-lock"></i> Selesai
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