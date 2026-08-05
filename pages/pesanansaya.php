<?php
// pages/pesanansaya.php
// session sudah aktif dari index.php
require 'config/db.php';

if (!isset($_SESSION['user'])) {
    echo "<script>window.location='index.php?page=login';</script>";
    exit;
}

function resolveGambarItem($gambarRaw) {
    if (empty($gambarRaw)) return null;
    $list = json_decode($gambarRaw, true);
    if (is_array($list) && !empty($list[0]) && file_exists("uploads/" . $list[0])) {
        return "uploads/" . $list[0];
    }
    if (!is_array($list) && file_exists("uploads/" . $gambarRaw)) {
        return "uploads/" . $gambarRaw;
    }
    return null;
}

$user_id = (int) $_SESSION['user'];
$tab     = $_GET['tab'] ?? 'semua';

// Ambil semua pesanan milik user ini
$stmt = $conn->prepare("
    SELECT r.*,
           i.nama_barang, i.harga, i.gambar, i.lokasi, i.kategori,
           u.username AS pemilik, u.nomor_wa AS wa_pemilik
    FROM rentals r
    JOIN items i ON r.item_id = i.id
    JOIN users u ON i.user_id = u.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$user_id]);
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kelompokkan per tab
$tabs = [
    'semua'                  => [],
    'menunggu_pembayaran'    => [],
    'menunggu_konfirmasi'    => [],
    'lunas'                  => [],
    'sedang_dipinjam'        => [],
    'selesai'                => [],
];

foreach ($all as $r) {
    $sp  = $r['status_pembayaran'] ?? 'pending';
    $spj = $r['status_pinjam']     ?? 'belum_mulai';

    $tabs['semua'][] = $r;

    if ($sp === 'pending') {
        $tabs['menunggu_pembayaran'][] = $r;
    } elseif ($sp === 'menunggu_konfirmasi') {
        $tabs['menunggu_konfirmasi'][] = $r;
    } elseif ($sp === 'lunas' && $spj === 'belum_mulai') {
        $tabs['lunas'][] = $r;
    } elseif ($spj === 'sedang_dipinjam') {
        $tabs['sedang_dipinjam'][] = $r;
    } elseif ($spj === 'selesai') {
        $tabs['selesai'][] = $r;
    }
}

$list = $tabs[$tab] ?? $tabs['semua'];

// Helper label & style per status
function getStatusBadge(array $r): string {
    $sp  = $r['status_pembayaran'] ?? 'pending';
    $spj = $r['status_pinjam']     ?? 'belum_mulai';

    if ($spj === 'selesai')
        return '<span class="sbadge s-selesai"><i class="ti ti-circle-check"></i> Selesai</span>';
    if ($spj === 'sedang_dipinjam')
        return '<span class="sbadge s-pinjam"><i class="ti ti-clock-play"></i> Sedang Dipinjam</span>';
    if ($sp === 'lunas')
        return '<span class="sbadge s-lunas"><i class="ti ti-check"></i> Dibayar</span>';
    if ($sp === 'menunggu_konfirmasi')
        return '<span class="sbadge s-konfirmasi"><i class="ti ti-hourglass"></i> Menunggu Konfirmasi</span>';
    return '<span class="sbadge s-pending"><i class="ti ti-clock"></i> Belum Bayar</span>';
}

// Apakah pesanan ini sudah bisa dilaporkan? (baru relevan setelah pembayaran lunas)
function canBeReported(array $r): bool {
    $sp  = $r['status_pembayaran'] ?? 'pending';
    $spj = $r['status_pinjam']     ?? 'belum_mulai';
    return $sp === 'lunas' || $spj === 'sedang_dipinjam' || $spj === 'selesai';
}
?>

