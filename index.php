<?php
session_start();
require 'config/db.php';

$page = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ItemLend – Sewa Apa Saja</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root {
            --brand:       #3d4bff;
            --brand-dark:  #2c38d4;
            --brand-soft:  #eef0ff;
            --text-primary:#1a1d2e;
            --text-muted:  #6b7280;
            --border:      #e5e7eb;
            --bg:          #f4f5f7;
            --white:       #ffffff;
            --radius-sm:   8px;
            --radius-md:   12px;
            --radius-lg:   16px;
            --radius-xl:   24px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            min-height: 100vh;
        }

        a { text-decoration: none; color: inherit; }

        /* ── NAVBAR ── */
        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 200;
            padding: 0 0;
        }
        .navbar-inner {
            max-width: 1200px; margin: 0 auto;
            padding: 0 24px; height: 64px;
            display: flex; align-items: center; gap: 24px;
        }
        .navbar-brand {
            display: flex; align-items: center; gap: 8px;
            font-size: 20px; font-weight: 800; color: var(--brand);
            flex-shrink: 0;
        }
        .navbar-brand i { font-size: 22px; }
        .navbar-search {
            flex: 1; max-width: 400px;
            display: flex; align-items: center; gap: 8px;
            background: var(--bg); border: 1px solid var(--border);
            border-radius: var(--radius-md); padding: 0 14px; height: 40px;
        }
        .navbar-search i { color: var(--text-muted); font-size: 17px; flex-shrink: 0; }
        .navbar-search input {
            border: none; outline: none; background: transparent;
            font-family: inherit; font-size: 13.5px; width: 100%; color: var(--text-primary);
        }
        .navbar-search input::placeholder { color: #9ca3af; }
        .navbar-right { margin-left: auto; display: flex; align-items: center; gap: 10px; }
        .nav-greeting { font-size: 13px; color: var(--text-muted); white-space: nowrap; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: var(--radius-sm); font-family: inherit; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: background 0.15s, transform 0.1s; }
        .btn:active { transform: scale(0.97); }
        .btn-primary   { background: var(--brand); color: #fff; }
        .btn-primary:hover { background: var(--brand-dark); }
        .btn-outline   { background: transparent; color: var(--text-primary); border: 1px solid var(--border); }
        .btn-outline:hover { background: var(--bg); }
        .btn-danger-outline { background: transparent; color: #dc2626; border: 1px solid #fecaca; }
        .btn-danger-outline:hover { background: #fff5f5; }

        /* ── HERO ── */
        .hero-wrap { max-width: 1200px; margin: 0 auto; padding: 32px 24px 0; }
        .hero {
            background: linear-gradient(135deg, #3d4bff 0%, #6366f1 50%, #8b5cf6 100%);
            border-radius: var(--radius-xl);
            padding: 52px 56px;
            position: relative; overflow: hidden;
            color: #fff;
        }
        .hero::before {
            content: '';
            position: absolute; top: -60px; right: -60px;
            width: 320px; height: 320px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
        }
        .hero::after {
            content: '';
            position: absolute; bottom: -80px; right: 120px;
            width: 200px; height: 200px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
        }
        .hero-title {
            font-size: 42px; font-weight: 800; line-height: 1.15;
            max-width: 560px;
        }
        .hero-title span { color: #fde68a; }
        .hero-sub {
            font-size: 15px; color: rgba(255,255,255,0.8);
            margin-top: 14px; max-width: 480px; line-height: 1.6;
        }
        .hero-actions { margin-top: 28px; display: flex; gap: 12px; flex-wrap: wrap; }
        .btn-hero-white {
            background: #fff; color: var(--brand);
            font-size: 14px; font-weight: 700;
            padding: 12px 24px; border-radius: var(--radius-md);
            border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            transition: box-shadow 0.15s;
        }
        .btn-hero-white:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
        .btn-hero-ghost {
            background: rgba(255,255,255,0.15); color: #fff;
            font-size: 14px; font-weight: 600;
            padding: 12px 24px; border-radius: var(--radius-md);
            border: 1px solid rgba(255,255,255,0.3); cursor: pointer;
            display: inline-flex; align-items: center; gap: 8px;
            transition: background 0.15s;
        }
        .btn-hero-ghost:hover { background: rgba(255,255,255,0.22); }
        .hero-stats {
            margin-top: 36px; display: flex; gap: 32px; flex-wrap: wrap;
        }
        .hero-stat-value { font-size: 24px; font-weight: 800; }
        .hero-stat-label { font-size: 12px; color: rgba(255,255,255,0.7); margin-top: 2px; }

        /* ── MAIN CONTENT ── */
        .main-wrap { max-width: 1200px; margin: 0 auto; padding: 32px 24px 60px; }

        /* ── FILTER BAR ── */
        .filter-bar {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 24px; flex-wrap: wrap;
        }
        .filter-label { font-size: 13px; color: var(--text-muted); font-weight: 500; white-space: nowrap; }
        .filter-chips { display: flex; gap: 8px; flex-wrap: wrap; flex: 1; }
        .chip {
            padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 500;
            border: 1px solid var(--border); background: var(--white); color: var(--text-muted);
            cursor: pointer; transition: all 0.15s; text-decoration: none; display: inline-block;
        }
        .chip:hover  { border-color: var(--brand); color: var(--brand); }
        .chip.active { background: var(--brand); color: #fff; border-color: var(--brand); }
        .filter-right { margin-left: auto; display: flex; align-items: center; gap: 10px; }
        .sort-select {
            height: 38px; border: 1px solid var(--border); border-radius: var(--radius-sm);
            padding: 0 12px; background: var(--white); font-family: inherit;
            font-size: 13px; color: var(--text-primary); cursor: pointer; outline: none;
        }
        .result-count { font-size: 13px; color: var(--text-muted); white-space: nowrap; }

        /* ── ITEM GRID ── */
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }

        /* ── ITEM CARD ── */
        .item-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            display: flex; flex-direction: column;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .item-card:hover {
            box-shadow: 0 8px 32px rgba(61,75,255,0.10);
            transform: translateY(-4px);
        }
        .item-thumb {
            width: 100%; aspect-ratio: 4/3;
            background: #f0f1f5;
            display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden;
        }
        .item-thumb img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform 0.35s;
        }
        .item-card:hover .item-thumb img { transform: scale(1.05); }
        .item-thumb .no-img-icon { font-size: 48px; color: #d1d5db; }
        .price-tag {
            position: absolute; top: 12px; right: 12px;
            background: #1a1d2e; color: #fff;
            font-size: 12px; font-weight: 700;
            padding: 4px 10px; border-radius: 20px;
        }
        .item-body { padding: 14px 16px 16px; flex: 1; display: flex; flex-direction: column; }
        .item-name {
            font-size: 15px; font-weight: 700; color: var(--text-primary);
            margin-bottom: 6px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .item-desc {
            font-size: 12.5px; color: var(--text-muted); line-height: 1.55;
            display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden;
            margin-bottom: 14px; flex: 1;
        }
        .item-footer-row {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
        }
        .owner-chip {
            display: flex; align-items: center; gap: 6px;
            background: var(--bg); border-radius: 20px;
            padding: 4px 10px 4px 4px; min-width: 0;
        }
        .owner-av {
            width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 9px; font-weight: 700;
        }
        .owner-name { font-size: 12px; font-weight: 500; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 80px; }
        .btn-detail {
            display: inline-flex; align-items: center; gap: 5px;
            background: var(--brand); color: #fff;
            font-size: 12px; font-weight: 600;
            padding: 7px 14px; border-radius: var(--radius-sm);
            flex-shrink: 0; transition: background 0.15s;
        }
        .btn-detail:hover { background: var(--brand-dark); color: #fff; }

        /* ── EMPTY STATE ── */
        .empty-state {
            grid-column: 1/-1;
            text-align: center; padding: 80px 20px; color: #9ca3af;
        }
        .empty-state i { font-size: 56px; display: block; margin-bottom: 16px; color: #d1d5db; }
        .empty-state h3 { font-size: 16px; font-weight: 600; color: #6b7280; margin-bottom: 6px; }
        .empty-state p  { font-size: 13px; }

        /* ── SECTION TITLE ── */
        .section-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px; flex-wrap: wrap; gap: 12px;
        }
        .section-title { font-size: 22px; font-weight: 800; }

        /* ── SEARCH HIGHLIGHT ── */
        .search-banner {
            background: var(--brand-soft); border: 1px solid #c7ccff;
            border-radius: var(--radius-md); padding: 12px 16px;
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 20px; font-size: 13.5px; color: var(--brand); font-weight: 500;
        }
        .search-banner a { color: #dc2626; font-weight: 600; margin-left: auto; font-size: 13px; }

        /* ── OWN ITEMS NOTICE ── */
        .own-notice {
            background: #fff7e6; border: 1px solid #fed7aa;
            border-radius: var(--radius-md); padding: 10px 16px;
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 20px; font-size: 13px; color: #92400e;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .hero { padding: 32px 28px; }
            .hero-title { font-size: 28px; }
            .navbar-search { display: none; }
            .hero-stats { gap: 20px; }
        }
        @media (max-width: 500px) {
            .items-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
        }
    </style>
</head>
<body>

<?php require 'navbar.php'; ?>


<?php
switch ($page) {

    case 'login':
        echo '<div class="main-wrap">';
        require 'pages/login.php';
        echo '</div>';
        break;

    case 'register':
        require 'pages/register.php';
        break;

    case 'tambah_barang':
        echo '<div class="main-wrap">';
        require 'pages/tambah_barang.php';
        echo '</div>';
        break;

    case 'detail':
        echo '<div class="main-wrap">';
        require 'pages/detail.php';
        echo '</div>';
        break;

    case 'sewa':
        echo '<div class="main-wrap">';
        require 'pages/sewa.php';
        echo '</div>';
        break;
        

    case 'edit_barang':
        echo '<div class="main-wrap">';
        require 'pages/edit_barang.php';
        echo '</div>';
        break;

    case 'wishlist':
        echo '<div class="main-wrap">';
        require 'pages/wishlist.php';
        echo '</div>';
        break;

    case 'profile':
        echo '<div class="main-wrap">';
        require 'pages/profile.php';
        echo '</div>';
        break;

    case 'barangsaya':
        echo '<div class="main-wrap">';
        require 'pages/barangsaya.php';
        echo '</div>';
        break;

    case 'chat':
        echo '<div class="main-wrap">';
        require 'pages/chat.php';
        echo '</div>';
        break;
    case 'chat_list':
        echo '<div class="main-wrap">';
       require 'pages/chat_list.php';
        echo '</div>';
        break;
    case 'pembayaran':
        echo '<div class="main-wrap">';
        require 'pages/pembayaran.php';
        echo '</div>';
        break;

    case 'pesanansaya':
        echo '<div class="main-wrap">';
        require 'pages/pesanansaya.php';
        echo '</div>';
        break;

    default: // HOME

        // ── Parameter search & filter
        $search   = trim($_GET['q']      ?? '');
        $kategori = trim($_GET['cat']    ?? '');
        $sort     = trim($_GET['sort']   ?? 'terbaru');
        $my_id    = $_SESSION['user'] ?? 0; // login.php set $_SESSION['user'] = $user['id']

        // ── Stats untuk hero
        $total_items   = $conn->query("SELECT COUNT(*) FROM items WHERE status='approved'")->fetchColumn();
        $total_users   = $conn->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
        $total_rentals = $conn->query("SELECT COUNT(*) FROM rentals")->fetchColumn();

        // ── Build query
        $where   = ["i.status = 'approved'"];
        $params  = [];

        // Exclude barang milik user yang login
        if ($my_id > 0) {
            $where[]            = "i.user_id != :my_id";
            $params[':my_id']   = $my_id;
        }

        if ($search !== '') {
            $where[]             = "(i.nama_barang LIKE :q OR i.deskripsi LIKE :q)";
            $params[':q']        = "%$search%";
        }

        if ($kategori !== '') {
            $where[]             = "i.kategori = :cat";
            $params[':cat']      = $kategori;
        }

        $order = match ($sort) {
            'termurah' => 'i.harga ASC',
            'termahal' => 'i.harga DESC',
            'az'       => 'i.nama_barang ASC',
            default    => 'i.id DESC',
        };

        $sql = "
            SELECT i.*, u.username
            FROM items i
            JOIN users u ON i.user_id = u.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY $order
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Kategori list (dari DB, ambil distinct)
        $kategori_list = [];
        try {
            $kategori_list = $conn->query("SELECT DISTINCT kategori FROM items WHERE kategori IS NOT NULL AND kategori != '' AND status='approved'")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {}

        // ── Avatar colors
        $av_colors = [
            ['#eef0ff','#3d4bff'], ['#e9f9f0','#1a7a46'],
            ['#fff7e6','#cc7a00'], ['#fce7f3','#9d174d'], ['#e4f7f5','#0d7d72'],
        ];

        // Hitung berapa barang milik user yang disembunyikan
        $own_count = 0;
        if ($my_id > 0) {
            $own_count = (int) $conn->query("SELECT COUNT(*) FROM items WHERE user_id = $my_id AND status='approved'")->fetchColumn();
        }
?>

<!-- HERO -->
<div class="hero-wrap">
    <div class="hero">
        <div class="hero-title">
            Barang tidak terpakai<br>bisa jadi <span>penghasilan.</span>
        </div>
        <p class="hero-sub">
            Sewa apa saja, dari siapa saja. Marketplace sewa-menyewa yang aman, mudah, dan terpercaya.
        </p>
        <div class="hero-actions">
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['user', 'vendor'])): ?>
                <a href="?page=tambah_barang" class="btn-hero-white">
                    <i class="ti ti-plus"></i> Daftarkan Barangmu
                </a>
            <?php elseif (!isset($_SESSION['role'])): ?>
                <a href="?page=register" class="btn-hero-white">
                    <i class="ti ti-rocket"></i> Mulai Sekarang
                </a>
            <?php endif; ?>
            <a href="#daftar-barang" class="btn-hero-ghost">
                <i class="ti ti-search"></i> Jelajahi Barang
            </a>
        </div>
        <div class="hero-stats">
            <div>
                <div class="hero-stat-value"><?= number_format($total_items) ?>+</div>
                <div class="hero-stat-label">Barang Tersedia</div>
            </div>
            <div>
                <div class="hero-stat-value"><?= number_format($total_users) ?>+</div>
                <div class="hero-stat-label">Pengguna Aktif</div>
            </div>
            <div>
                <div class="hero-stat-value"><?= number_format($total_rentals) ?>+</div>
                <div class="hero-stat-label">Transaksi Sewa</div>
            </div>
        </div>
    </div>
</div>

<!-- MAIN -->
<div class="main-wrap" id="daftar-barang">

    <!-- Search hint mobile -->
    <form method="GET" action="index.php" style="margin-bottom:16px;display:none;" id="mobile-search-form">
        <input type="hidden" name="page" value="home">
        <div style="display:flex;gap:8px;">
            <div style="flex:1;display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--border);border-radius:var(--radius-md);padding:0 14px;height:44px;">
                <i class="ti ti-search" style="color:#9ca3af;font-size:17px;"></i>
                <input type="text" name="q" placeholder="Cari barang..." value="<?= htmlspecialchars($search) ?>"
                    style="border:none;outline:none;font-family:inherit;font-size:14px;width:100%;background:transparent;">
            </div>
            <button type="submit" class="btn btn-primary">Cari</button>
        </div>
    </form>
    <script>if(window.innerWidth<=768) document.getElementById('mobile-search-form').style.display='block';</script>

    <!-- Search result banner -->
    <?php if ($search !== ''): ?>
        <div class="search-banner">
            <i class="ti ti-search" style="font-size:16px;"></i>
            Menampilkan hasil untuk <strong>&nbsp;"<?= htmlspecialchars($search) ?>"</strong>
            &nbsp;—&nbsp; <?= count($items) ?> barang ditemukan
            <a href="index.php">✕ Hapus pencarian</a>
        </div>
    <?php endif; ?>

    <!-- Own items notice -->
    <?php if ($my_id > 0 && $own_count > 0): ?>
        <div class="own-notice">
            <i class="ti ti-info-circle" style="font-size:16px;flex-shrink:0;"></i>
            <?= $own_count ?> barang milikmu tidak ditampilkan di sini.
            <a href="?page=barangsaya" style="margin-left:auto;color:#cc7a00;font-weight:600;font-size:12px;">Lihat barangku →</a>
        </div>
    <?php endif; ?>

    <!-- Section header + filter -->
    <div class="section-header">
        <h2 class="section-title">Daftar Barang</h2>
    </div>

    <!-- Filter bar -->
    <form method="GET" action="index.php" id="filter-form">
        <input type="hidden" name="page" value="home">
        <?php if ($search): ?><input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
        <div class="filter-bar">
            <span class="filter-label">Filter:</span>
            <div class="filter-chips">
                <a href="?page=home<?= $search ? '&q='.urlencode($search) : '' ?><?= $sort !== 'terbaru' ? '&sort='.$sort : '' ?>"
                   class="chip <?= $kategori === '' ? 'active' : '' ?>">Semua</a>
                <?php foreach ($kategori_list as $kat): ?>
                    <a href="?page=home&cat=<?= urlencode($kat) ?><?= $search ? '&q='.urlencode($search) : '' ?><?= $sort !== 'terbaru' ? '&sort='.$sort : '' ?>"
                       class="chip <?= $kategori === $kat ? 'active' : '' ?>">
                        <?= htmlspecialchars($kat) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="filter-right">
                <select name="sort" class="sort-select" onchange="document.getElementById('filter-form').submit()">
                    <option value="terbaru"  <?= $sort==='terbaru'  ? 'selected':'' ?>>Terbaru</option>
                    <option value="az"       <?= $sort==='az'       ? 'selected':'' ?>>A – Z</option>
                    <option value="termurah" <?= $sort==='termurah' ? 'selected':'' ?>>Termurah</option>
                    <option value="termahal" <?= $sort==='termahal' ? 'selected':'' ?>>Termahal</option>
                </select>
                <span class="result-count"><?= count($items) ?> barang</span>
            </div>
        </div>
    </form>

    <!-- Grid -->
    <div class="items-grid">
        <?php if (empty($items)): ?>
            <div class="empty-state">
                <i class="ti ti-mood-empty"></i>
                <h3>Tidak ada barang<?= $search ? " untuk \"$search\"" : '' ?></h3>
                <p><?= $search ? 'Coba kata kunci lain.' : 'Belum ada barang yang tersedia.' ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item):
                $gambar = null;

if (!empty($item['gambar'])) {

    $gambar_list = json_decode($item['gambar'], true);

    // Jika format baru (JSON multiple image)
    if (is_array($gambar_list) && !empty($gambar_list[0])) {

        $first_image = $gambar_list[0];

        if (file_exists("uploads/" . $first_image)) {
            $gambar = "uploads/" . $first_image;
        }

    } else {

        // Support format lama (single image)
        if (file_exists("uploads/" . $item['gambar'])) {
            $gambar = "uploads/" . $item['gambar'];
        }
    }
}
                $harga  = 'Rp ' . number_format($item['harga'], 0, ',', '.');
                $c      = $av_colors[abs(crc32($item['username'] ?? '')) % 5];
                $init   = strtoupper(substr($item['username'] ?? '?', 0, 2));
            ?>
            <div class="item-card">
                <div class="item-thumb">
                    <?php if ($gambar): ?>
                        <img src="<?= htmlspecialchars($gambar) ?>" alt="<?= htmlspecialchars($item['nama_barang']) ?>">
                    <?php else: ?>
                        <i class="ti ti-box-seam no-img-icon"></i>
                    <?php endif; ?>
                    <span class="price-tag"><?= $harga ?>/hr</span>
                </div>
                <div class="item-body">
                    <div class="item-name" title="<?= htmlspecialchars($item['nama_barang']) ?>">
                        <?= htmlspecialchars($item['nama_barang']) ?>
                    </div>
                    <div class="item-desc">
                        <?= htmlspecialchars($item['deskripsi'] ?? '') ?>
                    </div>
                    <div class="item-footer-row">
                        <div class="owner-chip">
                            <div class="owner-av" style="background:<?= $c[0] ?>;color:<?= $c[1] ?>;"><?= $init ?></div>
                            <span class="owner-name"><?= htmlspecialchars($item['username']) ?></span>
                        </div>
                        <a href="?page=detail&id=<?= $item['id'] ?>" class="btn-detail">
                            <i class="ti ti-eye" style="font-size:14px;"></i> Detail
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php
        break;
} // end switch
?>

</body>
</html>
