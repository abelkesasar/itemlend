<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php?page=login");
    exit;
}

$dari   = $_GET['dari']   ?? date('Y-m-01');        // default awal bulan ini
$sampai = $_GET['sampai'] ?? date('Y-m-d');          // default hari ini
$status = $_GET['status'] ?? 'semua';

// Validasi tanggal
if (!strtotime($dari) || !strtotime($sampai)) {
    die("Tanggal tidak valid.");
}
if ($sampai < $dari) {
    die("Tanggal akhir tidak boleh sebelum tanggal awal.");
}

// Build query
$where  = ["DATE(r.created_at) BETWEEN :dari AND :sampai"];
$params = [':dari' => $dari, ':sampai' => $sampai];

if ($status !== 'semua') {
    $where[]          = "r.status_pembayaran = :status";
    $params[':status'] = $status;
}

$sql = "
    SELECT
        r.id,
        r.created_at,
        i.nama_barang,
        i.kategori,
        i.lokasi          AS lokasi_barang,
        i.harga           AS harga_per_hari,
        u.username        AS penyewa,
        u.alamat          AS alamat_penyewa,
        u.nomor_wa        AS wa_penyewa,
        pu.username       AS pemilik,
        pu.alamat         AS alamat_pemilik,
        pu.nomor_wa       AS wa_pemilik,
        r.tanggal_mulai,
        r.tanggal_selesai,
        r.total_harga,
        r.metode_pembayaran,
        r.status_pembayaran,
        r.status_pinjam,
        r.paid_at,
        r.catatan_admin
    FROM rentals r
    JOIN items i  ON r.item_id = i.id
    JOIN users u  ON r.user_id = u.id
    JOIN users pu ON i.user_id = pu.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY r.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary stats
$total_transaksi = count($data);
$total_revenue   = array_sum(array_column(
    array_filter($data, fn($r) => $r['status_pembayaran'] === 'lunas'),
    'total_harga'
));
$total_pending = count(array_filter($data, fn($r) => $r['status_pembayaran'] === 'pending'));
$total_selesai = count(array_filter($data, fn($r) => $r['status_pinjam'] === 'selesai'));

// Label helper
function labelStatus(string $sp, string $spj): string {
    if ($spj === 'selesai')             return 'Selesai';
    if ($spj === 'sedang_dipinjam')     return 'Sedang Dipinjam';
    if ($sp  === 'lunas')               return 'Lunas - Belum Mulai';
    if ($sp  === 'menunggu_konfirmasi') return 'Menunggu Konfirmasi';
    if ($sp  === 'ditolak')             return 'Ditolak';
    return 'Belum Bayar';
}

// Hitung durasi
function durasi(string $mulai, string $selesai): int {
    return max(1, (int) ((strtotime($selesai) - strtotime($mulai)) / 86400));
}

