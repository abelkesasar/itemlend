<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') != 'admin') {
    header("Location: ../index.php?page=login");
    exit;
}

// ── Stats
$total_users   = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pending_users = $conn->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$total_items   = $conn->query("SELECT COUNT(*) FROM items")->fetchColumn();
$total_rentals = $conn->query("SELECT COUNT(*) FROM rentals")->fetchColumn();

// ── Revenue minggu ini
$revenue_minggu = $conn->query("
    SELECT COALESCE(SUM(total_harga), 0)
    FROM rentals
    WHERE status_pembayaran = 'lunas'
    AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
")->fetchColumn();

// ── Pending users (latest 5)
$pending_list = $conn->query("SELECT * FROM users WHERE status='pending' ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// ── Latest items (latest 3)
$latest_items = $conn->query("SELECT * FROM items ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

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
    FROM rentals
    ORDER BY created_at DESC LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

$act_lunas = $conn->query("
    SELECT CONCAT('Pembayaran #', LPAD(id,6,'0'), ' dikonfirmasi') AS keterangan,
           paid_at AS created_at, 'success' AS warna
    FROM rentals
    WHERE status_pembayaran='lunas' AND paid_at IS NOT NULL
    ORDER BY paid_at DESC LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

$activities = array_merge($act_rentals, $act_lunas);
usort($activities, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
$activities = array_slice($activities, 0, 6);

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ItemLend</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f4f5f7; color: #1a1d2e; min-height: 100vh; }
        a { text-decoration: none; color: inherit; }

        .topbar { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 0 28px; height: 60px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
        .topbar h1 { font-size: 17px; font-weight: 600; }
        .topbar p  { font-size: 12px; color: #6b7280; margin-top: 1px; }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .admin-pill { background: #eef0ff; color: #3d4bff; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
        .avatar { width: 32px; height: 32px; border-radius: 50%; background: #3d4bff; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; }

        .content { padding: 24px 28px; display: flex; flex-direction: column; gap: 16px; }

        /* Stats - sekarang 5 kolom */
        .stats-grid { display: grid; grid-template-columns: repeat(5,1fr); gap: 12px; }
        .stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px; display: flex; flex-direction: column; gap: 10px; }
        .stat-top { display: flex; align-items: center; justify-content: space-between; }
        .stat-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .stat-icon i { font-size: 19px; }
        .stat-icon.blue   { background: #eef0ff; color: #3d4bff; }
        .stat-icon.amber  { background: #fff7e6; color: #cc7a00; }
        .stat-icon.green  { background: #e9f9f0; color: #16a34a; }
        .stat-icon.teal   { background: #e4f7f5; color: #0d7d72; }
        .stat-icon.purple { background: #f3f0ff; color: #7c3aed; }
        .stat-trend { font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 20px; }
        .trend-up  { background: #e9f9f0; color: #16a34a; }
        .trend-new { background: #fef3c7; color: #d97706; }
        .stat-value { font-size: 24px; font-weight: 700; line-height: 1; }
        .stat-value.small { font-size: 15px; }
        .stat-label { font-size: 12px; color: #6b7280; }

        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px; }
        .card-hd { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
        .card-title { font-size: 13px; font-weight: 600; }
        .card-link { font-size: 12px; color: #3d4bff; font-weight: 500; }
        .card-link:hover { text-decoration: underline; }

        /* Card chart bisa diklik */
        .card-clickable { cursor: pointer; transition: box-shadow 0.15s, border-color 0.15s; }
        .card-clickable:hover { border-color: #a5b4fc; box-shadow: 0 0 0 3px #eef0ff; }

        .row2 { display: grid; grid-template-columns: 1.4fr 1fr; gap: 12px; }

        .urow { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid #f0f1f3; }
        .urow:last-child { border-bottom: none; padding-bottom: 0; }
        .uav { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; flex-shrink: 0; }
        .uname { font-size: 13px; font-weight: 500; }
        .umeta { font-size: 11px; color: #9ca3af; }
        .upill { font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 20px; background: #fef3c7; color: #d97706; white-space: nowrap; }
        .ubtn { font-size: 11.5px; font-weight: 600; background: #3d4bff; color: #fff; border: none; padding: 5px 12px; border-radius: 7px; cursor: pointer; white-space: nowrap; font-family: inherit; transition: background 0.15s; }
        .ubtn:hover { background: #2c38d4; }

        .chart-wrap { display: flex; flex-direction: column; gap: 8px; }
        .bar-row { display: flex; align-items: center; gap: 8px; }
        .bar-label { font-size: 11px; color: #9ca3af; width: 28px; text-align: right; flex-shrink: 0; }
        .bar-track { flex: 1; background: #f0f1f5; border-radius: 20px; height: 7px; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 20px; background: #3d4bff; transition: width 0.4s ease; }
        .bar-val { font-size: 11px; font-weight: 600; color: #6b7280; width: 22px; text-align: right; flex-shrink: 0; }
        .chart-footer { margin-top: 12px; padding-top: 10px; border-top: 1px solid #f0f1f3; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12px; color: #3d4bff; font-weight: 600; }
        .chart-footer i { font-size: 14px; }

        .row3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; }

        .irow { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid #f0f1f3; }
        .irow:last-child { border-bottom: none; padding-bottom: 0; }
        .ithumb { width: 40px; height: 40px; border-radius: 8px; background: #f4f5f7; flex-shrink: 0; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .ithumb img { width: 100%; height: 100%; object-fit: cover; }
        .ithumb i { font-size: 18px; color: #c9ccd4; }
        .iname { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .iloc { font-size: 11px; color: #9ca3af; }
        .iprice { font-size: 12px; font-weight: 600; color: #3d4bff; white-space: nowrap; }

        .arow { display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; border-bottom: 1px solid #f0f1f3; }
        .arow:last-child { border-bottom: none; padding-bottom: 0; }
        .adot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
        .atext { font-size: 12.5px; flex: 1; line-height: 1.5; }
        .atime { font-size: 11px; color: #9ca3af; white-space: nowrap; }

        .qa { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 10px; border: 1px solid #e5e7eb; background: #f4f5f7; margin-bottom: 8px; transition: background 0.15s; }
        .qa:last-child { margin-bottom: 0; }
        .qa:hover { background: #eef0ff; border-color: #c7cbff; }
        .qa i { font-size: 18px; color: #3d4bff; }
        .qa span { font-size: 13px; font-weight: 500; }

        .empty-state { text-align: center; padding: 24px; color: #9ca3af; font-size: 13px; }

        @media (max-width: 1100px) {
            .stats-grid { grid-template-columns: repeat(3,1fr); }
        }
        @media (max-width: 800px) {
            .stats-grid { grid-template-columns: repeat(2,1fr); }
            .row2, .row3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="admin-wrap" style="display:flex;min-height:100vh;">

    <?php include 'sidebar.php'; ?>

    <div class="main" style="flex:1;min-width:0;">

        <div class="topbar">
            <div>
                <h1>Admin Dashboard</h1>
                <p><?php echo date('l, d F Y'); ?></p>
            </div>
            <div class="topbar-right">
                <span class="admin-pill">Admin</span>
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?></div>
            </div>
        </div>

        <div class="content">

            <!-- Stats (5 kartu) -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon blue"><i class="ti ti-users"></i></div>
                        <span class="stat-trend trend-up">aktif</span>
                    </div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon amber"><i class="ti ti-user-plus"></i></div>
                        <?php if ($pending_users > 0): ?>
                        <span class="stat-trend trend-new"><?php echo $pending_users; ?> baru</span>
                        <?php endif; ?>
                    </div>
                    <div class="stat-value"><?php echo $pending_users; ?></div>
                    <div class="stat-label">Pending approval</div>
                </div>
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon green"><i class="ti ti-box-seam"></i></div>
                        <span class="stat-trend trend-up">terdaftar</span>
                    </div>
                    <div class="stat-value"><?php echo $total_items; ?></div>
                    <div class="stat-label">Total barang</div>
                </div>
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon teal"><i class="ti ti-shopping-cart-check"></i></div>
                        <span class="stat-trend trend-up">transaksi</span>
                    </div>
                    <div class="stat-value"><?php echo $total_rentals; ?></div>
                    <div class="stat-label">Total rental</div>
                </div>
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon purple"><i class="ti ti-cash"></i></div>
                        <span class="stat-trend trend-up">7 hari</span>
                    </div>
                    <div class="stat-value small">Rp <?php echo number_format($revenue_minggu, 0, ',', '.'); ?></div>
                    <div class="stat-label">Revenue minggu ini</div>
                </div>
            </div>

            <!-- Row 2: pending + bar chart -->
            <div class="row2">
                <div class="card">
                    <div class="card-hd">
                        <span class="card-title">Pending approvals</span>
                        <a href="users.php" class="card-link">Lihat semua &rarr;</a>
                    </div>
                    <?php if (empty($pending_list)): ?>
                        <div class="empty-state">
                            <i class="ti ti-circle-check" style="font-size:26px;display:block;margin-bottom:6px;color:#16a34a;"></i>
                            Tidak ada user yang menunggu
                        </div>
                    <?php else: ?>
                        <?php foreach ($pending_list as $u):
                            $c    = $avatar_colors[abs(crc32($u['username'])) % 5];
                            $init = strtoupper(substr($u['username'] ?? '?', 0, 2));
                        ?>
                        <div class="urow">
                            <div class="uav" style="background:<?php echo $c[0]; ?>;color:<?php echo $c[1]; ?>;"><?php echo $init; ?></div>
                            <div style="flex:1;min-width:0;">
                                <div class="uname"><?php echo htmlspecialchars($u['username']); ?></div>
                                <div class="umeta">ID: <?php echo $u['id']; ?></div>
                            </div>
                            <span class="upill">Pending</span>
                            <a href="../actions/approve_user.php?id=<?php echo $u['id']; ?>">
                                <button class="ubtn">Approve</button>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Card chart: klik → rentals.php -->
                <div class="card card-clickable" onclick="window.location.href='rentals.php'">
                    <div class="card-hd">
                        <span class="card-title">Rental 7 hari terakhir</span>
                        <span class="card-link">Lihat semua &rarr;</span>
                    </div>
                    <div class="chart-wrap">
                        <?php foreach ($days_map as $tgl => $cnt):
                            $pct       = round($cnt / $max_weekly * 100);
                            $nama_hari = $hari_id[date('w', strtotime($tgl))];
                        ?>
                        <div class="bar-row">
                            <span class="bar-label"><?php echo $nama_hari; ?></span>
                            <div class="bar-track"><div class="bar-fill" style="width:<?php echo $pct; ?>%;"></div></div>
                            <span class="bar-val"><?php echo $cnt; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="chart-footer">
                        <i class="ti ti-arrow-right"></i> Buka halaman rentals
                    </div>
                </div>
            </div>

            <!-- Row 3: items + activity + quick actions -->
            <div class="row3">
                <div class="card">
                    <div class="card-hd">
                        <span class="card-title">Barang terbaru</span>
                        <a href="barang.php" class="card-link">Semua &rarr;</a>
                    </div>
                    <?php if (empty($latest_items)): ?>
                        <div class="empty-state">Belum ada barang</div>
                    <?php else: ?>
                        <?php foreach ($latest_items as $item):
                            $g    = getFirstGambar($item);
                            $icon = getItemIcon($item['nama_barang'] ?? '');
                        ?>
                        <div class="irow">
                            <div class="ithumb">
                                <?php if ($g): ?>
                                    <img src="<?php echo htmlspecialchars($g); ?>" alt="">
                                <?php else: ?>
                                    <i class="ti <?php echo $icon; ?>"></i>
                                <?php endif; ?>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div class="iname"><?php echo htmlspecialchars($item['nama_barang'] ?? '-'); ?></div>
                                <div class="iloc"><?php echo htmlspecialchars($item['lokasi'] ?? '-'); ?></div>
                            </div>
                            <span class="iprice">Rp<?php echo number_format($item['harga'] ?? 0, 0, ',', '.'); ?>/hr</span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="card-hd">
                        <span class="card-title">Aktivitas terkini</span>
                    </div>
                    <?php if (empty($activities)): ?>
                        <div class="empty-state">Belum ada aktivitas</div>
                    <?php else: ?>
                        <?php foreach ($activities as $act): ?>
                        <div class="arow">
                            <div class="adot" style="background:<?php echo warnaDot($act['warna']); ?>;"></div>
                            <div class="atext"><?php echo htmlspecialchars($act['keterangan']); ?></div>
                            <div class="atime"><?php echo timeAgo($act['created_at']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="card-hd">
                        <span class="card-title">Quick actions</span>
                    </div>
                    <a href="users.php" class="qa"><i class="ti ti-user-check"></i><span>Approve users</span></a>
                    <a href="barang.php" class="qa"><i class="ti ti-box-seam"></i><span>Kelola barang</span></a>
                    <a href="rentals.php" class="qa"><i class="ti ti-shopping-cart"></i><span>Kelola rentals</span></a>
                    <a href="../index.php" class="qa"><i class="ti ti-eye"></i><span>Lihat situs</span></a>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>