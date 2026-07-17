<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php?page=login");
    exit;
}

// Handle approve / reject via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id > 0 && in_array($action, ['approved', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE items SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $action, ':id' => $id]);
    }

    header("Location: barangapproval.php");
    exit;
}

// Stats untuk sidebar
$pending_users = $conn->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();

// Ambil semua barang pending + data owner
$items = $conn->query("
    SELECT i.*, u.username AS owner
    FROM items i
    LEFT JOIN users u ON i.user_id = u.id
    WHERE i.status = 'pending'
    ORDER BY i.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$total_pending = count($items);

// Helper: icon otomatis berdasarkan nama barang
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
    ['#eef0ff','#3d4bff'], ['#e9f9f0','#1a7a46'],
    ['#fff7e6','#cc7a00'], ['#fce7f3','#9d174d'], ['#e4f7f5','#0d7d72'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Barang - ItemLend Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 0 28px; height: 60px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar-left h1 { font-size: 17px; font-weight: 600; }
        .topbar-left p  { font-size: 12px; color: #6b7280; margin-top: 1px; }
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

        /* ── Page header ── */
        .page-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px; flex-wrap: wrap; gap: 12px;
        }
        .page-header-left { display: flex; align-items: center; gap: 12px; }
        .pending-count {
            background: #fff3cd; color: #856404;
            font-size: 13px; font-weight: 600;
            padding: 6px 14px; border-radius: 20px;
            border: 1px solid #fde68a;
            display: flex; align-items: center; gap: 6px;
        }
        .pending-count i { font-size: 15px; }
        .page-title { font-size: 20px; font-weight: 700; }
        .page-sub   { font-size: 13px; color: #6b7280; margin-top: 2px; }

        /* ── Approval Table Card ── */
        .table-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        /* ── Table ── */
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #f8f9fb; border-bottom: 1px solid #e5e7eb; }
        thead th {
            padding: 12px 16px;
            font-size: 11.5px; font-weight: 600;
            color: #6b7280; text-align: left;
            letter-spacing: 0.03em; text-transform: uppercase;
            white-space: nowrap;
        }
        tbody tr {
            border-bottom: 1px solid #f0f1f3;
            transition: background 0.12s;
        }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #fafbff; }
        tbody td { padding: 14px 16px; font-size: 13.5px; vertical-align: middle; }

        /* ── Item cell ── */
        .item-cell { display: flex; align-items: center; gap: 12px; }
        .item-thumb {
            width: 46px; height: 46px; border-radius: 10px;
            background: #f0f1f5; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }
        .item-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .item-thumb i { font-size: 22px; color: #c0c4ce; }
        .item-name { font-size: 13.5px; font-weight: 600; color: #1a1d2e; }
        .item-desc {
            font-size: 12px; color: #6b7280; margin-top: 2px;
            max-width: 260px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        /* ── Owner cell ── */
        .owner-cell { display: flex; align-items: center; gap: 8px; }
        .owner-av {
            width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 700;
        }
        .owner-name { font-size: 13px; font-weight: 500; }

        /* ── Price ── */
        .price { font-size: 13.5px; font-weight: 700; color: #3d4bff; }

        /* ── Date ── */
        .date-cell { font-size: 12px; color: #6b7280; white-space: nowrap; }

        /* ── Status badge ── */
        .badge-pending {
            display: inline-flex; align-items: center; gap: 5px;
            background: #fff7e6; color: #cc7a00;
            font-size: 11px; font-weight: 600;
            padding: 4px 10px; border-radius: 20px;
            border: 1px solid #fed7aa;
        }
        .badge-pending i { font-size: 12px; }

        /* ── Action buttons ── */
        .actions { display: flex; gap: 8px; }
        .btn-approve {
            display: inline-flex; align-items: center; gap: 5px;
            background: #3d4bff; color: #fff;
            font-size: 12px; font-weight: 600;
            padding: 7px 14px; border-radius: 8px;
            border: none; cursor: pointer;
            transition: background 0.15s;
            white-space: nowrap;
        }
        .btn-approve:hover { background: #2c38d4; }
        .btn-reject {
            display: inline-flex; align-items: center; gap: 5px;
            background: #fff5f5; color: #dc2626;
            font-size: 12px; font-weight: 600;
            padding: 7px 14px; border-radius: 8px;
            border: 1px solid #fecaca; cursor: pointer;
            transition: background 0.15s;
            white-space: nowrap;
        }
        .btn-reject:hover { background: #fee2e2; }

        /* ── Empty state ── */
        .empty-state {
            text-align: center; padding: 64px 20px;
            color: #9ca3af;
        }
        .empty-state i { font-size: 52px; display: block; margin-bottom: 14px; color: #d1d5db; }
        .empty-state h3 { font-size: 15px; font-weight: 600; color: #6b7280; margin-bottom: 6px; }
        .empty-state p  { font-size: 13px; }

        /* ── Flash message ── */
        .flash {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px; border-radius: 10px;
            font-size: 13px; font-weight: 500;
            margin-bottom: 16px;
        }
        .flash.success { background: #e9f9f0; color: #1a7a46; border: 1px solid #a7f3d0; }
        .flash.danger  { background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .hide-sm { display: none; }
        }
        @media (max-width: 600px) {
            .content { padding: 16px; }
            .actions { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="admin-wrap">

    <?php
    // sidebar.php ada di folder atas (admin/)
    include 'sidebar.php';
    ?>

    <div class="main">

        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>Approval Barang</h1>
                <p>Barang yang menunggu persetujuan admin</p>
            </div>
            <div class="topbar-right">
                <span class="admin-pill">Admin</span>
                <div class="avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
            </div>
        </div>

        <div class="content">

            <!-- Flash message dari URL -->
            <?php if (isset($_GET['approved'])): ?>
                <div class="flash success"><i class="ti ti-circle-check"></i> Barang berhasil diapprove!</div>
            <?php elseif (isset($_GET['rejected'])): ?>
                <div class="flash danger"><i class="ti ti-circle-x"></i> Barang berhasil ditolak.</div>
            <?php endif; ?>

            <!-- Page header -->
            <div class="page-header">
                <div class="page-header-left">
                    <div>
                        <div class="page-title">Pending Approval</div>
                        <div class="page-sub">Review barang sebelum tampil di marketplace</div>
                    </div>
                </div>
                <?php if ($total_pending > 0): ?>
                    <div class="pending-count">
                        <i class="ti ti-clock"></i>
                        <?= $total_pending ?> barang menunggu
                    </div>
                <?php endif; ?>
            </div>

            <!-- Table -->
            <div class="table-card">
                <?php if (empty($items)): ?>
                    <div class="empty-state">
                        <i class="ti ti-circle-check"></i>
                        <h3>Semua barang sudah diproses!</h3>
                        <p>Tidak ada barang yang menunggu approval saat ini.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Pemilik</th>
                                <th>Harga</th>
                                <th class="hide-sm">Didaftarkan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item):
                                $icon  = getItemIcon($item['nama_barang'] ?? '');
                                $c     = $avatar_colors[abs(crc32($item['owner'] ?? '')) % 5];
                                $init  = strtoupper(substr($item['owner'] ?? '?', 0, 2));
                                $tgl   = date('d M Y', strtotime($item['created_at']));
                                $harga = 'Rp' . number_format($item['harga'] ?? 0, 0, ',', '.');
                            ?>
                            <tr>
                                <!-- Barang -->
                                <td>
                                    <div class="item-cell">
                                        <div class="item-thumb">
<?php
$gambar = null;

if (!empty($item['gambar'])) {
    $gambar_list = json_decode($item['gambar'], true);

    if (is_array($gambar_list) && !empty($gambar_list[0])) {
        if (file_exists("../uploads/" . $gambar_list[0])) {
            $gambar = "../uploads/" . $gambar_list[0];
        }
    } else {
        if (file_exists("../uploads/" . $item['gambar'])) {
            $gambar = "../uploads/" . $item['gambar'];
        }
    }
}
?>

<?php if ($gambar): ?>
    <img src="<?= htmlspecialchars($gambar) ?>" alt="">
<?php else: ?>
    <i class="ti <?= $icon ?>"></i>
<?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="item-name"><?= htmlspecialchars($item['nama_barang'] ?? '-') ?></div>
                                            <div class="item-desc"><?= htmlspecialchars($item['deskripsi'] ?? 'Tidak ada deskripsi') ?></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Pemilik -->
                                <td>
                                    <div class="owner-cell">
                                        <div class="owner-av" style="background:<?= $c[0] ?>;color:<?= $c[1] ?>;"><?= $init ?></div>
                                        <span class="owner-name"><?= htmlspecialchars($item['owner'] ?? '-') ?></span>
                                    </div>
                                </td>

                                <!-- Harga -->
                                <td><span class="price"><?= $harga ?>/hr</span></td>

                                <!-- Tanggal -->
                                <td class="hide-sm"><span class="date-cell"><?= $tgl ?></span></td>

                                <!-- Status -->
                                <td>
                                    <span class="badge-pending">
                                        <i class="ti ti-clock"></i> Pending
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td>
                                    <div class="actions">
                                        <form method="POST" style="display:contents">
                                            <input type="hidden" name="id"     value="<?= $item['id'] ?>">
                                            <input type="hidden" name="action" value="approved">
                                            <button type="submit" class="btn-approve">
                                                <i class="ti ti-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST" style="display:contents"
                                              onsubmit="return confirm('Tolak barang ini?')">
                                            <input type="hidden" name="id"     value="<?= $item['id'] ?>">
                                            <input type="hidden" name="action" value="rejected">
                                            <button type="submit" class="btn-reject">
                                                <i class="ti ti-x"></i> Tolak
                                            </button>
                                        </form>
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