<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php?page=login");
    exit;
}

// Filter & search
$search   = trim($_GET['search'] ?? '');
$sort     = $_GET['sort'] ?? 'terbaru';

$where = "WHERE 1=1";
$params = [];

if ($search !== '') {
    $where .= " AND (i.nama_barang LIKE :search OR u.username LIKE :search)";
    $params[':search'] = "%$search%";
}

$order = match($sort) {
    'termurah' => 'i.harga ASC',
    'termahal' => 'i.harga DESC',
    'az'       => 'i.nama_barang ASC',
    default    => 'i.created_at DESC',
};

$sql = "
    SELECT i.*, u.username AS owner
    FROM items i
    LEFT JOIN users u ON i.user_id = u.id
    $where
    ORDER BY $order
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_items   = $conn->query("SELECT COUNT(*) FROM items")->fetchColumn();
$pending_users = $conn->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();

// Icon map berdasarkan kata kunci nama barang
function getItemIcon(string $name): string {
    $name = strtolower($name);
    if (str_contains($name, 'motor') || str_contains($name, 'mobil') || str_contains($name, 'sepeda')) return 'ti-bike';
    if (str_contains($name, 'kamera') || str_contains($name, 'camera') || str_contains($name, 'canon') || str_contains($name, 'sony')) return 'ti-camera';
    if (str_contains($name, 'laptop') || str_contains($name, 'komputer')) return 'ti-device-laptop';
    if (str_contains($name, 'tenda') || str_contains($name, 'camping') || str_contains($name, 'tent')) return 'ti-tent';
    if (str_contains($name, 'bor') || str_contains($name, 'drill') || str_contains($name, 'alat')) return 'ti-tool';
    if (str_contains($name, 'speaker') || str_contains($name, 'audio') || str_contains($name, 'jbl')) return 'ti-device-speaker';
    return 'ti-box-seam';
}

