<?php
// pages/keuangan.php
require 'config/db.php';

if (!isset($_SESSION['user'])) {
    echo "<script>window.location='index.php?page=login';</script>";
    exit;
}

$owner_id = (int) $_SESSION['user'];
$filter   = $_GET['status'] ?? 'semua';

// Ambil semua transaksi lunas dari barang milik owner ini
$stmt = $conn->prepare("
    SELECT r.*,
           i.nama_barang, i.gambar,
           u.username AS penyewa
    FROM rentals r
    JOIN items i ON r.item_id = i.id
    JOIN users u ON r.user_id = u.id
    WHERE i.user_id = ? AND r.status_pembayaran = 'lunas'
    ORDER BY r.created_at DESC
");
$stmt->execute([$owner_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Komisi admin 5% — dihitung langsung dari total_harga (sama seperti logic di halaman Admin),
// karena kolom komisi_admin/jumlah_dicairkan pada data lama bisa tersimpan 0.
function hitungKomisi(int $totalHarga): int {
    return (int) round($totalHarga * 0.05);
}
function hitungDiterima(array $r): int {
    $komisiTersimpan   = (int) ($r['komisi_admin'] ?? 0);
    $jumlahTersimpan   = (int) ($r['jumlah_dicairkan'] ?? 0);
    $sp = $r['status_pencairan'] ?? 'belum_dicairkan';

    if ($sp === 'sudah_dicairkan' && $jumlahTersimpan > 0) {
        return $jumlahTersimpan; // pakai nominal asli kalau memang tersimpan
    }
    $komisi = $komisiTersimpan > 0 ? $komisiTersimpan : hitungKomisi((int) $r['total_harga']);
    return (int) $r['total_harga'] - $komisi;
}

// Hitung ringkasan
$totalBelum   = 0;
$totalSudah   = 0;
$countBelum   = 0;
$countSudah   = 0;
$countLaporan = 0;

foreach ($rows as $r) {
    $sp = $r['status_pencairan'] ?? 'belum_dicairkan';
    if ($sp === 'sudah_dicairkan') {
        $totalSudah += hitungDiterima($r);
        $countSudah++;
    } elseif ($sp === 'ada_laporan') {
        $countLaporan++;
    } else {
        $totalBelum += hitungDiterima($r);
        $countBelum++;
    }
}

// Filter tampilan
$filtered = array_filter($rows, function ($r) use ($filter) {
    $sp = $r['status_pencairan'] ?? 'belum_dicairkan';
    if ($filter === 'semua') return true;
    return $sp === $filter;
});

function labelPencairan(string $s): array {
    return match ($s) {
        'sudah_dicairkan' => ['Sudah Dicairkan', 'e9f9f0', '16a34a', 'ti-circle-check'],
        'ada_laporan'      => ['Ada Laporan',      'fff5f5', 'dc2626', 'ti-flag'],
        default            => ['Menunggu Dicairkan', 'fff7e6', 'cc7a00', 'ti-clock'],
    };
}
?>

<style>
    .keu-wrap { max-width: 1000px; margin: 0 auto; padding: 8px 0 60px; }
    .keu-back { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #6b7280; text-decoration: none; margin-bottom: 14px; }
    .keu-back:hover { color: #3d4bff; }
    .keu-title { font-size: 24px; font-weight: 800; color: #1a1d2e; }
    .keu-sub   { font-size: 13px; color: #6b7280; margin-top: 4px; margin-bottom: 24px; }

    .stats-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; margin-bottom: 24px; }
    .stat-mini { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .stat-mini-icon { width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .stat-mini-icon i { font-size: 20px; }
    .stat-mini-icon.amber { background: #fff7e6; color: #cc7a00; }
    .stat-mini-icon.green { background: #e9f9f0; color: #16a34a; }
    .stat-mini-icon.red   { background: #fff5f5; color: #dc2626; }
    .stat-mini-val   { font-size: 20px; font-weight: 800; color: #1a1d2e; line-height: 1; }
    .stat-mini-label { font-size: 11.5px; color: #6b7280; margin-top: 2px; }

    .tabs { display: flex; gap: 4px; margin-bottom: 20px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 4px; width: fit-content; flex-wrap: wrap; }
    .tab-btn { display: flex; align-items: center; gap: 7px; padding: 8px 16px; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; color: #6b7280; border: none; background: transparent; font-family: 'Plus Jakarta Sans', sans-serif; text-decoration: none; transition: all 0.15s; }
    .tab-btn:hover { color: #1a1d2e; background: #f4f5f7; }
    .tab-btn.active { background: #3d4bff; color: #fff; }

    .keu-list { display: flex; flex-direction: column; gap: 14px; }
    .keu-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
    .kc-head { display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-bottom: 1px solid #f0f1f3; }
    .kc-thumb { width: 50px; height: 50px; border-radius: 10px; flex-shrink: 0; background: #f0f1f5; overflow: hidden; display: flex; align-items: center; justify-content: center; }
    .kc-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .kc-thumb i   { font-size: 22px; color: #d1d5db; }
    .kc-info { flex: 1; min-width: 0; }
    .kc-name { font-size: 14px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .kc-sub  { font-size: 12px; color: #6b7280; margin-top: 3px; }
    .kc-right { display: flex; flex-direction: column; align-items: flex-end; gap: 5px; flex-shrink: 0; }
    .kc-status { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 20px; }
    .kc-nominal { font-size: 16px; font-weight: 800; color: #1a1d2e; }

    .kc-body { padding: 14px 18px; display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; }
    .kc-body-item .lbl { font-size: 10.5px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 2px; }
    .kc-body-item .val { font-size: 13.5px; font-weight: 700; color: #1a1d2e; }
    .kc-body-item .val.minus { color: #dc2626; }
    .kc-body-item .val.plus  { color: #16a34a; }

    .kc-footer { padding: 10px 18px; border-top: 1px solid #f0f1f3; font-size: 12px; color: #9ca3af; display: flex; justify-content: space-between; align-items: center; }
    .kc-footer .bukti-link { color: #3d4bff; font-weight: 600; text-decoration: none; }
    .kc-footer .bukti-link:hover { text-decoration: underline; }

    .empty-state { text-align: center; padding: 48px 20px; color: #9ca3af; }
    .empty-state i { font-size: 44px; display: block; margin-bottom: 10px; color: #e5e7eb; }
    .empty-state h3 { font-size: 14px; font-weight: 700; color: #6b7280; margin-bottom: 4px; }

    @media (max-width: 720px) {
        .stats-row { grid-template-columns: 1fr 1fr; }
        .kc-body { grid-template-columns: 1fr 1fr; }
    }
</style>

<div class="keu-wrap">
    <a href="index.php?page=profile" class="keu-back"><i class="ti ti-arrow-left"></i> Kembali ke Profil</a>

    <div class="keu-title">Riwayat Pencairan</div>
    <div class="keu-sub">Daftar dana hasil penyewaan barangmu yang sudah maupun belum dicairkan oleh admin</div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-mini">
            <div class="stat-mini-icon amber"><i class="ti ti-clock"></i></div>
            <div>
                <div class="stat-mini-val">Rp<?= number_format($totalBelum, 0, ',', '.') ?></div>
                <div class="stat-mini-label"><?= $countBelum ?> menunggu dicairkan</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-icon green"><i class="ti ti-circle-check"></i></div>
            <div>
                <div class="stat-mini-val">Rp<?= number_format($totalSudah, 0, ',', '.') ?></div>
                <div class="stat-mini-label"><?= $countSudah ?> sudah dicairkan</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-icon red"><i class="ti ti-flag"></i></div>
            <div>
                <div class="stat-mini-val"><?= $countLaporan ?></div>
                <div class="stat-mini-label">ada laporan</div>
            </div>
        </div>
    </div>

    <!-- Tabs filter -->
    <div class="tabs">
        <a href="?page=keuangan&status=semua" class="tab-btn <?= $filter==='semua'?'active':'' ?>">Semua</a>
        <a href="?page=keuangan&status=belum_dicairkan" class="tab-btn <?= $filter==='belum_dicairkan'?'active':'' ?>">Menunggu Dicairkan</a>
        <a href="?page=keuangan&status=sudah_dicairkan" class="tab-btn <?= $filter==='sudah_dicairkan'?'active':'' ?>">Sudah Dicairkan</a>
        <a href="?page=keuangan&status=ada_laporan" class="tab-btn <?= $filter==='ada_laporan'?'active':'' ?>">Ada Laporan</a>
    </div>

    <?php if (empty($filtered)): ?>
        <div class="empty-state">
            <i class="ti ti-receipt-off"></i>
            <h3>Belum ada data pencairan</h3>
        </div>
    <?php else: ?>
    <div class="keu-list">
        <?php foreach ($filtered as $r):
            $sp = $r['status_pencairan'] ?? 'belum_dicairkan';
            [$label, $bg, $fg, $icon] = labelPencairan($sp);
            $g = resolveGambarItem($r['gambar']);
            $komisiTampil  = (int) $r['komisi_admin'] > 0 ? (int) $r['komisi_admin'] : hitungKomisi((int) $r['total_harga']);
            $nominalTampil = hitungDiterima($r);
        ?>
        <div class="keu-card">
            <div class="kc-head">
                <div class="kc-thumb">
                    <?php if ($g): ?><img src="<?= htmlspecialchars($g) ?>" alt=""><?php else: ?><i class="ti ti-box-seam"></i><?php endif; ?>
                </div>
                <div class="kc-info">
                    <div class="kc-name"><?= htmlspecialchars($r['nama_barang']) ?></div>
                    <div class="kc-sub">Penyewa: <?= htmlspecialchars($r['penyewa']) ?> · #<?= str_pad($r['id'],6,'0',STR_PAD_LEFT) ?></div>
                </div>
                <div class="kc-right">
                    <span class="kc-status" style="background:#<?= $bg ?>;color:#<?= $fg ?>;">
                        <i class="ti <?= $icon ?>" style="font-size:11px;"></i> <?= $label ?>
                    </span>
                    <div class="kc-nominal">Rp<?= number_format($nominalTampil, 0, ',', '.') ?></div>
                </div>
            </div>

            <div class="kc-body">
                <div class="kc-body-item">
                    <div class="lbl">Total Sewa</div>
                    <div class="val">Rp<?= number_format($r['total_harga'], 0, ',', '.') ?></div>
                </div>
                <div class="kc-body-item">
                    <div class="lbl">Komisi Admin (5%)</div>
                    <div class="val minus">- Rp<?= number_format($komisiTampil, 0, ',', '.') ?></div>
                </div>
                <div class="kc-body-item">
                    <div class="lbl"><?= $sp === 'sudah_dicairkan' ? 'Diterima' : 'Estimasi Diterima' ?></div>
                    <div class="val plus">Rp<?= number_format($nominalTampil, 0, ',', '.') ?></div>
                </div>
            </div>

            <div class="kc-footer">
                <span>
                    <?php if ($sp === 'sudah_dicairkan' && !empty($r['tanggal_pencairan'])): ?>
                        Dicairkan pada <?= date('d M Y, H:i', strtotime($r['tanggal_pencairan'])) ?>
                    <?php else: ?>
                        Transaksi selesai <?= date('d M Y', strtotime($r['created_at'])) ?>
                    <?php endif; ?>
                </span>
                <?php if ($sp === 'sudah_dicairkan' && !empty($r['bukti_pencairan']) && file_exists('uploads/' . $r['bukti_pencairan'])): ?>
                    <a href="uploads/<?= htmlspecialchars($r['bukti_pencairan']) ?>" target="_blank" class="bukti-link">Lihat Bukti Transfer</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>