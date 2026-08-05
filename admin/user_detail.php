<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php?page=login");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: users.php"); exit; }

// --- Data user ---
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { header("Location: users.php"); exit; }

// --- Barang milik user ---
$stmt = $conn->prepare("SELECT id, nama_barang, kategori, harga, stok, status, created_at, gambar, banned_until FROM items WHERE user_id = :uid ORDER BY created_at DESC");
$stmt->execute([':uid' => $id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Transaksi sebagai PEMINJAM ---
$stmt = $conn->prepare("
    SELECT r.id, r.tanggal_mulai, r.tanggal_selesai, r.total_harga,
           r.status_pembayaran, r.status_pinjam, r.status_refund, r.created_at,
           i.nama_barang,
           u_owner.username as pemilik
    FROM rentals r
    LEFT JOIN items i ON r.item_id = i.id
    LEFT JOIN users u_owner ON i.user_id = u_owner.id
    WHERE r.user_id = :uid
    ORDER BY r.created_at DESC
");
$stmt->execute([':uid' => $id]);
$rentals_as_borrower = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Transaksi sebagai PEMILIK ---
$stmt = $conn->prepare("
    SELECT r.id, r.tanggal_mulai, r.tanggal_selesai, r.total_harga,
           r.status_pembayaran, r.status_pinjam, r.status_refund, r.created_at,
           i.nama_barang,
           u_borrower.username as peminjam
    FROM rentals r
    LEFT JOIN items i ON r.item_id = i.id
    LEFT JOIN users u_borrower ON r.user_id = u_borrower.id
    WHERE i.user_id = :uid
    ORDER BY r.created_at DESC
");
$stmt->execute([':uid' => $id]);
$rentals_as_owner = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Total revenue (kecuali yang direfund) ---
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(r.total_harga), 0) as total_revenue,
           COUNT(*) as total_transaksi_masuk
    FROM rentals r
    LEFT JOIN items i ON r.item_id = i.id
    WHERE i.user_id = :uid AND r.status_pembayaran = 'lunas'
    AND (r.status_refund IS NULL OR r.status_refund = 'tidak_ada')
");
$stmt->execute([':uid' => $id]);
$revenue_data = $stmt->fetch(PDO::FETCH_ASSOC);

// --- Laporan yang melibatkan user ini ---
$stmt = $conn->prepare("
    SELECT rp.id, rp.reason, rp.status, rp.created_at, rp.ganti_rugi_amount,
           rp.tagihan_ganti_rugi, rp.bukti_refund_admin,
           rt.id AS rental_id,
           i.nama_barang
    FROM reports rp
    JOIN rentals rt ON rt.id = rp.target_id
    JOIN items i ON i.id = rt.item_id
    WHERE rp.reporter_id = :uid OR rt.user_id = :uid2 OR i.user_id = :uid3
    ORDER BY rp.created_at DESC
");
$stmt->execute([':uid' => $id, ':uid2' => $id, ':uid3' => $id]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pending_users = $conn->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();

// Helpers
function isBanned(?string $until): bool {
    return !empty($until) && strtotime($until) > time();
}
function isPermanentBan(?string $until): bool {
    return !empty($until) && strtotime($until) > strtotime('+3 years');
}
function cooldownRemaining(?string $until): string {
    if (empty($until)) return '-';
    $diff = strtotime($until) - time();
    if ($diff <= 0) return 'Selesai';
    $days = floor($diff / 86400);
    $hours = floor(($diff % 86400) / 3600);
    $mins = floor(($diff % 3600) / 60);
    if ($days > 0) return "{$days}h {$hours}j {$mins}m";
    if ($hours > 0) return "{$hours}j {$mins}m";
    return "{$mins}m";
}

function statusBadge($status) {
    $map = [
        'approved'        => ['badge-approved', 'ti-circle-check', 'Approved'],
        'pending'         => ['badge-pending',  'ti-clock',        'Pending'],
        'cooldown'        => ['badge-cooldown', 'ti-clock',        'Cooldown'],
        'lunas'           => ['badge-approved', 'ti-circle-check', 'Lunas'],
        'selesai'         => ['badge-approved', 'ti-circle-check', 'Selesai'],
        'sedang_dipinjam' => ['badge-pinjam',   'ti-clock-play',   'Dipinjam'],
        'belum_mulai'     => ['badge-unknown',  'ti-calendar',     'Belum Mulai'],
        'ditolak'         => ['badge-rejected', 'ti-x',            'Ditolak'],
    ];
    $s = $map[$status] ?? ['badge-unknown', 'ti-help', ucfirst(str_replace('_',' ',$status))];
    return "<span class=\"badge {$s[0]}\"><i class=\"ti {$s[1]}\"></i> {$s[2]}</span>";
}

function itemStatusBadge($status) {
    $map = [
        'approved'  => ['badge-approved', 'ti-circle-check', 'Approved'],
        'pending'   => ['badge-pending',  'ti-clock',        'Pending'],
        'rejected'  => ['badge-rejected', 'ti-circle-x',     'Rejected'],
        'cooldown'  => ['badge-cooldown', 'ti-clock',        'Cooldown'],
    ];
    $s = $map[$status] ?? ['badge-unknown', 'ti-help', ucfirst($status)];
    return "<span class=\"badge {$s[0]}\"><i class=\"ti {$s[1]}\"></i> {$s[2]}</span>";
}

function reportStatusBadge($status) {
    $map = [
        'pending'   => ['badge-pending',  'ti-clock',        'Pending'],
        'reviewed'  => ['badge-approved', 'ti-circle-check', 'Selesai'],
        'dismissed' => ['badge-rejected', 'ti-circle-x',     'Ditolak'],
    ];
    $s = $map[$status] ?? ['badge-unknown', 'ti-help', ucfirst($status)];
    return "<span class=\"badge {$s[0]}\"><i class=\"ti {$s[1]}\"></i> {$s[2]}</span>";
}

function refundBadge($status) {
    if (!$status || $status === 'tidak_ada') return '';
    return $status === 'selesai'
        ? '<span class="badge badge-approved"><i class="ti ti-coin-euro"></i> Refund Selesai</span>'
        : '<span class="badge badge-pending"><i class="ti ti-coin-euro"></i> Refund Menunggu</span>';
}

function userInitial($name) { return strtoupper(substr($name ?? '?', 0, 2)); }
function formatRp($n) { return 'Rp ' . number_format($n ?? 0, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail User: <?= htmlspecialchars($user['username']) ?> - ItemLend Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f5f7;color:#1a1d2e;min-height:100vh}
        a{text-decoration:none;color:inherit}
        .admin-wrap{display:flex;min-height:100vh}
        .main{margin-left:220px;flex:1;display:flex;flex-direction:column}

        .topbar{background:#fff;border-bottom:1px solid #e5e7eb;padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
        .topbar-left{display:flex;align-items:center;gap:10px}
        .back-btn{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#6b7280;padding:6px 10px;border-radius:8px;transition:background .12s,color .12s}
        .back-btn:hover{background:#f3f4f6;color:#1a1d2e}
        .topbar-divider{width:1px;height:20px;background:#e5e7eb}
        .topbar-title{font-size:16px;font-weight:700}
        .topbar-right{display:flex;align-items:center;gap:12px}
        .admin-pill{background:#eef0ff;color:#3d4bff;font-size:12px;font-weight:600;padding:4px 12px;border-radius:20px}
        .avatar-sm{width:32px;height:32px;border-radius:50%;background:#3d4bff;color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600}

        .content{padding:24px 28px;display:flex;flex-direction:column;gap:20px}

        /* Profile Hero */
        .profile-hero{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:24px;display:flex;gap:20px;align-items:flex-start}
        .profile-av-wrap{position:relative;flex-shrink:0}
        .profile-av{width:72px;height:72px;border-radius:50%;background:#eef0ff;color:#3d4bff;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;border:3px solid #e0e3ff;object-fit:cover}
        .status-dot{position:absolute;bottom:3px;right:3px;width:14px;height:14px;border-radius:50%;border:2px solid #fff}
        .dot-approved{background:#22c55e}.dot-pending{background:#f59e0b}.dot-cooldown{background:#ef4444}
        .profile-info{flex:1}
        .profile-name{font-size:20px;font-weight:700;margin-bottom:4px}
        .profile-email{font-size:13px;color:#6b7280;margin-bottom:10px}
        .profile-tags{display:flex;flex-wrap:wrap;gap:8px}
        .tag{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:4px 10px;border-radius:20px;background:#f3f4f6;color:#374151}
        .tag-blue{background:#eef0ff;color:#3d4bff}
        .tag-red{background:#fff5f5;color:#dc2626;border:1px solid #fecaca}

        .profile-meta{margin-left:auto;display:flex;flex-direction:column;align-items:flex-end;gap:8px}
        .revenue-box{background:linear-gradient(135deg,#3d4bff 0%,#6c78ff 100%);color:#fff;border-radius:12px;padding:14px 20px;text-align:right;min-width:160px}
        .revenue-label{font-size:11px;font-weight:600;opacity:.8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px}
        .revenue-value{font-size:22px;font-weight:700;line-height:1}
        .revenue-sub{font-size:11px;opacity:.7;margin-top:4px}

        /* Cooldown banner */
        .cooldown-banner{background:#fff5f5;border:1px solid #fecaca;border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:10px}
        .cooldown-banner i{font-size:18px;color:#dc2626;flex-shrink:0}
        .cooldown-banner-text{font-size:13px;color:#991b1b;font-weight:600}
        .cooldown-banner-sub{font-size:11.5px;color:#dc2626;margin-top:2px;font-weight:400}

        /* 2-col */
        .two-col{display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start}

        .card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden}
        .card-head{padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between}
        .card-head-left{display:flex;align-items:center;gap:10px}
        .card-icon{width:32px;height:32px;border-radius:8px;background:#eef0ff;color:#3d4bff;display:flex;align-items:center;justify-content:center;font-size:15px}
        .card-title{font-size:14px;font-weight:700}
        .count-pill{background:#f3f4f6;color:#374151;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px}

        .info-list{padding:4px 0}
        .info-row{display:flex;align-items:flex-start;gap:10px;padding:13px 20px;border-bottom:1px solid #f3f4f6;font-size:13px}
        .info-row:last-child{border-bottom:none}
        .info-icon{width:28px;height:28px;border-radius:7px;background:#f3f4f6;color:#6b7280;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;margin-top:1px}
        .info-key{font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.03em;margin-bottom:2px}
        .info-val{font-size:13.5px;font-weight:500;color:#1a1d2e;word-break:break-all}
        .info-val.muted{color:#9ca3af;font-weight:400}

        .docs-section{padding:16px 20px;display:flex;flex-direction:column;gap:10px}
        .doc-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #e5e7eb;border-radius:10px;text-decoration:none;transition:background .12s,border-color .12s}
        .doc-item:hover{background:#f8f9ff;border-color:#c7ccff}
        .doc-thumb{width:36px;height:36px;border-radius:8px;background:#eef0ff;color:#3d4bff;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0}
        .doc-name{font-size:13px;font-weight:600;color:#1a1d2e;margin-bottom:2px}
        .doc-hint{font-size:11px;color:#9ca3af}
        .doc-arrow{margin-left:auto;color:#9ca3af;font-size:16px}

        table{width:100%;border-collapse:collapse}
        thead tr{background:#f8f9fb;border-bottom:1px solid #e5e7eb}
        thead th{padding:11px 16px;font-size:11px;font-weight:700;color:#6b7280;text-align:left;letter-spacing:.04em;text-transform:uppercase;white-space:nowrap}
        tbody tr{border-bottom:1px solid #f0f1f3;transition:background .1s}
        tbody tr:hover{background:#fafbff}
        tbody tr:last-child{border-bottom:none}
        tbody td{padding:13px 16px;font-size:13px;vertical-align:middle}

        .item-cell{display:flex;align-items:center;gap:10px}
        .item-thumb{width:36px;height:36px;border-radius:8px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;font-size:16px;color:#9ca3af;flex-shrink:0;overflow:hidden}
        .item-thumb img{width:100%;height:100%;object-fit:cover}
        .item-name{font-size:13px;font-weight:600;color:#1a1d2e}
        .item-cat{font-size:11.5px;color:#9ca3af;margin-top:2px}
        .text-money{font-size:13px;font-weight:700;color:#1a7a46}
        .text-muted{color:#9ca3af;font-size:12px}
        .date-cell{font-size:12px;color:#374151}
        .date-range{font-size:11px;color:#9ca3af;margin-top:2px}

        .badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:4px 9px;border-radius:20px;white-space:nowrap}
        .badge-approved{background:#e9f9f0;color:#1a7a46;border:1px solid #a7f3d0}
        .badge-pending{background:#fff7e6;color:#cc7a00;border:1px solid #fed7aa}
        .badge-rejected{background:#fff5f5;color:#dc2626;border:1px solid #fecaca}
        .badge-cooldown{background:#fff7e6;color:#a16207;border:1px solid #fed7aa}
        .badge-pinjam{background:#eff6ff;color:#2563eb;border:1px solid #93c5fd}
        .badge-unknown{background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb}

        .mini-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
        .mini-stat{background:#f8f9fb;border:1px solid #f0f1f3;border-radius:10px;padding:14px 16px}
        .mini-stat-label{font-size:11px;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.03em;margin-bottom:5px}
        .mini-stat-value{font-size:20px;font-weight:700;color:#1a1d2e}

        .tabs-wrap{padding:16px 20px 0;border-bottom:1px solid #e5e7eb;display:flex;gap:0}
        .tab-btn{padding:8px 16px;font-size:13px;font-weight:600;color:#6b7280;background:none;border:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;transition:color .12s,border-color .12s}
        .tab-btn.active{color:#3d4bff;border-bottom-color:#3d4bff}
        .tab-btn:hover:not(.active){color:#374151}
        .tab-content{display:none}.tab-content.active{display:block}

        .empty-state{text-align:center;padding:40px 20px;color:#9ca3af}
        .empty-state i{font-size:32px;display:block;margin-bottom:10px;opacity:.4}
        .empty-state p{font-size:13px}

        @media(max-width:1100px){.two-col{grid-template-columns:1fr}}
        @media(max-width:700px){.main{margin-left:0}.content{padding:16px}.profile-hero{flex-direction:column}.profile-meta{align-items:flex-start;margin-left:0}}
    </style>
</head>
<body>
<div class="admin-wrap">
    <?php include 'sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <a href="users.php" class="back-btn"><i class="ti ti-arrow-left"></i> Kembali</a>
                <div class="topbar-divider"></div>
                <div class="topbar-title">Detail User</div>
            </div>
            <div class="topbar-right">
                <span class="admin-pill">Admin</span>
                <div class="avatar-sm"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
            </div>
        </div>

        <div class="content">

            <!-- Profile Hero -->
            <div class="profile-hero">
                <div class="profile-av-wrap">
                    <?php if (!empty($user['foto_profil']) && $user['foto_profil'] !== 'default.png'): ?>
                        <img class="profile-av" src="../uploads/<?= htmlspecialchars($user['foto_profil']) ?>" alt="">
                    <?php else: ?>
                        <div class="profile-av"><?= userInitial($user['username']) ?></div>
                    <?php endif; ?>
                    <div class="status-dot dot-<?= $user['status'] ?>"></div>
                </div>
                <div class="profile-info">
                    <div class="profile-name"><?= htmlspecialchars($user['username']) ?></div>
                    <div class="profile-email"><?= !empty($user['email']) ? htmlspecialchars($user['email']) : '<span style="color:#9ca3af">Email tidak diisi</span>' ?></div>
                    <div class="profile-tags">
                        <span class="tag tag-blue"><i class="ti ti-shield-half-filled"></i> <?= ucfirst($user['role']) ?></span>
                        <?= statusBadge($user['status']) ?>
                        <span class="tag"><i class="ti ti-id-badge"></i> ID #<?= $user['id'] ?></span>
                        <?php if (isBanned($user['banned_until'] ?? null)): ?>
                            <?php if (isPermanentBan($user['banned_until'])): ?>
                                <span class="tag tag-red"><i class="ti ti-ban"></i> Banned Permanen</span>
                            <?php else: ?>
                                <span class="tag" style="background:#fff7e6;color:#a16207;"><i class="ti ti-clock"></i> Cooldown s/d <?= date('d M Y', strtotime($user['banned_until'])) ?> (<?= cooldownRemaining($user['banned_until']) ?> lagi)</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="profile-meta">
                    <div class="revenue-box">
                        <div class="revenue-label">Total Revenue</div>
                        <div class="revenue-value"><?= formatRp($revenue_data['total_revenue']) ?></div>
                        <div class="revenue-sub"><?= $revenue_data['total_transaksi_masuk'] ?> transaksi lunas masuk</div>
                    </div>
                </div>
            </div>

            <!-- Cooldown banner -->
            <?php if (isBanned($user['banned_until'] ?? null) && !isPermanentBan($user['banned_until'])): ?>
            <div class="cooldown-banner">
                <i class="ti ti-alert-triangle"></i>
                <div>
                    <div class="cooldown-banner-text">User sedang dalam masa cooldown</div>
                    <div class="cooldown-banner-sub">Berakhir: <?= date('d M Y H:i', strtotime($user['banned_until'])) ?> · Sisa: <?= cooldownRemaining($user['banned_until']) ?> · Semua barang ikut dinonaktifkan</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- 2-col -->
            <div class="two-col">
                <!-- Kiri -->
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <div class="card">
                        <div class="card-head">
                            <div class="card-head-left">
                                <div class="card-icon"><i class="ti ti-user"></i></div>
                                <div class="card-title">Data Diri</div>
                            </div>
                        </div>
                        <div class="info-list">
                            <div class="info-row">
                                <div class="info-icon"><i class="ti ti-phone"></i></div>
                                <div><div class="info-key">WhatsApp</div><div class="info-val <?= empty($user['nomor_wa']) ? 'muted' : '' ?>"><?= !empty($user['nomor_wa']) ? htmlspecialchars($user['nomor_wa']) : 'Belum diisi' ?></div></div>
                            </div>
                            <div class="info-row">
                                <div class="info-icon"><i class="ti ti-map-pin"></i></div>
                                <div><div class="info-key">Alamat</div><div class="info-val <?= empty($user['alamat']) ? 'muted' : '' ?>"><?= !empty($user['alamat']) ? htmlspecialchars($user['alamat']) : 'Belum diisi' ?></div></div>
                            </div>
                            <div class="info-row">
                                <div class="info-icon"><i class="ti ti-building-store"></i></div>
                                <div><div class="info-key">Deskripsi Vendor</div><div class="info-val <?= empty($user['deskripsi_vendor']) ? 'muted' : '' ?>"><?= !empty($user['deskripsi_vendor']) ? htmlspecialchars($user['deskripsi_vendor']) : 'Belum diisi' ?></div></div>
                            </div>
                            <div class="info-row">
                                <div class="info-icon"><i class="ti ti-package"></i></div>
                                <div><div class="info-key">Jumlah Barang</div><div class="info-val"><?= count($items) ?> barang</div></div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-head"><div class="card-head-left"><div class="card-icon"><i class="ti ti-file-text"></i></div><div class="card-title">Dokumen</div></div></div>
                        <div class="docs-section">
                            <?php
                            $docs = [
                                'foto_profil' => ['Foto Profil','ti-user-circle','Identitas visual akun'],
                                'ktm'         => ['KTM','ti-id-badge-2','Kartu Tanda Mahasiswa'],
                                'ktp'         => ['KTP','ti-credit-card','Kartu Tanda Penduduk'],
                            ];
                            $has_doc = false;
                            foreach ($docs as $col => [$label,$icon,$hint]):
                                if (empty($user[$col])) continue;
                                $has_doc = true;
                            ?>
                                <a class="doc-item" href="../uploads/<?= htmlspecialchars($user[$col]) ?>" target="_blank">
                                    <div class="doc-thumb"><i class="ti <?= $icon ?>"></i></div>
                                    <div><div class="doc-name"><?= $label ?></div><div class="doc-hint"><?= $hint ?> · Klik untuk lihat</div></div>
                                    <div class="doc-arrow"><i class="ti ti-external-link"></i></div>
                                </a>
                            <?php endforeach; ?>
                            <?php if (!$has_doc): ?><div class="empty-state"><i class="ti ti-file-off"></i><p>Belum ada dokumen diunggah</p></div><?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Kanan -->
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <div class="mini-stats">
                        <div class="mini-stat"><div class="mini-stat-label">Total Barang</div><div class="mini-stat-value"><?= count($items) ?></div></div>
                        <div class="mini-stat"><div class="mini-stat-label">Pernah Meminjam</div><div class="mini-stat-value"><?= count($rentals_as_borrower) ?></div></div>
                        <div class="mini-stat"><div class="mini-stat-label">Penyewaan Masuk</div><div class="mini-stat-value"><?= count($rentals_as_owner) ?></div></div>
                    </div>

                    <div class="card">
                        <div class="tabs-wrap">
                            <button class="tab-btn active" onclick="switchTab(this,'tab-items')"><i class="ti ti-package"></i> Barang (<?= count($items) ?>)</button>
                            <button class="tab-btn" onclick="switchTab(this,'tab-borrow')"><i class="ti ti-shopping-cart"></i> Pinjam (<?= count($rentals_as_borrower) ?>)</button>
                            <button class="tab-btn" onclick="switchTab(this,'tab-income')"><i class="ti ti-trending-up"></i> Masuk (<?= count($rentals_as_owner) ?>)</button>
                            <button class="tab-btn" onclick="switchTab(this,'tab-reports')"><i class="ti ti-flag"></i> Laporan (<?= count($reports) ?>)</button>
                        </div>

                        <!-- Tab: Barang -->
                        <div id="tab-items" class="tab-content active">
                            <?php if (empty($items)): ?>
                                <div class="empty-state"><i class="ti ti-package-off"></i><p>User ini belum memiliki barang</p></div>
                            <?php else: ?>
                            <table>
                                <thead><tr><th>Barang</th><th>Kategori</th><th>Harga/hari</th><th>Stok</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php foreach ($items as $it):
                                    $gambar_arr = json_decode($it['gambar'] ?? '[]', true);
                                    $thumb = !empty($gambar_arr) ? $gambar_arr[0] : null;
                                ?>
                                    <tr>
                                        <td>
                                            <div class="item-cell">
                                                <div class="item-thumb"><?php if ($thumb): ?><img src="../uploads/<?= htmlspecialchars($thumb) ?>" alt=""><?php else: ?><i class="ti ti-photo"></i><?php endif; ?></div>
                                                <div><div class="item-name"><?= htmlspecialchars($it['nama_barang']) ?></div><div class="item-cat">ID #<?= $it['id'] ?></div></div>
                                            </div>
                                        </td>
                                        <td class="text-muted"><?= !empty($it['kategori']) ? htmlspecialchars($it['kategori']) : '-' ?></td>
                                        <td class="text-money"><?= formatRp($it['harga']) ?></td>
                                        <td><?= $it['stok'] ?></td>
                                        <td><?= itemStatusBadge($it['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>

                        <!-- Tab: Pinjam -->
                        <div id="tab-borrow" class="tab-content">
                            <?php if (empty($rentals_as_borrower)): ?>
                                <div class="empty-state"><i class="ti ti-shopping-cart-off"></i><p>Belum pernah meminjam barang</p></div>
                            <?php else: ?>
                            <table>
                                <thead><tr><th>Barang</th><th>Pemilik</th><th>Periode</th><th>Total</th><th>Bayar</th><th>Pinjam</th></tr></thead>
                                <tbody>
                                <?php foreach ($rentals_as_borrower as $r): ?>
                                    <tr>
                                        <td><div class="item-name"><?= htmlspecialchars($r['nama_barang'] ?? '-') ?></div><div class="item-cat">Rental #<?= $r['id'] ?></div></td>
                                        <td class="text-muted"><?= htmlspecialchars($r['pemilik'] ?? '-') ?></td>
                                        <td><div class="date-cell"><?= date('d M Y', strtotime($r['tanggal_mulai'])) ?></div><div class="date-range">s/d <?= date('d M Y', strtotime($r['tanggal_selesai'])) ?></div></td>
                                        <td class="text-money"><?= formatRp($r['total_harga']) ?></td>
                                        <td><?= statusBadge($r['status_pembayaran'] ?? 'pending') ?></td>
                                        <td><?= statusBadge($r['status_pinjam']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>

                        <!-- Tab: Masuk -->
                        <div id="tab-income" class="tab-content">
                            <?php if (empty($rentals_as_owner)): ?>
                                <div class="empty-state"><i class="ti ti-trending-down"></i><p>Belum ada yang menyewa barang user ini</p></div>
                            <?php else: ?>
                            <table>
                                <thead><tr><th>Barang</th><th>Peminjam</th><th>Periode</th><th>Total</th><th>Bayar</th><th>Pinjam</th></tr></thead>
                                <tbody>
                                <?php foreach ($rentals_as_owner as $r): ?>
                                    <tr>
                                        <td><div class="item-name"><?= htmlspecialchars($r['nama_barang'] ?? '-') ?></div><div class="item-cat">Rental #<?= $r['id'] ?></div></td>
                                        <td class="text-muted"><?= htmlspecialchars($r['peminjam'] ?? '-') ?></td>
                                        <td><div class="date-cell"><?= date('d M Y', strtotime($r['tanggal_mulai'])) ?></div><div class="date-range">s/d <?= date('d M Y', strtotime($r['tanggal_selesai'])) ?></div></td>
                                        <td class="text-money"><?= formatRp($r['total_harga']) ?></td>
                                        <td><?= statusBadge($r['status_pembayaran'] ?? 'pending') ?></td>
                                        <td><?= statusBadge($r['status_pinjam']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>

                        <!-- Tab: Laporan -->
                        <div id="tab-reports" class="tab-content">
                            <?php if (empty($reports)): ?>
                                <div class="empty-state"><i class="ti ti-flag-off"></i><p>Tidak ada laporan yang melibatkan user ini</p></div>
                            <?php else: ?>
                            <table>
                                <thead><tr><th>Laporan</th><th>Barang</th><th>Alasan</th><th>Status</th><th>Ganti Rugi</th><th>Tanggal</th></tr></thead>
                                <tbody>
                                <?php foreach ($reports as $rp): ?>
                                    <tr>
                                        <td><div class="item-name">#<?= $rp['id'] ?></div><div class="item-cat">Rental #<?= $rp['rental_id'] ?></div></td>
                                        <td class="text-muted"><?= htmlspecialchars($rp['nama_barang'] ?? '-') ?></td>
                                        <td><div style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($rp['reason']) ?>"><?= htmlspecialchars($rp['reason']) ?></div></td>
                                        <td><?= reportStatusBadge($rp['status']) ?></td>
                                        <td><?php if (!empty($rp['ganti_rugi_amount']) && $rp['ganti_rugi_amount'] > 0): ?><span class="text-money">+<?= formatRp($rp['ganti_rugi_amount']) ?></span><?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
                                        <td><div class="date-cell"><?= date('d M Y', strtotime($rp['created_at'])) ?></div></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(btn, targetId) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(targetId).classList.add('active');
}
</script>
</body>
</html>