$avatar_colors = [
    ['#eef0ff','#3d4bff'],['#e9f9f0','#1a7a46'],
    ['#fff7e6','#cc7a00'],['#fce7f3','#9d174d'],['#e4f7f5','#0d7d72'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Barang - ItemLend Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f4f5f7;
            color: #1a1d2e;
            min-height: 100vh;
        }

        /* ── Topbar ── */
        .topbar {
            background: #fff; border-bottom: 1px solid #e5e7eb;
            padding: 0 28px; height: 60px;
            display: flex; align-items: center; justify-content: space-between;
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

        /* ── Toolbar ── */
        .toolbar {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 20px; flex-wrap: wrap;
        }
        .search-box {
            display: flex; align-items: center; gap: 8px;
            background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
            padding: 0 14px; height: 40px; flex: 1; min-width: 200px;
        }
        .search-box i { color: #9ca3af; font-size: 17px; flex-shrink: 0; }
        .search-box input {
            border: none; outline: none; font-family: inherit;
            font-size: 13.5px; background: transparent; width: 100%; color: #1a1d2e;
        }
        .search-box input::placeholder { color: #9ca3af; }
        .sort-select {
            height: 40px; border: 1px solid #e5e7eb; border-radius: 10px;
            padding: 0 14px; background: #fff; font-family: inherit;
            font-size: 13px; color: #1a1d2e; cursor: pointer; outline: none;
        }
        .count-pill {
            background: #1a1d2e; color: #fff;
            font-size: 12px; font-weight: 600;
            padding: 5px 12px; border-radius: 20px; white-space: nowrap;
        }

        /* ── Grid ── */
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 16px;
        }

        /* ── Item Card ── */
        .item-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
            transition: box-shadow 0.18s, transform 0.18s;
            display: flex; flex-direction: column;
        }
        .item-card:hover {
            box-shadow: 0 8px 28px rgba(61,75,255,0.10);
            transform: translateY(-3px);
        }

        /* Foto / placeholder */
        .item-thumb {
            width: 100%; aspect-ratio: 4/3;
            background: #f0f1f5;
            display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden;
        }
        .item-thumb img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform 0.3s;
        }
        .item-card:hover .item-thumb img { transform: scale(1.04); }
        .item-thumb .placeholder-icon { font-size: 44px; color: #c9ccd4; }

        /* Price badge */
        .price-badge {
            position: absolute; top: 10px; right: 10px;
            background: #1a1d2e; color: #fff;
            font-size: 11.5px; font-weight: 700;
            padding: 4px 10px; border-radius: 20px;
            letter-spacing: 0.01em;
        }

        /* Card body */
        .item-body { padding: 14px 16px 16px; flex: 1; display: flex; flex-direction: column; }
        .item-name {
            font-size: 14px; font-weight: 600; color: #1a1d2e;
            margin-bottom: 6px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .item-desc {
            font-size: 12px; color: #6b7280; line-height: 1.5;
            display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden;
            margin-bottom: 12px; flex: 1;
        }

        /* Meta row */
        .item-meta { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .owner-chip {
            display: flex; align-items: center; gap: 6px;
            background: #f4f5f7; border-radius: 20px;
            padding: 4px 10px 4px 4px;
            min-width: 0;
        }
        .owner-av {
            width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 9px; font-weight: 700;
        }
        .owner-name { font-size: 12px; font-weight: 500; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 90px; }
        .item-date { font-size: 11px; color: #9ca3af; white-space: nowrap; }

        /* Delete button */
        .item-footer {
            border-top: 1px solid #f0f1f3;
            padding: 10px 16px;
            display: flex; gap: 8px;
        }
        .btn-del {
            flex: 1; height: 34px;
            display: flex; align-items: center; justify-content: center; gap: 6px;
            border-radius: 8px; font-size: 12.5px; font-weight: 600;
            cursor: pointer; text-decoration: none;
            border: 1px solid #fecaca; background: #fff5f5; color: #dc2626;
            transition: background 0.15s;
        }
        .btn-del:hover { background: #fee2e2; }
        .btn-detail {
            flex: 2; height: 34px;
            display: flex; align-items: center; justify-content: center; gap: 6px;
            border-radius: 8px; font-size: 12.5px; font-weight: 600;
            cursor: pointer; text-decoration: none;
            background: #3d4bff; color: #fff;
            transition: background 0.15s;
        }
        .btn-detail:hover { background: #2c38d4; }

        /* Empty state */
        .empty-state {
            grid-column: 1/-1;
            text-align: center; padding: 60px 20px; color: #9ca3af;
        }
        .empty-state i { font-size: 48px; display: block; margin-bottom: 12px; }
        .empty-state p { font-size: 14px; }

        /* Responsive */
        @media (max-width: 600px) {
            .sidebar { display: none; }
            .main { margin-left: 0; }
        }
    </style>
</head>
<body>
<div class="admin-wrap">

    <?php include 'sidebar.php'; ?>

    <!-- Main -->
    <div class="main">

        <!-- Topbar -->
        <div class="topbar">
            <div>
                <h1>Kelola Barang</h1>
                <p>Semua barang yang terdaftar di platform</p>
            </div>
            <div class="topbar-right">
                <span class="admin-pill">Admin</span>
                <div class="avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
            </div>
        </div>

        <!-- Content -->
        <div class="content">

            <!-- Toolbar -->
            <form method="GET" action="">
                <div class="toolbar">
                    <div class="search-box">
                        <i class="ti ti-search"></i>
                        <input
                            type="text"
                            name="search"
                            placeholder="Cari nama barang atau pemilik..."
                            value="<?= htmlspecialchars($search) ?>"
                        >
                    </div>
                    <select name="sort" class="sort-select" onchange="this.form.submit()">
                        <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="az"       <?= $sort === 'az'      ? 'selected' : '' ?>>A – Z</option>
                        <option value="termurah" <?= $sort === 'termurah'? 'selected' : '' ?>>Harga Termurah</option>
                        <option value="termahal" <?= $sort === 'termahal'? 'selected' : '' ?>>Harga Termahal</option>
                    </select>
                    <button type="submit" style="display:none"></button>
                    <span class="count-pill"><?= count($items) ?> barang</span>
                </div>
            </form>

            <!-- Grid -->
            <div class="items-grid">
                <?php if (empty($items)): ?>
                    <div class="empty-state">
                        <i class="ti ti-mood-empty"></i>
                        <p>Tidak ada barang<?= $search ? " untuk \"$search\"" : '' ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($items as $item):
                        $icon  = getItemIcon($item['nama_barang'] ?? '');
                        $c     = $avatar_colors[crc32($item['owner'] ?? '') % 5];
                        $init  = strtoupper(substr($item['owner'] ?? '?', 0, 2));
                        $tgl   = date('d M Y', strtotime($item['created_at']));
                        $harga = 'Rp' . number_format($item['harga'] ?? 0, 0, ',', '.') . '/hr';
                    ?>
                    <div class="item-card">
                        <div class="item-thumb">
                            <?php
$gambar = null;

if (!empty($item['gambar'])) {

    $gambar_list = json_decode($item['gambar'], true);

    if (is_array($gambar_list) && !empty($gambar_list[0])) {

        $first_image = $gambar_list[0];

        if (file_exists("../uploads/" . $first_image)) {
            $gambar = "../uploads/" . $first_image;
        }

    } else {

        // support gambar lama
        if (file_exists("../uploads/" . $item['gambar'])) {
            $gambar = "../uploads/" . $item['gambar'];
        }
    }
}
?>

<?php if ($gambar): ?>
    <img src="<?= htmlspecialchars($gambar) ?>" alt="<?= htmlspecialchars($item['nama_barang']) ?>">
<?php else: ?>
    <i class="ti <?= $icon ?> placeholder-icon"></i>
<?php endif; ?>
                            <span class="price-badge"><?= $harga ?></span>
                        </div>

                        <div class="item-body">
                            <div class="item-name" title="<?= htmlspecialchars($item['nama_barang'] ?? '') ?>">
                                <?= htmlspecialchars($item['nama_barang'] ?? '-') ?>
                            </div>
                            <div class="item-desc">
                                <?= htmlspecialchars($item['deskripsi'] ?? 'Tidak ada deskripsi.') ?>
                            </div>
                            <div class="item-meta">
                                <div class="owner-chip">
                                    <div class="owner-av" style="background:<?= $c[0] ?>;color:<?= $c[1] ?>;"><?= $init ?></div>
                                    <span class="owner-name"><?= htmlspecialchars($item['owner'] ?? '-') ?></span>
                                </div>
                                <span class="item-date"><?= $tgl ?></span>
                            </div>
                        </div>

                        <div class="item-footer">
                            <a href="../actions/delete_item.php?id=<?= $item['id'] ?>"
                               class="btn-del"
                               onclick="return confirm('Hapus barang ini?')">
                                <i class="ti ti-trash" style="font-size:15px"></i> Hapus
                            </a>
                            <a href="../index.php?page=detail&id=<?= $item['id'] ?>" class="btn-detail">
                                <i class="ti ti-eye" style="font-size:15px"></i> Detail
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
</body>
</html>