// ── Set headers untuk download Excel
$filename = 'Laporan_Rental_ItemLend_' . $dari . '_sd_' . $sampai . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
echo "\xEF\xBB\xBF"; // BOM UTF-8 agar Excel baca karakter Indonesia dengan benar
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<!--[if gte mso 9]>
<xml><x:ExcelWorkbook><x:ExcelWorksheets>
<x:ExcelWorksheet><x:Name>Laporan Rental</x:Name>
<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml>
<![endif]-->
<style>
    body  { font-family: Arial, sans-serif; font-size: 11pt; }
    table { border-collapse: collapse; width: 100%; }
    td, th { border: 1px solid #d0d0d0; padding: 6px 10px; vertical-align: middle; }

    /* Header laporan */
    .title-row td { border: none; font-size: 16pt; font-weight: bold; color: #1a1d2e; }
    .sub-row   td { border: none; font-size: 10pt; color: #6b7280; }
    .gap-row   td { border: none; height: 10px; }

    /* Summary boxes */
    .summary-label { font-size: 9pt; color: #6b7280; font-weight: normal; }
    .summary-value { font-size: 14pt; font-weight: bold; color: #1a1d2e; }
    .box-blue   { background: #eef0ff; color: #3d4bff; }
    .box-green  { background: #e9f9f0; color: #16a34a; }
    .box-amber  { background: #fff7e6; color: #cc7a00; }
    .box-teal   { background: #e4f7f5; color: #0d7d72; }

    /* Tabel header */
    .th-main {
        background: #1a1d2e; color: #ffffff;
        font-weight: bold; font-size: 10pt;
        text-align: center;
    }
    .th-group-barang  { background: #eef0ff; color: #3d4bff; font-weight: bold; font-size: 9.5pt; text-align: center; }
    .th-group-penyewa { background: #e9f9f0; color: #16a34a; font-weight: bold; font-size: 9.5pt; text-align: center; }
    .th-group-pemilik { background: #fff7e6; color: #cc7a00; font-weight: bold; font-size: 9.5pt; text-align: center; }
    .th-group-trans   { background: #e4f7f5; color: #0d7d72; font-weight: bold; font-size: 9.5pt; text-align: center; }
    .th-group-status  { background: #f4f5f7; color: #374151; font-weight: bold; font-size: 9.5pt; text-align: center; }

    /* Row alternating */
    .row-even { background: #ffffff; }
    .row-odd  { background: #f9fafb; }

    /* Status colors */
    .s-selesai    { background: #dcfce7; color: #16a34a; font-weight: bold; text-align: center; }
    .s-dipinjam   { background: #dbeafe; color: #2563eb; font-weight: bold; text-align: center; }
    .s-lunas      { background: #e9f9f0; color: #16a34a; font-weight: bold; text-align: center; }
    .s-menunggu   { background: #fefce8; color: #a16207; font-weight: bold; text-align: center; }
    .s-ditolak    { background: #fee2e2; color: #dc2626; font-weight: bold; text-align: center; }
    .s-pending    { background: #f4f5f7; color: #6b7280; font-weight: bold; text-align: center; }

    .num  { text-align: right; }
    .ctr  { text-align: center; }
    .bold { font-weight: bold; }

    /* Footer */
    .footer-row td { border: none; font-size: 9pt; color: #9ca3af; font-style: italic; }
    .total-row td  { background: #1a1d2e; color: #fff; font-weight: bold; }

    
</style>
</head>
<body>

<table>

    <!-- JUDUL -->
    <tr class="title-row"><td colspan="18">📦 Laporan Peminjaman Barang — ItemLend</td></tr>
    <tr class="sub-row">
        <td colspan="18">
            Periode: <?= date('d F Y', strtotime($dari)) ?> s/d <?= date('d F Y', strtotime($sampai)) ?>
            &nbsp;|&nbsp; Status: <?= $status === 'semua' ? 'Semua' : ucfirst(str_replace('_',' ', $status)) ?>
            &nbsp;|&nbsp; Digenerate: <?= date('d F Y H:i') ?>
            &nbsp;|&nbsp; Admin: <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
        </td>
    </tr>
    <tr class="gap-row"><td colspan="18"></td></tr>

    <!-- SUMMARY -->
    <tr>
        <td colspan="4" class="box-blue" style="text-align:center;">
            <div class="summary-label">Total Transaksi</div>
            <div class="summary-value" style="color:#3d4bff;"><?= $total_transaksi ?></div>
        </td>
        <td colspan="5" class="box-green" style="text-align:center;">
            <div class="summary-label">Total Revenue (Lunas)</div>
            <div class="summary-value" style="color:#16a34a;">Rp <?= number_format($total_revenue, 0, ',', '.') ?></div>
        </td>
        <td colspan="5" class="box-amber" style="text-align:center;">
            <div class="summary-label">Belum Bayar</div>
            <div class="summary-value" style="color:#cc7a00;"><?= $total_pending ?></div>
        </td>
        <td colspan="4" class="box-teal" style="text-align:center;">
            <div class="summary-label">Peminjaman Selesai</div>
            <div class="summary-value" style="color:#0d7d72;"><?= $total_selesai ?></div>
        </td>
    </tr>
    <tr class="gap-row"><td colspan="18"></td></tr>

    <!-- HEADER GROUP -->
    <tr>
        <td class="th-main" rowspan="2">No</td>
        <td class="th-main" rowspan="2">ID</td>
        <td class="th-main" rowspan="2">Tgl Pesan</td>
        <td class="th-group-barang" colspan="4">BARANG</td>
        <td class="th-group-penyewa" colspan="3">PENYEWA</td>
        <td class="th-group-pemilik" colspan="3">PEMILIK BARANG</td>
        <td class="th-group-trans" colspan="4">TRANSAKSI</td>
        <td class="th-group-status" colspan="1">STATUS</td>
    </tr>
    <tr>
        <!-- Barang -->
        <td class="th-group-barang">Nama Barang</td>
        <td class="th-group-barang">Kategori</td>
        <td class="th-group-barang">Lokasi</td>
        <td class="th-group-barang">Harga/Hari</td>
        <!-- Penyewa -->
        <td class="th-group-penyewa">Username</td>
        <td class="th-group-penyewa">Alamat</td>
        <td class="th-group-penyewa">No. WA</td>
        <!-- Pemilik -->
        <td class="th-group-pemilik">Username</td>
        <td class="th-group-pemilik">Alamat</td>
        <td class="th-group-pemilik">No. WA</td>
        <!-- Transaksi -->
        <td class="th-group-trans">Tgl Mulai</td>
        <td class="th-group-trans">Tgl Selesai</td>
        <td class="th-group-trans">Durasi (hari)</td>
        <td class="th-group-trans">Total (Rp)</td>
        <!-- Status -->
        <td class="th-group-status">Status</td>
    </tr>

    <!-- DATA ROWS -->
    <?php if (empty($data)): ?>
    <tr>
        <td colspan="18" class="ctr" style="color:#9ca3af;padding:20px;">
            Tidak ada data pada periode ini.
        </td>
    </tr>
    <?php else: ?>

    <?php
    $no           = 1;
    $grand_total  = 0;
    foreach ($data as $r):
        $sp    = $r['status_pembayaran'] ?? 'pending';
        $spj   = $r['status_pinjam']     ?? 'belum_mulai';
        $dur   = durasi($r['tanggal_mulai'], $r['tanggal_selesai']);
        $tot   = $r['total_harga'] ?: ($dur * $r['harga_per_hari']);
        $grand_total += ($sp === 'lunas') ? $tot : 0;
        $label = labelStatus($sp, $spj);
        $rowCls = ($no % 2 === 0) ? 'row-even' : 'row-odd';

        // Status cell class
        $sCls = match(true) {
            $spj === 'selesai'             => 's-selesai',
            $spj === 'sedang_dipinjam'     => 's-dipinjam',
            $sp  === 'lunas'               => 's-lunas',
            $sp  === 'menunggu_konfirmasi' => 's-menunggu',
            $sp  === 'ditolak'             => 's-ditolak',
            default                        => 's-pending',
        };
    ?>
    <tr class="<?= $rowCls ?>">
        <td class="ctr"><?= $no++ ?></td>
        <td class="ctr">#<?= str_pad($r['id'], 6, '0', STR_PAD_LEFT) ?></td>
        <td class="ctr"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>

        <!-- Barang -->
        <td class="bold"><?= htmlspecialchars($r['nama_barang']) ?></td>
        <td class="ctr"><?= htmlspecialchars($r['kategori'] ?? '-') ?></td>
        <td><?= htmlspecialchars($r['lokasi_barang'] ?? '-') ?></td>
        <td class="num">Rp <?= number_format($r['harga_per_hari'], 0, ',', '.') ?></td>

        <!-- Penyewa -->
        <td class="bold"><?= htmlspecialchars($r['penyewa']) ?></td>
        <td><?= htmlspecialchars($r['alamat_penyewa'] ?? '-') ?></td>
        <td class="ctr"><?= htmlspecialchars($r['wa_penyewa'] ?? '-') ?></td>

        <!-- Pemilik -->
        <td class="bold"><?= htmlspecialchars($r['pemilik']) ?></td>
        <td><?= htmlspecialchars($r['alamat_pemilik'] ?? '-') ?></td>
        <td class="ctr"><?= htmlspecialchars($r['wa_pemilik'] ?? '-') ?></td>

        <!-- Transaksi -->
        <td class="ctr"><?= date('d/m/Y', strtotime($r['tanggal_mulai'])) ?></td>
        <td class="ctr"><?= date('d/m/Y', strtotime($r['tanggal_selesai'])) ?></td>
        <td class="ctr"><?= $dur ?></td>
        <td class="num bold">Rp <?= number_format($tot, 0, ',', '.') ?></td>

        <!-- Status -->
        <td class="<?= $sCls ?>"><?= $label ?></td>
    </tr>
    <?php endforeach; ?>

    <!-- TOTAL ROW -->
    <tr class="total-row">
        <td colspan="15" style="text-align:right;">TOTAL REVENUE (LUNAS)</td>
        <td colspan="2" class="num">Rp <?= number_format($total_revenue, 0, ',', '.') ?></td>
        <td></td>
    </tr>

    <?php endif; ?>

    <!-- FOOTER -->
    <tr class="gap-row"><td colspan="18"></td></tr>
    <tr class="footer-row">
        <td colspan="18">
            * Laporan ini digenerate otomatis oleh sistem ItemLend pada <?= date('d F Y H:i:s') ?>.
            Hanya menampilkan data pada periode yang dipilih.
        </td>
    </tr>

</table>
</body>
</html>