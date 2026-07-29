<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background: #f4f5f7; color: #1a1d2e;
        min-height: 100vh;
    }
    a { text-decoration: none; color: inherit; }

    /* ══════════════════════════════
       NAVBAR — 3 kolom seimbang
       kiri: brand | tengah: nav+search | kanan: user
    ══════════════════════════════ */
    .navbar {
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        position: sticky; top: 0; z-index: 300;
    }
    .navbar-inner {
        max-width: 1280px; margin: 0 auto;
        padding: 0 28px; height: 62px;
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 16px;
    }

    /* ── Kiri: brand ── */
    .navbar-left {
        display: flex; align-items: center;
    }
    .navbar-brand {
        display: flex; align-items: center; gap: 8px;
        font-size: 19px; font-weight: 800; color: #3d4bff;
        white-space: nowrap;
    }
    .navbar-brand i { font-size: 21px; }

    /* ── Tengah: nav links + search ── */
    .navbar-center {
        display: flex; align-items: center; gap: 4px;
    }
    .nav-link {
        display: flex; align-items: center; gap: 6px;
        padding: 7px 13px; border-radius: 9px;
        font-size: 13px; font-weight: 600; color: #6b7280;
        white-space: nowrap; transition: all 0.15s;
    }
    .nav-link i { font-size: 17px; }
    .nav-link:hover  { background: #f4f5f7; color: #1a1d2e; }
    .nav-link.active { background: #eef0ff; color: #3d4bff; }

    .nav-divider {
        width: 1px; height: 20px; background: #e5e7eb; margin: 0 4px;
    }

    /* Search pill */
    .nav-search {
        display: flex; align-items: center; gap: 7px;
        background: #f4f5f7; border: 1px solid #e5e7eb;
        border-radius: 20px; padding: 0 14px; height: 36px;
        width: 200px; transition: width 0.2s, border-color 0.15s;
    }
    .nav-search:focus-within {
        width: 240px; border-color: #3d4bff;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(61,75,255,0.08);
    }
    .nav-search i { font-size: 15px; color: #9ca3af; flex-shrink: 0; }
    .nav-search input {
        border: none; outline: none; background: transparent;
        font-family: inherit; font-size: 13px; width: 100%; color: #1a1d2e;
    }
    .nav-search input::placeholder { color: #9ca3af; }

    /* ── Kanan: user area ── */
    .navbar-right {
        display: flex; align-items: center; justify-content: flex-end; gap: 8px;
    }

    /* Guest buttons */
    .btn-nav {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 16px; border-radius: 9px;
        font-family: inherit; font-size: 13px; font-weight: 600;
        cursor: pointer; border: none; transition: all 0.15s;
    }
    .btn-nav-ghost   { background: transparent; color: #1a1d2e; border: 1.5px solid #e5e7eb; }
    .btn-nav-ghost:hover { background: #f4f5f7; }
    .btn-nav-primary { background: #3d4bff; color: #fff; }
    .btn-nav-primary:hover { background: #2c38d4; }

    /* ── User dropdown ── */
    .user-menu { position: relative; }
    .user-trigger {
        display: flex; align-items: center; gap: 8px;
        padding: 5px 10px 5px 5px;
        border: 1.5px solid #e5e7eb; border-radius: 20px;
        cursor: pointer; background: #fff;
        transition: border-color 0.15s, background 0.15s;
        user-select: none;
    }
    .user-trigger:hover { border-color: #3d4bff; background: #fafbff; }
    .user-av {
        width: 30px; height: 30px; border-radius: 50%;
        background: #3d4bff; color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 800; flex-shrink: 0;
    }
    .user-trigger-name {
        font-size: 13px; font-weight: 600; color: #1a1d2e;
        max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .user-chevron { font-size: 16px; color: #9ca3af; transition: transform 0.2s; }
    .user-menu.open .user-chevron    { transform: rotate(180deg); }
    .user-menu.open .user-trigger    { border-color: #3d4bff; background: #fafbff; }

    /* Dropdown panel */
    .user-dropdown {
        position: absolute; top: calc(100% + 8px); right: 0;
        background: #fff; border: 1px solid #e5e7eb;
        border-radius: 14px; width: 220px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        opacity: 0; transform: translateY(-8px) scale(0.98);
        pointer-events: none;
        transition: opacity 0.18s, transform 0.18s;
        z-index: 400;
    }
    .user-menu.open .user-dropdown {
        opacity: 1; transform: translateY(0) scale(1);
        pointer-events: all;
    }
    .dd-header {
        padding: 14px 16px 12px;
        border-bottom: 1px solid #f0f1f3;
    }
    .dd-header-name { font-size: 13.5px; font-weight: 700; color: #1a1d2e; }
    .dd-header-role {
        font-size: 11px; font-weight: 600; color: #6b7280; margin-top: 2px;
        display: flex; align-items: center; gap: 4px;
    }
    .dd-items { padding: 6px; }
    .dd-item {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 10px; border-radius: 9px;
        font-size: 13px; font-weight: 500; color: #374151;
        text-decoration: none; transition: background 0.12s; cursor: pointer;
        border: none; background: transparent; font-family: inherit;
        width: 100%; text-align: left;
    }
    .dd-item i { font-size: 17px; color: #9ca3af; flex-shrink: 0; }
    .dd-item:hover { background: #f4f5f7; color: #1a1d2e; }
    .dd-item:hover i { color: #3d4bff; }
    .dd-item.danger { color: #dc2626; }
    .dd-item.danger i { color: #fca5a5; }
    .dd-item.danger:hover { background: #fff5f5; }
    .dd-item.danger:hover i { color: #dc2626; }
    .dd-divider { height: 1px; background: #f0f1f3; margin: 4px 6px; }
    .dd-badge {
        margin-left: auto; background: #ff5c5c; color: #fff;
        font-size: 10px; font-weight: 700; border-radius: 20px; padding: 1px 7px;
    }

    /* ── Hamburger ── */
    .nav-hamburger {
        display: none; align-items: center; justify-content: center;
        width: 36px; height: 36px; border-radius: 9px;
        border: 1.5px solid #e5e7eb; cursor: pointer; background: #fff;
    }
    .nav-hamburger i { font-size: 19px; color: #374151; }

    /* ── Mobile drawer ── */
    .mobile-drawer {
        display: none; flex-direction: column; gap: 2px;
        padding: 8px 16px 16px;
        background: #fff; border-bottom: 1px solid #e5e7eb;
    }
    .mobile-drawer.open { display: flex; }
    .mobile-search {
        display: flex; align-items: center; gap: 8px;
        background: #f4f5f7; border: 1px solid #e5e7eb;
        border-radius: 10px; padding: 0 12px; height: 40px; margin-bottom: 6px;
    }
    .mobile-search i { color: #9ca3af; font-size: 16px; }
    .mobile-search input {
        border: none; outline: none; background: transparent;
        font-family: inherit; font-size: 13px; width: 100%;
    }
    .mobile-drawer .nav-link { padding: 10px 12px; border-radius: 10px; }
    .mobile-drawer .nav-link.danger { color: #dc2626; }

    /* ── Responsive ── */
    @media (max-width: 860px) {
        .navbar-center { display: none; }
        .nav-hamburger  { display: flex; }
    }
    @media (max-width: 480px) {
        .navbar-inner { padding: 0 16px; gap: 10px; }
        .user-trigger-name { display: none; }
    }
</style>

<nav class="navbar">
    <div class="navbar-inner">

        <!-- KIRI: Brand -->
        <div class="navbar-left">
            <a href="index.php" class="navbar-brand">
                <i class="ti ti-briefcase"></i> ItemLend
            </a>
        </div>

        <!-- TENGAH: Nav links + Search -->
        <div class="navbar-center">
            <a href="index.php"
               class="nav-link <?= (!isset($_GET['page']) || $_GET['page'] === 'home') ? 'active' : '' ?>">
                <i class="ti ti-home"></i> Beranda
            </a>

            <?php if (isset($_SESSION['user'])): ?>
                <a href="index.php?page=tambah_barang"
                   class="nav-link <?= ($_GET['page'] ?? '') === 'tambah_barang' ? 'active' : '' ?>">
                    <i class="ti ti-plus"></i> Jual/Sewa
                </a>
            <?php endif; ?>

            <div class="nav-divider"></div>

            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="home">
                <div class="nav-search">
                    <i class="ti ti-search"></i>
                    <input type="text" name="q"
                           placeholder="Cari barang..."
                           value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                </div>
            </form>
        </div>

        <!-- KANAN: User -->
        <div class="navbar-right">

            <?php if (isset($_SESSION['user'])): ?>
                <?php
                $unm  = $_SESSION['username'] ?? 'User';
                $init = strtoupper(substr($unm, 0, 1));
                $role = $_SESSION['role'] ?? 'user';

                $pending_saya = 0;
                try {
                    $ps = $conn->prepare("
                        SELECT COUNT(*) FROM rentals
                        WHERE user_id = ?
                        AND (status_pembayaran = 'pending' OR status_pembayaran IS NULL)
                    ");
                    $ps->execute([$_SESSION['user']]);
                    $pending_saya = (int) $ps->fetchColumn();
                } catch (Exception $e) {}
                ?>
                    <a href="index.php?page=chat_list" class="btn-nav btn-nav-ghost" style="padding:7px 10px;">
                    <i class="ti ti-message-circle"></i>
                    </a>
                <div class="user-menu" id="userMenu">
                    <div class="user-trigger" onclick="toggleUserMenu()">
                        <div class="user-av"><?= $init ?></div>
                        <span class="user-trigger-name"><?= htmlspecialchars($unm) ?></span>
                        <i class="ti ti-chevron-down user-chevron"></i>
                    </div>

                    <div class="user-dropdown">
                        <div class="dd-header">
                            <div class="dd-header-name"><?= htmlspecialchars($unm) ?></div>
                            <div class="dd-header-role">
                                <i class="ti ti-shield" style="font-size:11px;"></i>
                                <?= ucfirst($role) ?>
                            </div>
                        </div>
                        <div class="dd-items">
                            <a href="index.php?page=profile" class="dd-item">
                                <i class="ti ti-user"></i> Profil Saya
                            </a>

                            <div class="dd-divider"></div>

                            <a href="index.php?page=barangsaya" class="dd-item">
                            <i class="ti ti-building-store"></i> Toko Saya
                            </a>
                            <a href="index.php?page=pesanansaya" class="dd-item">
                                <i class="ti ti-clipboard-list"></i> Pesanan Saya
                            </a>
                    
                            <?php if ($role === 'admin'): ?>
                                <div class="dd-divider"></div>
                                <a href="admin/dashboard.php" class="dd-item">
                                    <i class="ti ti-layout-dashboard"></i> Admin Panel
                                </a>
                            <?php endif; ?>

                            <div class="dd-divider"></div>
                            <a href="actions/logout.php" class="dd-item danger">
                                <i class="ti ti-logout"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <a href="index.php?page=login"    class="btn-nav btn-nav-ghost">Login</a>
                <a href="index.php?page=register" class="btn-nav btn-nav-primary">Daftar</a>
            <?php endif; ?>

            <!-- Hamburger -->
            <div class="nav-hamburger" onclick="toggleMobileNav()">
                <i class="ti ti-menu-2"></i>
            </div>

        </div>
    </div>

    <!-- Mobile Drawer -->
    <div class="mobile-drawer" id="mobileDrawer">
        <form method="GET" action="index.php" class="mobile-search">
            <input type="hidden" name="page" value="home">
            <i class="ti ti-search"></i>
            <input type="text" name="q" placeholder="Cari barang..."
                   value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
        </form>

        <a href="index.php" class="nav-link <?= (!isset($_GET['page']) || $_GET['page'] === 'home') ? 'active' : '' ?>">
            <i class="ti ti-home"></i> Beranda
        </a>

        <?php if (isset($_SESSION['user'])): ?>
            <a href="index.php?page=tambah_barang"   class="nav-link"><i class="ti ti-plus"></i> Jual/Sewa Barang</a>
            <div class="dd-divider" style="margin:4px 0;"></div>
            <a href="index.php?page=barangsaya"      class="nav-link"><i class="ti ti-building-store"></i> Toko Saya</a>
            <a href="index.php?page=pesanansaya"     class="nav-link"><i class="ti ti-clipboard-list"></i> Pesanan Saya</a>
            
            <div class="dd-divider" style="margin:4px 0;"></div>
            <a href="index.php?page=profile"         class="nav-link"><i class="ti ti-user"></i> Profil</a>
            <div class="dd-divider" style="margin:4px 0;"></div>
            <a href="actions/logout.php" class="nav-link danger"><i class="ti ti-logout"></i> Logout</a>
        <?php else: ?>
            <a href="index.php?page=login"    class="nav-link"><i class="ti ti-login"></i> Login</a>
            <a href="index.php?page=register" class="nav-link"><i class="ti ti-user-plus"></i> Daftar</a>
        <?php endif; ?>
    </div>
</nav>

<script>
function toggleUserMenu() {
    document.getElementById('userMenu').classList.toggle('open');
}
function toggleMobileNav() {
    const d = document.getElementById('mobileDrawer');
    d.classList.toggle('open');
}
document.addEventListener('click', function(e) {
    const menu = document.getElementById('userMenu');
    if (menu && !menu.contains(e.target)) menu.classList.remove('open');
});
</script>