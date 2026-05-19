<?php
session_start();
require '../config/db.php';

if (
    !isset($_SESSION['user']) ||
    $_SESSION['user']['role'] != 'admin'
) {
    header("Location: ../index.php?page=login");
    exit;
}

// Get stats
$total_users   = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pending_users = $conn->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$total_items   = $conn->query("SELECT COUNT(*) FROM items")->fetchColumn();
$total_rentals = $conn->query("SELECT COUNT(*) FROM rentals")->fetchColumn();

// Get pending users
$pending_list = $conn->query("
    SELECT * FROM users 
    WHERE status='pending' 
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get latest items
$latest_items = $conn->query("
    SELECT * FROM items 
    ORDER BY id DESC 
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ItemLend</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f4f5f7;
            color: #1a1d2e;
            min-height: 100vh;
        }

        /* ── Layout ── */
        .admin-wrap { display: flex; min-height: 100vh; }

        /* ── Sidebar ── */
        .sidebar {
            width: 220px;
            background: #1a1d2e;
            color: #fff;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 100;
        }
        .sidebar-logo {
            padding: 24px 20px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .logo-icon {
            width: 32px; height: 32px;
            background: #3d4bff;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
        }
        .logo-icon i { font-size: 17px; color: #fff; }
        .logo-text { font-size: 16px; font-weight: 600; color: #fff; }

        .sidebar-section {
            padding: 18px 20px 6px;
            font-size: 10px;
            font-weight: 600;
            color: rgba(255,255,255,0.3);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            margin: 1px 8px;
            border-radius: 8px;
            font-size: 13.5px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }
        .nav-item:hover { background: rgba(255,255,255,0.07); color: #fff; }
        .nav-item.active { background: #3d4bff; color: #fff; }
        .nav-item i { font-size: 18px; }
        .nav-badge {
            margin-left: auto;
            background: #ff5c5c;
            color: #fff;
            font-size: 10px;
            font-weight: 600;
            border-radius: 20px;
            padding: 1px 7px;
        }
        .sidebar-footer {
            margin-top: auto;
            padding: 12px 8px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        /* ── Main ── */
        .main {
            margin-left: 220px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* ── Topbar ── */
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 0 28px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar h1 { font-size: 17px; font-weight: 600; }
        .topbar p  { font-size: 12px; color: #6b7280; margin-top: 1px; }
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

        /* ── Content ── */
        .content { padding: 24px 28px; }

        /* ── Stat Cards ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 18px;
        }
        .stat-icon {
            width: 42px; height: 42px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 14px;
        }
        .stat-icon i { font-size: 21px; }
        .stat-icon.blue  { background: #eef0ff; color: #3d4bff; }
        .stat-icon.amber { background: #fff7e6; color: #cc7a00; }
        .stat-icon.green { background: #e9f9f0; color: #1a7a46; }
        .stat-icon.teal  { background: #e4f7f5; color: #0d7d72; }
        .stat-label { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
        .stat-value { font-size: 28px; font-weight: 600; line-height: 1; }
        .stat-sub { font-size: 11.5px; color: #6b7280; margin-top: 6px; }
        .stat-sub .up { color: #1a7a46; font-weight: 500; }

        /* ── Two column ── */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }

        /* ── Card ── */
        .card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 20px;
        }
        .card-header {
            display: flex; align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .card-title { font-size: 14px; font-weight: 600; }
        .card-action { font-size: 12px; color: #3d4bff; font-weight: 500; text-decoration: none; }
        .card-action:hover { text-decoration: underline; }

        /* ── Pending user rows ── */
        .user-row {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 0;
            border-bottom: 1px solid #f0f1f3;
        }
        .user-row:last-child { border-bottom: none; padding-bottom: 0; }
        .user-av {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 600; flex-shrink: 0;
        }
        .user-info { flex: 1; min-width: 0; }
        .user-name  { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-meta  { font-size: 11px; color: #6b7280; }

        /* ── Badges ── */
        .badge { font-size: 10.5px; font-weight: 600; padding: 3px 8px; border-radius: 20px; white-space: nowrap; }
        .badge-pending { background: #fff7e6; color: #cc7a00; }
        .badge-active  { background: #e9f9f0; color: #1a7a46; }

        /* ── Approve button ── */
        .approve-btn {
            font-size: 11.5px; font-weight: 600;
            background: #3d4bff; color: #fff;
            border: none; padding: 5px 12px; border-radius: 7px;
            cursor: pointer; white-space: nowrap;
            text-decoration: none; display: inline-block;
            transition: background 0.15s;
        }
        .approve-btn:hover { background: #2c38d4; }

        /* ── Item rows ── */
        .item-row {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 0;
            border-bottom: 1px solid #f0f1f3;
        }
        .item-row:last-child { border-bottom: none; }
        .item-img {
            width: 40px; height: 40px; border-radius: 8px;
            background: #f4f5f7; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }
        .item-img img { width: 100%; height: 100%; object-fit: cover; }
        .item-img i { font-size: 19px; color: #9ca3af; }
        .item-info { flex: 1; min-width: 0; }
        .item-name { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .item-loc  { font-size: 11px; color: #6b7280; }
        .item-price { font-size: 13px; font-weight: 600; color: #3d4bff; white-space: nowrap; }

        /* ── Quick actions ── */
        .quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .action-btn {
            display: flex; align-items: center; gap: 10px;
            padding: 13px 16px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: #fff;
            text-decoration: none; color: #1a1d2e;
            transition: background 0.15s, border-color 0.15s;
        }
        .action-btn:hover { background: #f4f5f7; border-color: #c9cbcd; }
        .action-btn i    { font-size: 19px; color: #3d4bff; }
        .action-btn span { font-size: 13px; font-weight: 500; }

        /* ── Empty state ── */
        .empty-state { text-align: center; padding: 24px; color: #9ca3af; font-size: 13px; }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .two-col    { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .sidebar { display: none; }
            .main { margin-left: 0; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
<div class="admin-wrap">

    <!-- ── Sidebar ── -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon"><i class="ti ti-briefcase"></i></div>
            <span class="logo-text">ItemLend</span>
        </div>

        <div class="sidebar-section">Main</div>
        <a href="dashboard.php" class="nav-item active">
            <i class="ti ti-layout-dashboard"></i> Dashboard
        </a>
        <a href="../index.php?page=admin_users" class="nav-item">
            <i class="ti ti-users"></i> User Approval
            <?php if ($pending_users > 0): ?>
                <span class="nav-badge"><?= $pending_users ?></span>
            <?php endif; ?>
        </a>
        <a href="../index.php?page=items" class="nav-item">
            <i class="ti ti-box-seam"></i> Items
        </a>
        <a href="../index.php?page=rentals" class="nav-item">
            <i class="ti ti-shopping-cart"></i> Rentals
        </a>

        <div class="sidebar-section">System</div>
        <a href="../index.php" class="nav-item">
            <i class="ti ti-home"></i> View Site
        </a>

        <div class="sidebar-footer">
            <a href="../actions/logout.php" class="nav-item">
                <i class="ti ti-logout"></i> Logout
            </a>
        </div>
    </div>

    <!-- ── Main ── -->
    <div class="main">

        <!-- Topbar -->
        <div class="topbar">
            <div>
                <h1>Admin Dashboard</h1>
                <p><?= date('l, d F Y') ?></p>
            </div>
            <div class="topbar-right">
                <span class="admin-pill">Admin</span>
                <div class="avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
            </div>
        </div>

        <!-- Content -->
        <div class="content">

            <!-- Stat Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="ti ti-users"></i></div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value"><?= $total_users ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="ti ti-user-plus"></i></div>
                    <div class="stat-label">Pending Users</div>
                    <div class="stat-value"><?= $pending_users ?></div>
                    <div class="stat-sub">Menunggu persetujuan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="ti ti-box-seam"></i></div>
                    <div class="stat-label">Total Items</div>
                    <div class="stat-value"><?= $total_items ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon teal"><i class="ti ti-shopping-cart-check"></i></div>
                    <div class="stat-label">Total Rentals</div>
                    <div class="stat-value"><?= $total_rentals ?></div>
                </div>
            </div>

            <!-- Pending Approvals + Latest Items -->
            <div class="two-col">

                <!-- Pending Users -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Pending Approvals</span>
                        <a href="../index.php?page=admin_users" class="card-action">Lihat semua →</a>
                    </div>

                    <?php if (empty($pending_list)): ?>
                        <div class="empty-state"><i class="ti ti-circle-check" style="font-size:28px;display:block;margin-bottom:6px;color:#1a7a46"></i>Tidak ada user yang menunggu</div>
                    <?php else: ?>
                        <?php foreach ($pending_list as $u):
                            $initials = strtoupper(substr($u['username'] ?? '?', 0, 2));
                            $colors   = [
                                ['#eef0ff','#3d4bff'], ['#e9f9f0','#1a7a46'],
                                ['#fff7e6','#cc7a00'], ['#fce7f3','#9d174d'],
                                ['#e4f7f5','#0d7d72'],
                            ];
                            $c = $colors[crc32($u['id'] ?? $u['username']) % 5];
                        ?>
                        <div class="user-row">
                            <div class="user-av" style="background:<?= $c[0] ?>;color:<?= $c[1] ?>;"><?= $initials ?></div>
                            <div class="user-info">
                                <div class="user-name"><?= htmlspecialchars($u['username'] ?? '-') ?></div>
                                <div class="user-meta">User ID: <?= $u['id'] ?></div>
                            </div>
                            <span class="badge badge-pending">Pending</span>
                            <a href="../actions/approve_user.php?id=<?= $u['id'] ?>" class="approve-btn">Approve</a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Latest Items -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Item Terbaru</span>
                        <a href="../index.php" class="card-action">Lihat semua →</a>
                    </div>

                    <?php if (empty($latest_items)): ?>
                        <div class="empty-state">Belum ada item</div>
                    <?php else: ?>
                        <?php foreach ($latest_items as $item): ?>
                        <div class="item-row">
                            <div class="item-img">
                                <i class="ti ti-box-seam"></i>
                            </div>
                            <div class="item-info">
                                <div class="item-name"><?= htmlspecialchars($item['nama_barang'] ?? '-') ?></div>
                                <div class="item-loc">
                                    <i class="ti ti-info-circle" style="font-size:11px;vertical-align:-1px"></i>
                                    <?= htmlspecialchars(substr($item['deskripsi'] ?? '-', 0, 30)) ?>...
                                </div>
                            </div>
                            <span class="item-price">
                                Rp<?= number_format($item['harga'] ?? 0, 0, ',', '.') ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Quick Actions</span>
                </div>
                <div class="quick-actions">
                    <a href="../index.php?page=admin_users" class="action-btn">
                        <i class="ti ti-user-check"></i><span>Approve Users</span>
                    </a>
                    <a href="../index.php" class="action-btn">
                        <i class="ti ti-eye"></i><span>View Site</span>
                    </a>
                    <a href="../index.php?page=items" class="action-btn">
                        <i class="ti ti-box-seam"></i><span>Kelola Items</span>
                    </a>
                    <a href="../index.php?page=rentals" class="action-btn">
                        <i class="ti ti-shopping-cart"></i><span>Kelola Rentals</span>
                    </a>
                </div>
            </div>

        </div><!-- /content -->
    </div><!-- /main -->
</div><!-- /admin-wrap -->
</body>
</html>