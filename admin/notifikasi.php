<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php?page=login");
    exit;
}

// ─── Handle aksi report ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['report_id'])) {
    $report_id = (int)$_POST['report_id'];
    $admin_id  = $_SESSION['user'];
    $new_status = $_POST['action'] === 'reviewed_report' ? 'reviewed' : 'dismissed';

    $stmt = $conn->prepare("UPDATE reports SET status=?, reviewed_at=NOW(), reviewed_by=? WHERE id=?");
    $stmt->execute([$new_status, $admin_id, $report_id]);

    header("Location: notifikasi.php");
    exit;
}

// ─── Query: User pending verifikasi ──────────────────────────────────────────
$users_pending = $conn->query("
    SELECT id, username, email, nomor_wa, alamat, ktm, ktp, foto_profil
    FROM users
    WHERE status = 'pending'
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ─── Query: Item pending validasi ─────────────────────────────────────────────
$items_pending = $conn->query("
    SELECT i.id, i.nama_barang, i.harga, i.gambar, i.foto, i.kategori, i.lokasi, i.created_at,
           u.username AS owner
    FROM items i
    JOIN users u ON i.user_id = u.id
    WHERE i.status = 'pending'
    ORDER BY i.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ─── Query: Pembayaran pending konfirmasi ─────────────────────────────────────
$payments_pending = $conn->query("
    SELECT r.id, r.total_harga, r.bukti_pembayaran, r.created_at,
           r.tanggal_mulai, r.tanggal_selesai,
           u.username AS nama_penyewa,
           i.nama_barang
    FROM rentals r
    JOIN users u ON r.user_id = u.id
    JOIN items i ON r.item_id = i.id
    WHERE r.status_pembayaran = 'menunggu_konfirmasi'
      AND r.bukti_pembayaran IS NOT NULL
    ORDER BY r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ─── Query: Pencairan pending ─────────────────────────────────────────────────
$pencairan_pending = $conn->query("
    SELECT r.id, r.total_harga, r.komisi_admin, r.jumlah_dicairkan,
           r.tanggal_mulai, r.tanggal_selesai, r.created_at,
           u.username AS nama_pemilik,
           u.metode_pembayaran, u.nama_penyedia, u.nomor_rekening,
           u.nama_pemilik_rekening, u.foto_qris,
           i.nama_barang,
           penyewa.username AS nama_penyewa
    FROM rentals r
    JOIN items i ON r.item_id = i.id
    JOIN users u ON i.user_id = u.id
    JOIN users penyewa ON r.user_id = penyewa.id
    WHERE r.status_pembayaran = 'lunas'
      AND r.status_pencairan = 'belum_dicairkan'
    ORDER BY r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ─── Query: Laporan pending ───────────────────────────────────────────────────
$reports_pending = $conn->query("
    SELECT rp.id, rp.target_id, rp.reason, rp.detail, rp.created_at,
           u.username AS nama_pelapor
    FROM reports rp
    JOIN users u ON rp.reporter_id = u.id
    WHERE rp.status = 'pending'
    ORDER BY rp.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ─── Counts ───────────────────────────────────────────────────────────────────
$c_user    = count($users_pending);
$c_item    = count($items_pending);
$c_pay     = count($payments_pending);
$c_cairkan = count($pencairan_pending);
$c_report  = count($reports_pending);
$c_total   = $c_user + $c_item + $c_pay + $c_cairkan + $c_report;

// ─── Helper: ambil gambar pertama dari kolom JSON ─────────────────────────────
function firstImage(string $raw): ?string {
    $list = json_decode($raw, true);
    if (is_array($list) && !empty($list[0])) {
        $path = "../uploads/" . $list[0];
        return file_exists($path) ? $path : null;
    }
    $path = "../uploads/" . $raw;
    return file_exists($path) ? $path : null;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi — ItemLend Admin</title>
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

        /* ── Tabs ── */
        .tab-nav {
            display: flex; gap: 4px;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .tab-btn {
            display: flex; align-items: center; gap: 7px;
            padding: 10px 16px;
            font-size: 13.5px; font-weight: 500;
            color: #6b7280; background: none; border: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            cursor: pointer; white-space: nowrap;
            transition: color 0.15s;
        }
        .tab-btn:hover { color: #1a1d2e; }
        .tab-btn.active { color: #3d4bff; border-bottom-color: #3d4bff; font-weight: 600; }
        .tab-badge {
            font-size: 10.5px; font-weight: 700;
            padding: 2px 7px; border-radius: 20px; line-height: 1.5;
        }
        .badge-blue    { background: #eef0ff; color: #3d4bff; }
        .badge-green   { background: #e9f9f0; color: #1a7a46; }
        .badge-amber   { background: #fff7e6; color: #cc7a00; }
        .badge-purple  { background: #f3e8ff; color: #7c3aed; }
        .badge-red     { background: #fee2e2; color: #dc2626; }

        /* ── Tab panels ── */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ── Notif card ── */
        .notif-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-left: 4px solid;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .notif-card.blue   { border-left-color: #3d4bff; }
        .notif-card.green  { border-left-color: #1a7a46; }
        .notif-card.amber  { border-left-color: #cc7a00; }
        .notif-card.purple { border-left-color: #7c3aed; }
        .notif-card.red    { border-left-color: #dc2626; }

        .notif-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .notif-icon i { font-size: 20px; }
        .notif-icon.blue   { background: #eef0ff; color: #3d4bff; }
        .notif-icon.green  { background: #e9f9f0; color: #1a7a46; }
        .notif-icon.amber  { background: #fff7e6; color: #cc7a00; }
        .notif-icon.purple { background: #f3e8ff; color: #7c3aed; }
        .notif-icon.red    { background: #fee2e2; color: #dc2626; }

        .notif-thumb {
            width: 52px; height: 52px; border-radius: 10px;
            object-fit: cover; flex-shrink: 0; cursor: pointer;
            border: 1px solid #e5e7eb;
        }
        .notif-thumb-placeholder {
            width: 52px; height: 52px; border-radius: 10px;
            background: #f4f5f7; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .notif-thumb-placeholder i { font-size: 22px; color: #c9ccd4; }

        .notif-body { flex: 1; min-width: 0; }
        .notif-title { font-size: 14px; font-weight: 600; margin-bottom: 3px; }
        .notif-meta  { font-size: 12px; color: #6b7280; line-height: 1.6; }
        .notif-meta strong { color: #374151; }

        .notif-actions { display: flex; flex-direction: column; gap: 6px; flex-shrink: 0; }

        /* ── Buttons ── */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 8px;
            font-size: 12.5px; font-weight: 600;
            text-decoration: none; cursor: pointer;
            border: 1px solid transparent;
            white-space: nowrap; transition: background 0.15s;
        }
        .btn-primary { background: #3d4bff; color: #fff; }
        .btn-primary:hover { background: #2c38d4; }
        .btn-purple { background: #7c3aed; color: #fff; }
        .btn-purple:hover { background: #6d28d9; }
        .btn-outline-success { background: #fff; color: #1a7a46; border-color: #6ee7b7; }
        .btn-outline-success:hover { background: #e9f9f0; }
        .btn-outline-muted { background: #fff; color: #6b7280; border-color: #d1d5db; }
        .btn-outline-muted:hover { background: #f4f5f7; }

        /* ── Empty state ── */
        .empty-state {
            text-align: center; padding: 48px 20px; color: #9ca3af;
        }
        .empty-state i { font-size: 40px; display: block; margin-bottom: 10px; }
        .empty-state p { font-size: 13.5px; }

        /* ── Modal ── */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.55); z-index: 200;
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff; border-radius: 16px;
            padding: 24px; max-width: 420px; width: 90%;
        }
        .modal-box img { width: 100%; border-radius: 10px; }
        .modal-close {
            display: block; margin-top: 14px; text-align: center;
            font-size: 13px; color: #6b7280; cursor: pointer;
            background: none; border: none; font-family: inherit;
        }
    </style>
</head>
<body>
<div class="admin-wrap">

    <?php include 'sidebar.php'; ?>

    <div class="main">

        <!-- Topbar -->
        <div class="topbar">
            <div>
                <h1>
                    Notifikasi
                    <?php if ($c_total > 0): ?>
                        <span class="tab-badge badge-red" style="font-size:12px;margin-left:6px;"><?= $c_total ?></span>
                    <?php endif; ?>
                </h1>
                <p>Permintaan yang memerlukan tindakan admin</p>
            </div>
            <div class="topbar-right">
                <span class="admin-pill">Admin</span>
                <div class="avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
            </div>
        </div>

        <div class="content">

            <!-- Tab Nav -->
            <div class="tab-nav">
                <button class="tab-btn active" onclick="switchTab('user', this)">
                    <i class="ti ti-user-check"></i> Verifikasi User
                    <?php if ($c_user > 0): ?>
                        <span class="tab-badge badge-blue"><?= $c_user ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-btn" onclick="switchTab('item', this)">
                    <i class="ti ti-box-seam"></i> Validasi Barang
                    <?php if ($c_item > 0): ?>
                        <span class="tab-badge badge-green"><?= $c_item ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-btn" onclick="switchTab('pay', this)">
                    <i class="ti ti-credit-card"></i> Konfirmasi Bayar
                    <?php if ($c_pay > 0): ?>
                        <span class="tab-badge badge-amber"><?= $c_pay ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-btn" onclick="switchTab('cairkan', this)">
                    <i class="ti ti-building-bank"></i> Pencairan
                    <?php if ($c_cairkan > 0): ?>
                        <span class="tab-badge badge-purple"><?= $c_cairkan ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-btn" onclick="switchTab('report', this)">
                    <i class="ti ti-flag"></i> Laporan
                    <?php if ($c_report > 0): ?>
                        <span class="tab-badge badge-red"><?= $c_report ?></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- ══ TAB: VERIFIKASI USER ══════════════════════════════════════ -->
            <div class="tab-panel active" id="tab-user">
                <?php if (empty($users_pending)): ?>
                    <div class="empty-state">
                        <i class="ti ti-circle-check" style="color:#1a7a46"></i>
                        <p>Tidak ada user baru yang menunggu verifikasi.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($users_pending as $u): ?>
                    <div class="notif-card blue">
                        <div class="notif-icon blue"><i class="ti ti-user"></i></div>
                        <div class="notif-body">
                            <div class="notif-title"><?= htmlspecialchars($u['username']) ?></div>
                            <div class="notif-meta">
                                <i class="ti ti-mail" style="font-size:12px"></i> <?= htmlspecialchars($u['email']) ?>
                                &nbsp;·&nbsp;
                                <i class="ti ti-phone" style="font-size:12px"></i> <?= htmlspecialchars($u['nomor_wa'] ?? '-') ?>
                            </div>
                        </div>
                        <div class="notif-actions">
                            <a href="users.php?id=<?= $u['id'] ?>" class="btn btn-primary">
                                <i class="ti ti-eye" style="font-size:14px"></i> Review
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- ══ TAB: VALIDASI BARANG ══════════════════════════════════════ -->
            <div class="tab-panel" id="tab-item">
                <?php if (empty($items_pending)): ?>
                    <div class="empty-state">
                        <i class="ti ti-circle-check" style="color:#1a7a46"></i>
                        <p>Tidak ada barang yang menunggu validasi.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($items_pending as $item):
                        $img = !empty($item['gambar']) ? firstImage($item['gambar']) : null;
                    ?>
                    <div class="notif-card green">
                        <?php if ($img): ?>
                            <img src="<?= htmlspecialchars($img) ?>" class="notif-thumb" alt=""
                                 onclick="openModal('<?= htmlspecialchars($img) ?>')">
                        <?php else: ?>
                            <div class="notif-thumb-placeholder"><i class="ti ti-box-seam"></i></div>
                        <?php endif; ?>
                        <div class="notif-body">
                            <div class="notif-title"><?= htmlspecialchars($item['nama_barang']) ?></div>
                            <div class="notif-meta">
                                Pemilik: <strong><?= htmlspecialchars($item['owner']) ?></strong>
                                &nbsp;·&nbsp;
                                <strong>Rp<?= number_format($item['harga'], 0, ',', '.') ?>/hr</strong>
                                &nbsp;·&nbsp;
                                <i class="ti ti-clock" style="font-size:12px"></i> <?= date('d M Y H:i', strtotime($item['created_at'])) ?>
                            </div>
                        </div>
                        <div class="notif-actions">
                            <a href="barangapproval.php?id=<?= $item['id'] ?>" class="btn btn-primary">
                                <i class="ti ti-eye" style="font-size:14px"></i> Review
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- ══ TAB: KONFIRMASI PEMBAYARAN ════════════════════════════════ -->
            <div class="tab-panel" id="tab-pay">
                <?php if (empty($payments_pending)): ?>
                    <div class="empty-state">
                        <i class="ti ti-circle-check" style="color:#1a7a46"></i>
                        <p>Tidak ada pembayaran yang menunggu konfirmasi.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($payments_pending as $pay):
                        $bukti = !empty($pay['bukti_pembayaran']) ? "../uploads/" . $pay['bukti_pembayaran'] : null;
                    ?>
                    <div class="notif-card amber">
                        <?php if ($bukti && file_exists($bukti)): ?>
                            <img src="<?= htmlspecialchars($bukti) ?>" class="notif-thumb" alt=""
                                 onclick="openModal('<?= htmlspecialchars($bukti) ?>')">
                        <?php else: ?>
                            <div class="notif-thumb-placeholder"><i class="ti ti-receipt"></i></div>
                        <?php endif; ?>
                        <div class="notif-body">
                            <div class="notif-title"><?= htmlspecialchars($pay['nama_penyewa']) ?></div>
                            <div class="notif-meta">
                                Barang: <strong><?= htmlspecialchars($pay['nama_barang']) ?></strong>
                                &nbsp;·&nbsp;
                                <strong>Rp<?= number_format($pay['total_harga'], 0, ',', '.') ?></strong>
                                &nbsp;·&nbsp;
                                <?= date('d M Y', strtotime($pay['tanggal_mulai'])) ?> – <?= date('d M Y', strtotime($pay['tanggal_selesai'])) ?>
                                &nbsp;·&nbsp;
                                <i class="ti ti-clock" style="font-size:12px"></i> <?= date('d M Y H:i', strtotime($pay['created_at'])) ?>
                            </div>
                        </div>
                        <div class="notif-actions">
                            <a href="rentals.php?id=<?= $pay['id'] ?>" class="btn btn-primary">
                                <i class="ti ti-eye" style="font-size:14px"></i> Review
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- ══ TAB: PENCAIRAN ════════════════════════════════════════════ -->
            <div class="tab-panel" id="tab-cairkan">
                <?php if (empty($pencairan_pending)): ?>
                    <div class="empty-state">
                        <i class="ti ti-circle-check" style="color:#1a7a46"></i>
                        <p>Tidak ada pencairan yang menunggu diproses.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pencairan_pending as $cairkan):
                        $jumlah = $cairkan['jumlah_dicairkan'] > 0
                            ? $cairkan['jumlah_dicairkan']
                            : ($cairkan['total_harga'] - $cairkan['komisi_admin']);
                    ?>
                    <div class="notif-card purple">
                        <div class="notif-icon purple"><i class="ti ti-building-bank"></i></div>
                        <div class="notif-body">
                            <div class="notif-title"><?= htmlspecialchars($cairkan['nama_pemilik']) ?></div>
                            <div class="notif-meta">
                                Barang: <strong><?= htmlspecialchars($cairkan['nama_barang']) ?></strong>
                                &nbsp;·&nbsp;
                                Penyewa: <strong><?= htmlspecialchars($cairkan['nama_penyewa']) ?></strong>
                                &nbsp;·&nbsp;
                                <strong style="color:#7c3aed">Rp<?= number_format($jumlah, 0, ',', '.') ?></strong>
                                <br>
                                <?php if (!empty($cairkan['metode_pembayaran'])): ?>
                                    <?= ucfirst($cairkan['metode_pembayaran']) ?>:
                                    <strong><?= htmlspecialchars($cairkan['nama_penyedia'] ?? '') ?></strong>
                                    <?php if (!empty($cairkan['nomor_rekening'])): ?>
                                        · <?= htmlspecialchars($cairkan['nomor_rekening']) ?>
                                        a.n. <?= htmlspecialchars($cairkan['nama_pemilik_rekening'] ?? '') ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:#dc2626">⚠ Pemilik belum set metode pembayaran</span>
                                <?php endif; ?>
                                &nbsp;·&nbsp;
                                <?= date('d M Y', strtotime($cairkan['tanggal_mulai'])) ?> – <?= date('d M Y', strtotime($cairkan['tanggal_selesai'])) ?>
                            </div>
                        </div>
                        <div class="notif-actions">
                            <a href="pencairan.php?id=<?= $cairkan['id'] ?>" class="btn btn-purple">
                                <i class="ti ti-coin" style="font-size:14px"></i> Cairkan
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- ══ TAB: LAPORAN ══════════════════════════════════════════════ -->
            <div class="tab-panel" id="tab-report">
                <?php if (empty($reports_pending)): ?>
                    <div class="empty-state">
                        <i class="ti ti-circle-check" style="color:#1a7a46"></i>
                        <p>Tidak ada laporan yang masuk.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reports_pending as $rp): ?>
                    <div class="notif-card red">
                        <div class="notif-icon red"><i class="ti ti-flag"></i></div>
                        <div class="notif-body">
                            <div class="notif-title">
                                Laporan Rental #<?= $rp['target_id'] ?>
                            </div>
                            <div class="notif-meta">
                                Pelapor: <strong><?= htmlspecialchars($rp['nama_pelapor']) ?></strong>
                                &nbsp;·&nbsp;
                                <i class="ti ti-clock" style="font-size:12px"></i> <?= date('d M Y H:i', strtotime($rp['created_at'])) ?>
                                <br>
                                Alasan: <strong><?= htmlspecialchars($rp['reason']) ?></strong>
                                <?php if (!empty($rp['detail'])): ?>
                                    — <span style="color:#374151"><?= htmlspecialchars($rp['detail']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="notif-actions">
                            <form method="POST" style="margin:0">
                                <input type="hidden" name="report_id" value="<?= $rp['id'] ?>">
                                <input type="hidden" name="action" value="reviewed_report">
                                <button type="submit" class="btn btn-outline-success">
                                    <i class="ti ti-check" style="font-size:14px"></i> Ditinjau
                                </button>
                            </form>
                            <form method="POST" style="margin:0"
                                  onsubmit="return confirm('Abaikan laporan ini?')">
                                <input type="hidden" name="report_id" value="<?= $rp['id'] ?>">
                                <input type="hidden" name="action" value="dismiss_report">
                                <button type="submit" class="btn btn-outline-muted">
                                    <i class="ti ti-x" style="font-size:14px"></i> Abaikan
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div><!-- /content -->
    </div><!-- /main -->
</div>

<!-- Modal preview gambar -->
<div class="modal-overlay" id="imgModal" onclick="closeModal()">
    <div class="modal-box" onclick="event.stopPropagation()">
        <img id="modalImg" src="" alt="preview">
        <button class="modal-close" onclick="closeModal()">Tutup</button>
    </div>
</div>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}
function openModal(src) {
    document.getElementById('modalImg').src = src;
    document.getElementById('imgModal').classList.add('open');
}
function closeModal() {
    document.getElementById('imgModal').classList.remove('open');
}
</script>
</body>
</html>