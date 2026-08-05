<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') != 'admin') {
    header("Location: ../index.php?page=login");
    exit;
}

// ── Stats
$total_users     = $conn->query("SELECT COUNT(*) FROM users WHERE status != 'cooldown'")->fetchColumn();
$pending_users   = $conn->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$cooldown_users  = $conn->query("SELECT COUNT(*) FROM users WHERE status='cooldown'")->fetchColumn();
$total_items     = $conn->query("SELECT COUNT(*) FROM items")->fetchColumn();
$pending_items   = $conn->query("SELECT COUNT(*) FROM items WHERE status='pending'")->fetchColumn();
$cooldown_items  = $conn->query("SELECT COUNT(*) FROM items WHERE status='cooldown'")->fetchColumn();
$total_rentals   = $conn->query("SELECT COUNT(*) FROM rentals")->fetchColumn();
$pending_reports = $conn->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn();

// ── Revenue minggu ini (kecuali yang direfund)
$revenue_minggu = $conn->query("
    SELECT COALESCE(SUM(total_harga), 0)
    FROM rentals
    WHERE status_pembayaran = 'lunas'
    AND (status_refund IS NULL OR status_refund = 'tidak_ada')
    AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
")->fetchColumn();

$revenue_total = $conn->query("
    SELECT COALESCE(SUM(total_harga), 0)
    FROM rentals
    WHERE status_pembayaran = 'lunas'
    AND (status_refund IS NULL OR status_refund = 'tidak_ada')
")->fetchColumn();

// ── Pending users (latest 5)
$pending_list = $conn->query("SELECT * FROM users WHERE status='pending' ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// ── Pending barang approval (latest 3)
$pending_items_list = $conn->query("SELECT i.*, u.username AS owner FROM items i LEFT JOIN users u ON i.user_id = u.id WHERE i.status='pending' ORDER BY i.created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

// ── Latest items (approved, latest 3)
$latest_items = $conn->query("SELECT * FROM items WHERE status='approved' ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

// ── Aktivitas 7 hari dari rentals
$weekly_raw = $conn->query("
    SELECT DATE(created_at) AS tgl, COUNT(*) AS total
    FROM rentals
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY tgl ASC
")->fetchAll(PDO::FETCH_ASSOC);

$days_map = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $days_map[$d] = 0;
}
foreach ($weekly_raw as $w) {
    $days_map[$w['tgl']] = (int)$w['total'];
}
$max_weekly = max($days_map) ?: 1;
$hari_id = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

// ── Aktivitas terkini
$act_rentals = $conn->query("
    SELECT CONCAT('Rental baru #', LPAD(id,6,'0'), ' dibuat') AS keterangan,
           created_at, 'accent' AS warna
    FROM rentals ORDER BY created_at DESC LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

$act_lunas = $conn->query("
    SELECT CONCAT('Pembayaran #', LPAD(id,6,'0'), ' dikonfirmasi') AS keterangan,
           paid_at AS created_at, 'success' AS warna
    FROM rentals WHERE status_pembayaran='lunas' AND paid_at IS NOT NULL
    ORDER BY paid_at DESC LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

$act_reports = $conn->query("
    SELECT CONCAT('Laporan #', id, ' dari user #', reporter_id, ' — ', reason) AS keterangan,
           created_at, 'danger' AS warna
    FROM reports WHERE status='pending'
    ORDER BY created_at DESC LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

$activities = array_merge($act_rentals, $act_lunas, $act_reports);
usort($activities, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
$activities = array_slice($activities, 0, 8);

// ── Helpers
function getFirstGambar(array $item): ?string {
    if (empty($item['gambar'])) return null;
    $list = json_decode($item['gambar'], true);
    $file = (is_array($list) && !empty($list[0])) ? $list[0] : $item['gambar'];
    return file_exists("../uploads/$file") ? "../uploads/$file" : null;
}

function getItemIcon(string $name): string {
    $n = strtolower($name);
    if (str_contains($n, 'motor') || str_contains($n, 'mobil') || str_contains($n, 'sepeda')) return 'ti-bike';
    if (str_contains($n, 'kamera') || str_contains($n, 'camera') || str_contains($n, 'canon') || str_contains($n, 'sony')) return 'ti-camera';
    if (str_contains($n, 'laptop') || str_contains($n, 'komputer')) return 'ti-device-laptop';
    if (str_contains($n, 'tenda') || str_contains($n, 'camping')) return 'ti-tent';
    if (str_contains($n, 'bor') || str_contains($n, 'drill') || str_contains($n, 'alat')) return 'ti-tool';
    if (str_contains($n, 'speaker') || str_contains($n, 'audio') || str_contains($n, 'jbl')) return 'ti-device-speaker';
    return 'ti-box-seam';
}

$avatar_colors = [
    ['#eef0ff','#3d4bff'], ['#e9f9f0','#1a7a46'],
    ['#fff7e6','#cc7a00'], ['#fce7f3','#9d174d'], ['#e4f7f5','#0d7d72'],
];

function warnaDot(string $w): string {
    return match($w) {
        'success' => '#16a34a',
        'warning' => '#d97706',
        'danger'  => '#dc2626',
        default   => '#3d4bff',
    };
}

function timeAgo(string $dt): string {
    $ts = strtotime($dt);
    if (!$ts) return '-';
    $diff = abs(time() - $ts);
    if ($diff < 60)    return $diff . 'd';
    if ($diff < 3600)  return floor($diff/60) . 'm';
    if ($diff < 86400) return floor($diff/3600) . 'j';
    return floor($diff/86400) . 'h';
}

$urgent_count = $pending_users + $pending_items + $pending_reports;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ItemLend</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f5f7;color:#1a1d2e;min-height:100vh}
        a{text-decoration:none;color:inherit}

        .topbar{background:#fff;border-bottom:1px solid #e5e7eb;padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
        .topbar h1{font-size:17px;font-weight:700}
        .topbar p{font-size:12px;color:#6b7280;margin-top:1px}
        .topbar-right{display:flex;align-items:center;gap:12px}
        .admin-pill{background:#eef0ff;color:#3d4bff;font-size:12px;font-weight:600;padding:4px 12px;border-radius:20px}
        .avatar{width:32px;height:32px;border-radius:50%;background:#3d4bff;color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600}

        .content{padding:24px 28px;display:flex;flex-direction:column;gap:16px}

        /* Urgent banner */
        .urgent-banner{
            display:flex;align-items:center;gap:12px;
            background:linear-gradient(135deg,#fef3c7,#fff7e6);
            border:1px solid #fde68a;border-radius:12px;padding:14px 18px;
            cursor:pointer;transition:all .15s;
        }
        .urgent-banner:hover{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.1)}
        .urgent-banner i{font-size:20px;color:#d97706;flex-shrink:0}
        .urgent-banner-text{flex:1}
        .urgent-banner-text strong{font-size:13px;color:#92400e;font-weight:700}
        .urgent-banner-text span{display:block;font-size:12px;color:#b45309;margin-top:2px}
        .urgent-badge{background:#d97706;color:#fff;font-size:13px;font-weight:800;padding:4px 12px;border-radius:20px}

        /* Stats */
        .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
        .stat-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px 18px;display:flex;flex-direction:column;gap:8px}
        .stat-top{display:flex;align-items:center;justify-content:space-between}
        .stat-icon{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center}
        .stat-icon i{font-size:18px}
        .stat-icon.blue{background:#eef0ff;color:#3d4bff}
        .stat-icon.amber{background:#fff7e6;color:#cc7a00}
        .stat-icon.green{background:#e9f9f0;color:#16a34a}
        .stat-icon.teal{background:#e4f7f5;color:#0d7d72}
        .stat-icon.purple{background:#f3f0ff;color:#7c3aed}
        .stat-icon.red{background:#fff5f5;color:#dc2626}
        .stat-icon.indigo{background:#eef0ff;color:#4f46e5}
        .stat-trend{font-size:10.5px;font-weight:600;padding:2px 8px;border-radius:20px}
        .trend-up{background:#e9f9f0;color:#16a34a}
        .trend-warn{background:#fef3c7;color:#d97706}
        .trend-danger{background:#fff5f5;color:#dc2626}
        .stat-value{font-size:22px;font-weight:800;line-height:1}
        .stat-value.sm{font-size:15px;font-weight:700}
        .stat-label{font-size:11.5px;color:#6b7280}

        .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px}
        .card-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
        .card-title{font-size:13px;font-weight:700}
        .card-link{font-size:12px;color:#3d4bff;font-weight:500}
        .card-link:hover{text-decoration:underline}

        .card-clickable{cursor:pointer;transition:box-shadow .15s,border-color .15s}
        .card-clickable:hover{border-color:#a5b4fc;box-shadow:0 0 0 3px #eef0ff}

        .row2{display:grid;grid-template-columns:1.4fr 1fr;gap:12px}

        /* Pending user row */
        .urow{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid #f0f1f3}
        .urow:last-child{border-bottom:none;padding-bottom:0}
        .uav{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:600;flex-shrink:0}
        .uname{font-size:13px;font-weight:500}
        .umeta{font-size:11px;color:#9ca3af}
        .upill{font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;background:#fef3c7;color:#d97706;white-space:nowrap}
        .ubtn{font-size:11.5px;font-weight:600;background:#3d4bff;color:#fff;border:none;padding:5px 12px;border-radius:7px;cursor:pointer;white-space:nowrap;font-family:inherit;transition:background .15s}
        .ubtn:hover{background:#2c38d4}

        /* Chart */
        .chart-wrap{display:flex;flex-direction:column;gap:8px}
        .bar-row{display:flex;align-items:center;gap:8px}
        .bar-label{font-size:11px;color:#9ca3af;width:28px;text-align:right;flex-shrink:0}
        .bar-track{flex:1;background:#f0f1f5;border-radius:20px;height:7px;overflow:hidden}
        .bar-fill{height:100%;border-radius:20px;background:#3d4bff;transition:width .4s ease}
        .bar-val{font-size:11px;font-weight:600;color:#6b7280;width:22px;text-align:right;flex-shrink:0}
        .chart-footer{margin-top:12px;padding-top:10px;border-top:1px solid #f0f1f3;display:flex;align-items:center;justify-content:center;gap:6px;font-size:12px;color:#3d4bff;font-weight:600}

        .row3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}

        /* Item row */
        .irow{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid #f0f1f3}
        .irow:last-child{border-bottom:none;padding-bottom:0}
        .ithumb{width:40px;height:40px;border-radius:8px;background:#f4f5f7;flex-shrink:0;display:flex;align-items:center;justify-content:center;overflow:hidden}
        .ithumb img{width:100%;height:100%;object-fit:cover}
        .ithumb i{font-size:18px;color:#c9ccd4}
        .iname{font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .iloc{font-size:11px;color:#9ca3af}
        .iprice{font-size:12px;font-weight:600;color:#3d4bff;white-space:nowrap}

        /* Activity row */
        .arow{display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid #f0f1f3}
        .arow:last-child{border-bottom:none;padding-bottom:0}
        .adot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:5px}
        .atext{font-size:12.5px;flex:1;line-height:1.5}
        .atime{font-size:11px;color:#9ca3af;white-space:nowrap}

        /* Quick actions */
        .qa{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;border:1px solid #e5e7eb;background:#f4f5f7;margin-bottom:8px;transition:background .15s}
        .qa:last-child{margin-bottom:0}
        .qa:hover{background:#eef0ff;border-color:#c7cbff}
        .qa i{font-size:18px;color:#3d4bff}
        .qa span{font-size:13px;font-weight:500}

        /* Pending item row (approval) */
        .pirow{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid #f0f1f3}
        .pirow:last-child{border-bottom:none;padding-bottom:0}
        .pithumb{width:36px;height:36px;border-radius:8px;background:#f4f5f7;flex-shrink:0;display:flex;align-items:center;justify-content:center;overflow:hidden}
        .pithumb img{width:100%;height:100%;object-fit:cover}
        .pithumb i{font-size:16px;color:#c9ccd4}

        .empty-state{text-align:center;padding:24px;color:#9ca3af;font-size:13px}

        @media(max-width:1100px){.stats-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:800px){.stats-grid{grid-template-columns:repeat(2,1fr)}.row2,.row3{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div style="display:flex;min-height:100vh;">
    <?php include 'sidebar.php'; ?>
    <div class="main" style="flex:1;min-width:0;">

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

        <div class="content">

            <!-- Urgent banner -->
            <?php if ($urgent_count > 0): ?>
            <a href="<?= $pending_reports > 0 ? 'reports.php' : ($pending_items > 0 ? 'barangapproval.php' : 'users.php') ?>" class="urgent-banner">
                <i class="ti ti-alert-triangle"></i>
                <div class="urgent-banner-text">
                    <strong>Perlu perhatian admin</strong>
                    <span>
                        <?php
                        $parts = [];
                        if ($pending_users > 0) $parts[] = "$pending_users user pending";
                        if ($pending_items > 0) $parts[] = "$pending_items barang perlu approval";
                        if ($pending_reports > 0) $parts[] = "$pending_reports laporan pending";
                        echo implode(' · ', $parts);
                        ?>
                    </span>
                </div>
                <span class="urgent-badge"><?= $urgent_count ?></span>
            </a>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon blue"><i class="ti ti-users"></i></div>
                        <?php if ($pending_users > 0): ?><span class="stat-trend trend-warn"><?= $pending_users ?> baru</span><?php endif; ?>
                    </div>
                    <div class="stat-value"><?= $total_users ?></div>
                    <div class="stat-label">Total users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon green"><i class="ti ti-box-seam"></i></div>
                        <?php if ($pending_items > 0): ?><span class="stat-trend trend-warn"><?= $pending_items ?> baru</span><?php endif; ?>
                    </div>
                    <div class="stat-value"><?= $total_items ?></div>
                    <div class="stat-label">Total barang</div>
                </div>
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon teal"><i class="ti ti-shopping-cart-check"></i></div>
                        <span class="stat-trend trend-up"><?= $total_rentals ?> total</span>
                    </div>
                    <div class="stat-value"><?= $total_rentals ?></div>
                    <div class="stat-label">Total rental</div>
                </div>
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon red"><i class="ti ti-flag"></i></div>
                        <?php if ($pending_reports > 0): ?><span class="stat-trend trend-danger"><?= $pending_reports ?> baru</span><?php endif; ?>
                    </div>
                    <div class="stat-value"><?= $pending_reports ?></div>
                    <div class="stat-label">Laporan pending</div>
                </div>
            </div>

            <!-- Revenue row -->
            <div class="stats-grid" style="grid-template-columns:repeat(2,1fr);">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon purple"><i class="ti ti-cash"></i></div>
                        <span class="stat-trend trend-up">7 hari</span>
                    </div>
                    <div class="stat-value sm">Rp <?= number_format($revenue_minggu, 0, ',', '.') ?></div>
                    <div class="stat-label">Revenue minggu ini</div>
                </div>
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon indigo"><i class="ti ti-cash"></i></div>
                        <span class="stat-trend trend-up">total</span>
                    </div>
                    <div class="stat-value sm">Rp <?= number_format($revenue_total, 0, ',', '.') ?></div>
                    <div class="stat-label">Revenue keseluruhan</div>
                </div>
            </div>

            <!-- Row 2: pending users + bar chart -->
            <div class="row2">
                <!-- Pending users -->
                <div class="card">
                    <div class="card-hd">
                        <span class="card-title">User menunggu approval</span>
                        <a href="users.php" class="card-link">Lihat semua &rarr;</a>
                    </div>
                    <?php if (empty($pending_list)): ?>
                        <div class="empty-state">
                            <i class="ti ti-circle-check" style="font-size:26px;display:block;margin-bottom:6px;color:#16a34a;"></i>
                            Semua user sudah diproses
                        </div>
                    <?php else: ?>
                        <?php foreach ($pending_list as $u):
                            $c    = $avatar_colors[abs(crc32($u['username'])) % 5];
                            $init = strtoupper(substr($u['username'] ?? '?', 0, 2));
                        ?>
                        <div class="urow">
                            <div class="uav" style="background:<?= $c[0] ?>;color:<?= $c[1] ?>;"><?= $init ?></div>
                            <div style="flex:1;min-width:0;">
                                <div class="uname"><?= htmlspecialchars($u['username']) ?></div>
                                <div class="umeta">ID: <?= $u['id'] ?></div>
                            </div>
                            <span class="upill">Pending</span>
                            <a href="user_detail.php?id=<?= $u['id'] ?>" class="ubtn" style="text-decoration:none;">Review</a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Bar chart: click → rentals.php -->
                <div class="card card-clickable" onclick="window.location.href='rentals.php'">
                    <div class="card-hd">
                        <span class="card-title">Rental 7 hari terakhir</span>
                        <span class="card-link">Lihat semua &rarr;</span>
                    </div>
                    <div class="chart-wrap">
                        <?php foreach ($days_map as $tgl => $cnt):
                            $pct = round($cnt / $max_weekly * 100);
                            $nama_hari = $hari_id[date('w', strtotime($tgl))];
                        ?>
                        <div class="bar-row">
                            <span class="bar-label"><?= $nama_hari ?></span>
                            <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%;"></div></div>
                            <span class="bar-val"><?= $cnt ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="chart-footer"><i class="ti ti-arrow-right"></i> Buka halaman rentals</div>
                </div>
            </div>

            <!-- Row 3: pending items + activity + quick actions -->
            <div class="row3">
                <!-- Pending barang approval -->
                <div class="card">
                    <div class="card-hd">
                        <span class="card-title">Barang perlu approval</span>
                        <a href="barangapproval.php" class="card-link">Lihat semua &rarr;</a>
                    </div>
                    <?php if (empty($pending_items_list)): ?>
                        <div class="empty-state">
                            <i class="ti ti-circle-check" style="font-size:26px;display:block;margin-bottom:6px;color:#16a34a;"></i>
                            Semua barang sudah diproses
                        </div>
                    <?php else: ?>
                        <?php foreach ($pending_items_list as $item):
                            $g    = getFirstGambar($item);
                            $icon = getItemIcon($item['nama_barang'] ?? '');
                        ?>
                        <div class="pirow">
                            <div class="pithumb">
                                <?php if ($g): ?><img src="<?= htmlspecialchars($g) ?>" alt=""><?php else: ?><i class="ti <?= $icon ?>"></i><?php endif; ?>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div class="iname"><?= htmlspecialchars($item['nama_barang'] ?? '-') ?></div>
                                <div class="iloc">oleh <?= htmlspecialchars($item['owner'] ?? '-') ?></div>
                            </div>
                            <span class="upill">Review</span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Aktivitas terkini -->
                <div class="card">
                    <div class="card-hd"><span class="card-title">Aktivitas terkini</span></div>
                    <?php if (empty($activities)): ?>
                        <div class="empty-state">Belum ada aktivitas</div>
                    <?php else: ?>
                        <?php foreach ($activities as $act): ?>
                        <div class="arow">
                            <div class="adot" style="background:<?= warnaDot($act['warna']) ?>;"></div>
                            <div class="atext"><?= htmlspecialchars($act['keterangan']) ?></div>
                            <div class="atime"><?= timeAgo($act['created_at']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Quick actions -->
                <div class="card">
                    <div class="card-hd"><span class="card-title">Quick actions</span></div>
                    <?php if ($pending_reports > 0): ?>
                    <a href="reports.php" class="qa" style="border-color:#fecaca;background:#fff5f5;"><i class="ti ti-flag" style="color:#dc2626;"></i><span style="color:#dc2626;font-weight:600;">Tinjau Laporan (<?= $pending_reports ?>)</span></a>
                    <?php endif; ?>
                    <?php if ($pending_users > 0): ?>
                    <a href="users.php" class="qa"><i class="ti ti-user-check"></i><span>Approve Users (<?= $pending_users ?>)</span></a>
                    <?php endif; ?>
                    <?php if ($pending_items > 0): ?>
                    <a href="barangapproval.php" class="qa"><i class="ti ti-package"></i><span>Approval Barang (<?= $pending_items ?>)</span></a>
                    <?php endif; ?>
                    <a href="users.php" class="qa"><i class="ti ti-users"></i><span>Kelola Users</span></a>
                    <a href="barang.php" class="qa"><i class="ti ti-box-seam"></i><span>Kelola Barang</span></a>
                    <a href="rentals.php" class="qa"><i class="ti ti-shopping-cart"></i><span>Kelola Rentals</span></a>
                    <a href="pencairan.php" class="qa"><i class="ti ti-cash"></i><span>Pencairan Dana</span></a>
                    <a href="../index.php" class="qa"><i class="ti ti-eye"></i><span>Lihat Situs</span></a>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>