<style>
    .ps-wrap { max-width: 900px; margin: 0 auto; padding: 8px 0 60px; }

    .ps-header { margin-bottom: 24px; }
    .ps-title { font-size: 24px; font-weight: 800; color: #1a1d2e; }
    .ps-sub   { font-size: 13px; color: #6b7280; margin-top: 4px; }

    .tab-bar {
        display: flex; gap: 6px; margin-bottom: 20px;
        overflow-x: auto; padding-bottom: 2px;
        scrollbar-width: none;
    }
    .tab-bar::-webkit-scrollbar { display: none; }
    .tab-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 14px; border-radius: 20px; white-space: nowrap;
        font-size: 13px; font-weight: 600; text-decoration: none;
        border: 1.5px solid #e5e7eb; color: #6b7280; background: #fff;
        transition: all 0.15s; flex-shrink: 0;
    }
    .tab-pill:hover { border-color: #3d4bff; color: #3d4bff; }
    .tab-pill.active { background: #3d4bff; border-color: #3d4bff; color: #fff; }
    .tab-pill .tc { font-size: 11px; font-weight: 700; }
    .tab-pill.active .tc { background: rgba(255,255,255,0.25); color: #fff; }
    .tab-pill .tc {
        background: #f0f1f3; color: #6b7280;
        border-radius: 20px; padding: 1px 7px;
    }

    .order-list { display: flex; flex-direction: column; gap: 14px; }

    .order-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
        overflow: hidden; transition: box-shadow 0.18s;
    }
    .order-card:hover { box-shadow: 0 4px 20px rgba(61,75,255,0.08); }

    .order-card.s-pending       { border-top: 3px solid #fed7aa; }
    .order-card.s-konfirmasi    { border-top: 3px solid #fde68a; }
    .order-card.s-lunas         { border-top: 3px solid #bbf7d0; }
    .order-card.s-pinjam        { border-top: 3px solid #93c5fd; }
    .order-card.s-selesai       { border-top: 3px solid #d1d5db; }

    .order-main {
        display: flex; align-items: center; gap: 16px; padding: 16px 18px;
        border-bottom: 1px solid #f0f1f3;
    }
    .order-thumb {
        width: 70px; height: 70px; border-radius: 12px; flex-shrink: 0;
        background: #f0f1f5; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
    }
    .order-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .order-thumb i   { font-size: 28px; color: #d1d5db; }

    .order-info { flex: 1; min-width: 0; }
    .order-name {
        font-size: 15px; font-weight: 700; color: #1a1d2e;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        margin-bottom: 4px;
    }
    .order-meta {
        display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
        font-size: 12px; color: #6b7280;
    }
    .order-meta span { display: flex; align-items: center; gap: 4px; }
    .order-meta i    { font-size: 13px; }

    .order-right {
        display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex-shrink: 0;
    }
    .order-total { font-size: 16px; font-weight: 800; color: #3d4bff; }
    .order-id    { font-size: 11px; color: #9ca3af; }

    .sbadge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 11.5px; font-weight: 700;
        padding: 4px 11px; border-radius: 20px;
    }
    .sbadge i { font-size: 13px; }
    .s-pending    { background: #fff7e6; color: #cc7a00; border: 1px solid #fed7aa; }
    .s-konfirmasi { background: #fefce8; color: #a16207; border: 1px solid #fde68a; }
    .s-lunas      { background: #e9f9f0; color: #16a34a; border: 1px solid #bbf7d0; }
    .s-pinjam     { background: #eff6ff; color: #2563eb; border: 1px solid #93c5fd; }
    .s-selesai    { background: #f4f5f7; color: #6b7280; border: 1px solid #d1d5db; }

    .order-footer {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 18px; gap: 10px; flex-wrap: wrap;
    }
    .order-dates { font-size: 12.5px; color: #6b7280; display: flex; align-items: center; gap: 6px; }
    .order-dates i { font-size: 14px; }
    .order-dates strong { color: #374151; }

    .order-actions { display: flex; gap: 8px; flex-wrap: wrap; }

    .btn-sm {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 7px 14px; border-radius: 8px;
        font-size: 12.5px; font-weight: 600; cursor: pointer;
        text-decoration: none; transition: all 0.15s; border: none; font-family: inherit;
    }
    .btn-sm i { font-size: 15px; }
    .btn-bayar-sm  { background: #3d4bff; color: #fff; }
    .btn-bayar-sm:hover  { background: #2c38d4; }
    .btn-detail-sm { background: #f4f5f7; color: #374151; border: 1px solid #e5e7eb; }
    .btn-detail-sm:hover { background: #e5e7eb; }
    .btn-wa-sm     { background: #e9f9f0; color: #16a34a; border: 1px solid #bbf7d0; }
    .btn-wa-sm:hover { background: #dcfce7; }
    .btn-ulang-sm  { background: #eef0ff; color: #3d4bff; border: 1px solid #c7d0ff; }
    .btn-ulang-sm:hover { background: #dde0ff; }
    .btn-report-sm { background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; }
    .btn-report-sm:hover { background: #fee2e2; }

    .timeline-strip {
        background: #eff6ff; border-top: 1px solid #dbeafe;
        padding: 10px 18px;
        display: flex; align-items: center; gap: 10px;
        font-size: 12.5px; color: #1d4ed8;
    }
    .timeline-strip i { font-size: 16px; flex-shrink: 0; }
    .timeline-bar-wrap {
        flex: 1; background: #dbeafe; border-radius: 20px; height: 6px; overflow: hidden;
    }
    .timeline-bar { height: 100%; background: #3d4bff; border-radius: 20px; }
    .timeline-text { white-space: nowrap; font-weight: 600; }

    .empty-state {
        text-align: center; padding: 64px 20px; color: #9ca3af;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
    }
    .empty-state i  { font-size: 52px; display: block; margin-bottom: 12px; color: #e5e7eb; }
    .empty-state h3 { font-size: 15px; font-weight: 700; color: #6b7280; margin-bottom: 6px; }
    .empty-state p  { font-size: 13px; }
    .empty-state a  {
        display: inline-flex; align-items: center; gap: 6px;
        margin-top: 16px; padding: 10px 20px;
        background: #3d4bff; color: #fff;
        border-radius: 10px; font-size: 13px; font-weight: 600;
    }

    /* ── REPORT MODAL ── */
    .report-modal-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(26,29,46,0.55);
        z-index: 200;
        align-items: center; justify-content: center;
        padding: 20px;
    }
    .report-modal-overlay.active { display: flex; }
    .report-modal {
        background: #fff; border-radius: 18px;
        width: 100%; max-width: 440px;
        padding: 24px;
        max-height: 90vh; overflow-y: auto;
    }
    .report-modal-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 6px;
    }
    .report-modal-header h3 {
        font-size: 17px; font-weight: 800; color: #dc2626;
        display: flex; align-items: center; gap: 8px;
    }
    .report-close {
        background: #f4f5f7; border: none; cursor: pointer;
        width: 30px; height: 30px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #6b7280; font-size: 15px;
    }
    .report-close:hover { background: #e5e7eb; }
    .report-target-name {
        font-size: 13px; color: #6b7280; margin-bottom: 16px;
    }
    .report-target-name strong { color: #1a1d2e; }
    .report-field { margin-bottom: 14px; }
    .report-field label {
        display: block; font-size: 12.5px; font-weight: 700;
        color: #1a1d2e; margin-bottom: 6px;
    }
    .report-field textarea {
        width: 100%; padding: 10px 12px;
        border: 1.5px solid #e5e7eb; border-radius: 10px;
        font-family: inherit; font-size: 13.5px; color: #1a1d2e;
        resize: vertical;
    }
    .report-field input[type="text"] {
        width: 100%; padding: 10px 12px;
        border: 1.5px solid #e5e7eb; border-radius: 10px;
        font-family: inherit; font-size: 13.5px; color: #1a1d2e;
    }
    .report-field input[type="file"] { font-size: 13px; }
    .report-field input[type="text"]:focus,
    .report-field textarea:focus {
        outline: none; border-color: #dc2626;
    }
    .btn-submit-report {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        width: 100%; padding: 13px;
        background: #dc2626; color: #fff;
        border: none; border-radius: 12px;
        font-family: inherit; font-size: 14px; font-weight: 700;
        cursor: pointer; transition: background 0.15s;
    }
    .btn-submit-report:hover { background: #b91c1c; }

    @media (max-width: 600px) {
        .order-main { flex-wrap: wrap; }
        .order-right { flex-direction: row; align-items: center; width: 100%; justify-content: space-between; }
        .order-thumb { width: 56px; height: 56px; }
    }
</style>

<div class="ps-wrap">

    <!-- Header -->
    <div class="ps-header">
        <div class="ps-title">Pesanan Saya</div>
        <div class="ps-sub">Semua barang yang kamu sewa dari orang lain</div>
    </div>

    <!-- Tabs -->
    <div class="tab-bar">
        <?php
        $tab_labels = [
            'semua'               => ['label' => 'Semua',                   'icon' => 'ti-list'],
            'menunggu_pembayaran' => ['label' => 'Belum Bayar',             'icon' => 'ti-clock'],
            'menunggu_konfirmasi' => ['label' => 'Menunggu Konfirmasi',     'icon' => 'ti-hourglass'],
            'lunas'               => ['label' => 'Sudah Dibayar',           'icon' => 'ti-check'],
            'sedang_dipinjam'     => ['label' => 'Sedang Dipinjam',         'icon' => 'ti-clock-play'],
            'selesai'             => ['label' => 'Selesai',                 'icon' => 'ti-circle-check'],
        ];
        foreach ($tab_labels as $key => $tl):
            $count  = count($tabs[$key]);
            $active = ($tab === $key) ? 'active' : '';
        ?>
        <a href="?page=pesanansaya&tab=<?= $key ?>" class="tab-pill <?= $active ?>">
            <i class="ti <?= $tl['icon'] ?>"></i>
            <?= $tl['label'] ?>
            <span class="tc"><?= $count ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- List -->
    <?php if (empty($list)): ?>
        <div class="empty-state">
            <i class="ti ti-shopping-bag-x"></i>
            <h3>Tidak ada pesanan</h3>
            <p>Belum ada pesanan di kategori ini.</p>
            <a href="index.php"><i class="ti ti-search"></i> Cari Barang</a>
        </div>
    <?php else: ?>
    <div class="order-list">
        <?php foreach ($list as $r):
            $sp   = $r['status_pembayaran'] ?? 'pending';
            $spj  = $r['status_pinjam']     ?? 'belum_mulai';
            $tgl1 = date('d M Y', strtotime($r['tanggal_mulai']));
            $tgl2 = date('d M Y', strtotime($r['tanggal_selesai']));
            $dur  = (int) ((strtotime($r['tanggal_selesai']) - strtotime($r['tanggal_mulai'])) / 86400);
            $tot  = $r['total_harga'] ?: ($dur * $r['harga']);
            $g    = resolveGambarItem($r['gambar']);

            if ($spj === 'selesai')                $cardClass = 's-selesai';
            elseif ($spj === 'sedang_dipinjam')    $cardClass = 's-pinjam';
            elseif ($sp === 'lunas')               $cardClass = 's-lunas';
            elseif ($sp === 'menunggu_konfirmasi') $cardClass = 's-konfirmasi';
            else                                   $cardClass = 's-pending';

            $progress = 0;
            if ($spj === 'sedang_dipinjam') {
                $now      = time();
                $mulai    = strtotime($r['tanggal_mulai']);
                $selesai  = strtotime($r['tanggal_selesai']);
                $progress = $selesai > $mulai
                    ? min(100, max(0, round(($now - $mulai) / ($selesai - $mulai) * 100)))
                    : 0;
                $sisa_hari = max(0, (int) ceil(($selesai - $now) / 86400));
            }
        ?>
        <div class="order-card <?= $cardClass ?>">

            <div class="order-main">
                <div class="order-thumb">
                    <?php if ($g): ?>
                        <img src="<?= htmlspecialchars($g) ?>" alt="">
                    <?php else: ?>
                        <i class="ti ti-box-seam"></i>
                    <?php endif; ?>
                </div>

                <div class="order-info">
                    <div class="order-name"><?= htmlspecialchars($r['nama_barang']) ?></div>
                    <div class="order-meta">
                        <span><i class="ti ti-user"></i> <?= htmlspecialchars($r['pemilik']) ?></span>
                        <?php if (!empty($r['lokasi'])): ?>
                        <span><i class="ti ti-map-pin"></i> <?= htmlspecialchars($r['lokasi']) ?></span>
                        <?php endif; ?>
                        <span><i class="ti ti-calendar"></i> <?= $dur ?> hari</span>
                        <?php if (!empty($r['kategori'])): ?>
                        <span><i class="ti ti-tag"></i> <?= htmlspecialchars($r['kategori']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="order-right">
                    <?= getStatusBadge($r) ?>
                    <div class="order-total">Rp <?= number_format($tot, 0, ',', '.') ?></div>
                    <div class="order-id">#<?= str_pad($r['id'], 6, '0', STR_PAD_LEFT) ?></div>
                </div>
            </div>

            <?php if ($spj === 'sedang_dipinjam'): ?>
            <div class="timeline-strip">
                <i class="ti ti-clock-play"></i>
                <div class="timeline-bar-wrap">
                    <div class="timeline-bar" style="width:<?= $progress ?>%;"></div>
                </div>
                <span class="timeline-text"><?= $sisa_hari ?> hari lagi</span>
            </div>
            <?php endif; ?>

            <div class="order-footer">
                <div class="order-dates">
                    <i class="ti ti-calendar-event"></i>
                    <strong><?= $tgl1 ?></strong>
                    <i class="ti ti-arrow-right" style="font-size:12px;"></i>
                    <strong><?= $tgl2 ?></strong>
                </div>

                <div class="order-actions">
                    <?php if ($sp === 'pending'): ?>
                        <a href="index.php?page=pembayaran&rental_id=<?= $r['id'] ?>"
                           class="btn-sm btn-bayar-sm">
                            <i class="ti ti-credit-card"></i> Bayar Sekarang
                        </a>

                    <?php elseif ($sp === 'menunggu_konfirmasi'): ?>
                        <a href="index.php?page=pembayaran&rental_id=<?= $r['id'] ?>"
                           class="btn-sm btn-detail-sm">
                            <i class="ti ti-eye"></i> Lihat Status
                        </a>

                    <?php elseif ($sp === 'lunas' || $spj === 'sedang_dipinjam'): ?>
                        <?php if (!empty($r['wa_pemilik'])): ?>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $r['wa_pemilik']) ?>?text=Halo+<?= urlencode($r['pemilik']) ?>+saya+mau+ambil+barang+<?= urlencode($r['nama_barang']) ?>"
                           target="_blank" class="btn-sm btn-wa-sm">
                            <i class="ti ti-brand-whatsapp"></i> Hubungi Pemilik
                        </a>
                        <?php endif; ?>
                        <a href="index.php?page=detail&id=<?= $r['item_id'] ?>"
                           class="btn-sm btn-detail-sm">
                            <i class="ti ti-eye"></i> Detail
                        </a>

                    <?php elseif ($spj === 'selesai'): ?>
                        <a href="index.php?page=detail&id=<?= $r['item_id'] ?>"
                           class="btn-sm btn-ulang-sm">
                            <i class="ti ti-repeat"></i> Sewa Lagi
                        </a>
                    <?php endif; ?>

                    <?php if (canBeReported($r)): ?>
                        <button type="button" class="btn-sm btn-report-sm"
                                onclick="openReportModal(<?= (int) $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['nama_barang'])) ?>', '<?= htmlspecialchars(addslashes($r['pemilik'])) ?>')">
                            <i class="ti ti-flag"></i> Laporkan
                        </button>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<!-- REPORT MODAL (shared untuk semua card) -->
<div id="reportModal" class="report-modal-overlay">
    <div class="report-modal">
        <div class="report-modal-header">
            <h3><i class="ti ti-flag"></i> Laporkan Pesanan</h3>
            <button type="button" class="report-close" onclick="closeReportModal()"><i class="ti ti-x"></i></button>
        </div>
        <div class="report-target-name" id="reportTargetName"></div>

        <form action="actions/report.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="target_id" id="reportTargetId" value="">

            <div class="report-field">
                <label>Alasan Laporan</label>
                <input type="text" name="reason" required maxlength="255" placeholder="Contoh: Barang tidak sesuai deskripsi">
            </div>

            <div class="report-field">
                <label>Detail Tambahan (opsional)</label>
                <textarea name="detail" rows="4" placeholder="Jelaskan lebih lanjut masalah yang kamu alami..."></textarea>
            </div>

            <div class="report-field">
                <label>Upload Bukti (opsional)</label>
                <input type="file" name="bukti" accept="image/*">
            </div>

            <button type="submit" class="btn-submit-report">
                <i class="ti ti-send"></i> Kirim Laporan
            </button>
        </form>
    </div>
</div>

<script>
    function openReportModal(rentalId, namaBarang, namaPemilik) {
        document.getElementById('reportTargetId').value = rentalId;
        document.getElementById('reportTargetName').innerHTML =
            'Melaporkan pesanan <strong>' + namaBarang + '</strong> dari <strong>' + namaPemilik + '</strong>';
        document.getElementById('reportModal').classList.add('active');
    }
    function closeReportModal() {
        document.getElementById('reportModal').classList.remove('active');
    }
    document.getElementById('reportModal').addEventListener('click', function(e) {
        if (e.target === this) closeReportModal();
    });
</script>