<?php
// admin/sidebar.php
// Cara pakai: include 'sidebar.php';
// Syarat: session_start() dan require db.php sudah dipanggil sebelumnya.

// Hanya query jika $pending_users belum di-set oleh halaman pemanggil
if (!isset($pending_users)) {
    $pending_users = $conn->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
}

// Hitung total notifikasi untuk badge
if (!isset($total_notifikasi)) {
    $c_u = (int)$conn->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
    $c_i = (int)$conn->query("SELECT COUNT(*) FROM items WHERE status='pending'")->fetchColumn();
    $c_p = (int)$conn->query("SELECT COUNT(*) FROM rentals WHERE status_pembayaran='menunggu_konfirmasi' AND bukti_pembayaran IS NOT NULL")->fetchColumn();
    $c_r = (int)$conn->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn();
    $total_notifikasi = $c_u + $c_i + $c_p + $c_r;
}

// Tentukan halaman aktif otomatis berdasarkan nama file
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
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
        display: flex; align-items: center; gap: 10px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        text-decoration: none;
    }
    .logo-icon {
        width: 32px; height: 32px;
        background: #3d4bff; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .logo-icon i { font-size: 17px; color: #fff; }
    .logo-text { font-size: 16px; font-weight: 600; color: #fff; }

    .sidebar-section {
        padding: 18px 20px 6px;
        font-size: 10px; font-weight: 600;
        color: rgba(255,255,255,0.3);
        letter-spacing: 0.08em; text-transform: uppercase;
    }
    .nav-item {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 12px; margin: 1px 8px; border-radius: 8px;
        font-size: 13.5px; color: rgba(255,255,255,0.6);
        text-decoration: none;
        transition: background 0.15s, color 0.15s;
    }
    .nav-item:hover { background: rgba(255,255,255,0.07); color: #fff; }
    .nav-item.active { background: #3d4bff; color: #fff; }
    .nav-item i { font-size: 18px; flex-shrink: 0; }
    .nav-badge {
        margin-left: auto;
        background: #ff5c5c; color: #fff;
        font-size: 10px; font-weight: 600;
        border-radius: 20px; padding: 1px 7px;
    }
    .sidebar-footer {
        margin-top: auto; padding: 12px 8px;
        border-top: 1px solid rgba(255,255,255,0.08);
    }

    /* Dipakai di semua halaman admin */
    .admin-wrap { display: flex; min-height: 100vh; }
    .main { margin-left: 220px; flex: 1; display: flex; flex-direction: column; }

    @media (max-width: 600px) {
        .sidebar { display: none; }
        .main { margin-left: 0; }
    }
</style>

<div class="sidebar">
    <a href="dashboard.php" class="sidebar-logo">
        <div class="logo-icon"><i class="ti ti-briefcase"></i></div>
        <span class="logo-text">ItemLend</span>
    </a>

    <div class="sidebar-section">Main</div>

    <a href="dashboard.php" class="nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
        <i class="ti ti-layout-dashboard"></i> Dashboard
    </a>

    <a href="notifikasi.php" class="nav-item <?= $current_page === 'notifikasi.php' ? 'active' : '' ?>">
        <i class="ti ti-bell"></i> Notifikasi
        <?php if ($total_notifikasi > 0): ?>
            <span class="nav-badge"><?= $total_notifikasi ?></span>
        <?php endif; ?>
    </a>

    <a href="users.php" class="nav-item <?= $current_page === 'users.php' ? 'active' : '' ?>">
        <i class="ti ti-users"></i> User
        <?php if ($pending_users > 0): ?>
            <span class="nav-badge"><?= $pending_users ?></span>
        <?php endif; ?>
    </a>

    <a href="barang.php" class="nav-item <?= $current_page === 'barang.php' ? 'active' : '' ?>">
        <i class="ti ti-box-seam"></i> Items
    </a>

    <a href="rentals.php" class="nav-item <?= $current_page === 'rentals.php' ? 'active' : '' ?>">
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