<?php
if (!isset($_SESSION['user'])) {
    echo "<script>window.location='index.php?page=login';</script>";
    exit;
}

$my_id = $_SESSION['user'];

$stmt = $conn->prepare("
    SELECT r.*, i.nama_barang, u.username AS penyewa
    FROM rentals r
    JOIN items i ON r.item_id = i.id
    JOIN users u ON r.user_id = u.id
    WHERE i.user_id = ? AND r.status_pembayaran = 'lunas'
    ORDER BY r.created_at DESC
");
$stmt->execute([$my_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .keu-wrap { max-width: 1000px; margin: 0 auto; }
    .keu-back {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 13px; color: #6b7280; text-decoration: none; margin-bottom: 14px;
    }
    .keu-back:hover { color: #3d4bff; }
    .keu-title { font-size: 24px; font-weight: 800; color: #1a1d2e; margin-bottom: 20px; }
    .keu-table-wrap {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
        overflow-x: auto;
    }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th, td { padding: 12px 16px; text-align: left; white-space: nowrap; }
    thead th {
        background: #f8f9fb; color: #6b7280; font-size: 11.5px;
        text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;
        border-bottom: 1px solid #e5e7eb;
    }
    tbody tr { border-bottom: 1px solid #f0f1f3; }
    tbody tr:last-child { border-bottom: none; }
    .badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700;
    }
    .badge.pending { background: #fff7e6; color: #92400e; }
    .badge.done { background: #dcfce7; color: #166534; }
    .bukti-link {
        color: #3d4bff; font-weight: 600; text-decoration: none;
    }
    .bukti-link:hover { text-decoration: underline; }
    .empty-fin { text-align: center; padding: 60px 20px; color: #9ca3af; }
</style>

<div class="keu-wrap">
    <a href="index.php?page=profile" class="keu-back"><i class="ti ti-arrow-left"></i> Kembali ke Profil</a>
    <div class="keu-title">Riwayat Pencairan</div>

    <div class="keu-table-wrap">
        <?php if (empty($rows)): ?>
            <div class="empty-fin">Belum ada riwayat transaksi.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Barang</th>
                    <th>Penyewa</th>
                    <th>Tanggal Sewa</th>
                    <th>Total Harga</th>
                    <th>Komisi Admin</th>
                    <th>Profit Kamu</th>
                    <th>Status Pencairan</th>
                    <th>Bukti Pencairan</th>
                    <th>Tgl Pencairan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r):
                    $profit = (int)$r['total_harga'] - (int)$r['komisi_admin'];
                    $sudah  = $r['status_pencairan'] === 'sudah_dicairkan';
                    $buktiUrl = (!empty($r['bukti_pencairan']) && file_exists("uploads/" . $r['bukti_pencairan']))
                        ? "uploads/" . $r['bukti_pencairan'] : null;
                ?>
                <tr>
                    <td><?= htmlspecialchars($r['nama_barang']) ?></td>
                    <td><?= htmlspecialchars($r['penyewa']) ?></td>
                    <td><?= date('d M Y', strtotime($r['tanggal_mulai'])) ?> - <?= date('d M Y', strtotime($r['tanggal_selesai'])) ?></td>
                    <td>Rp<?= number_format($r['total_harga'], 0, ',', '.') ?></td>
                    <td>Rp<?= number_format($r['komisi_admin'], 0, ',', '.') ?></td>
                    <td><strong>Rp<?= number_format($profit, 0, ',', '.') ?></strong></td>
                    <td>
                        <?php if ($sudah): ?>
                            <span class="badge done"><i class="ti ti-circle-check"></i> Sudah Dicairkan</span>
                        <?php else: ?>
                            <span class="badge pending"><i class="ti ti-clock"></i> Belum Dicairkan</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($buktiUrl): ?>
                            <a href="<?= htmlspecialchars($buktiUrl) ?>" target="_blank" class="bukti-link">
                                <i class="ti ti-photo"></i> Lihat Bukti
                            </a>
                        <?php else: ?>
                            <span style="color:#9ca3af;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $r['tanggal_pencairan'] ? date('d M Y, H:i', strtotime($r['tanggal_pencairan'])) : '-' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>