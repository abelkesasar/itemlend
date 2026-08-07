import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../services/api_service.dart';
import 'detail_barang_screen.dart';
import 'tambah_barang_screen.dart';

const Color _brandColor = Color(0xFF3D4BFF);

class TokoSayaScreen extends StatefulWidget {
  const TokoSayaScreen({super.key});

  @override
  State<TokoSayaScreen> createState() => _TokoSayaScreenState();
}

class _TokoSayaScreenState extends State<TokoSayaScreen> {
  int _selectedTab = 0;

  bool _isLoadingBarang = true;
  bool _isLoadingPesanan = true;
  String? _errorBarang;
  String? _errorPesanan;

  List<dynamic> _items = [];
  Map<String, dynamic> _statsBarang = {};
  final String _filterStatus = 'Semua';

  List<dynamic> _rentals = [];
  Map<String, dynamic> _statsPesanan = {};
  final String _rentalFilter = 'Semua';

  @override
  void initState() {
    super.initState();
    _loadBarang();
    _loadPesanan();
  }

  // ── Load Data ──

  Future<void> _loadBarang() async {
    setState(() { _isLoadingBarang = true; _errorBarang = null; });
    final result = await ApiService.getBarangSaya();
    if (!mounted) return;
    setState(() {
      _isLoadingBarang = false;
      if (result['success'] == true) {
        _items = result['data']?['items'] ?? [];
        _statsBarang = result['data']?['stats'] ?? {};
      } else {
        _errorBarang = result['message'] ?? 'Gagal memuat data.';
      }
    });
  }

  Future<void> _loadPesanan() async {
    setState(() { _isLoadingPesanan = true; _errorPesanan = null; });
    final result = await ApiService.getPesananMasuk();
    if (!mounted) return;
    setState(() {
      _isLoadingPesanan = false;
      if (result['success'] == true) {
        _rentals = result['data']?['rentals'] ?? [];
        _statsPesanan = result['data']?['stats'] ?? {};
      } else {
        _errorPesanan = result['message'] ?? 'Gagal memuat data.';
      }
    });
  }

  Future<void> _refreshAll() async {
    await Future.wait([_loadBarang(), _loadPesanan()]);
  }

  // ── Helpers ──

  String _formatHarga(int harga) {
    return 'Rp${harga.toString().replaceAllMapped(RegExp(r'(\d)(?=(\d{3})+(?!\d))'), (m) => '${m[1]}.')}';
  }

  String _statusLabel(String s) {
    switch (s) {
      case 'approved': return 'Disetujui';
      case 'pending': return 'Menunggu';
      case 'rejected': return 'Ditolak';
      default: return s;
    }
  }

  Color _statusColor(String s) {
    switch (s) {
      case 'approved': return Colors.green;
      case 'pending': return Colors.orange;
      case 'rejected': return Colors.red;
      default: return Colors.grey;
    }
  }

  Color _pembayaranColor(String s) {
    switch (s) {
      case 'lunas': return Colors.green;
      case 'menunggu_konfirmasi': return Colors.orange;
      case 'pending': return Colors.blue;
      case 'ditolak': return Colors.red;
      default: return Colors.grey;
    }
  }

  String _pembayaranLabel(String s) {
    switch (s) {
      case 'lunas': return 'Lunas';
      case 'menunggu_konfirmasi': return 'Menunggu Konfirmasi';
      case 'pending': return 'Belum Bayar';
      case 'ditolak': return 'Ditolak';
      default: return s;
    }
  }

  // ── Aksi ──

  Future<void> _handleDelete(dynamic item) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Hapus Barang?'),
        content: Text('Yakin ingin menghapus "${item['nama_barang']}"?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          TextButton(onPressed: () => Navigator.pop(ctx, true), style: TextButton.styleFrom(foregroundColor: Colors.red), child: const Text('Hapus')),
        ],
      ),
    );
    if (confirmed != true) return;
    final result = await ApiService.hapusBarang(itemId: item['id']);
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(result['message'] ?? 'Terjadi kesalahan.')));
    if (result['success'] == true) _loadBarang();
  }

  Future<void> _handleUpdateStatus(int rentalId, String status) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(status == 'sedang_dipinjam' ? 'Mulai Dipinjam?' : 'Tandai Selesai?'),
        content: Text(status == 'sedang_dipinjam'
            ? 'Konfirmasi bahwa barang sudah diserahkan ke penyewa.'
            : 'Konfirmasi bahwa barang sudah dikembalikan.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          ElevatedButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Ya')),
        ],
      ),
    );
    if (confirmed != true) return;

    final result = await ApiService.updateStatusPinjam(rentalId: rentalId, statusPinjam: status);
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(result['message'] ?? 'Terjadi kesalahan.')));
    if (result['success'] == true) _loadPesanan();
  }

  Future<void> _showReportDialog(int rentalId, String namaBarang, String penyewa) async {
    final reasonController = TextEditingController();
    final detailController = TextEditingController();

    final reasons = [
      'Barang tidak dikembalikan',
      'Barang rusak',
      'Barang hilang',
      'Penyewa tidak kooperatif',
      'Penipuan',
      'Lainnya',
    ];
    String selectedReason = reasons[0];

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDialogState) => AlertDialog(
          title: const Text('Laporkan Penyewa'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Barang: $namaBarang', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                Text('Penyewa: $penyewa', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                const SizedBox(height: 16),
                const Text('Alasan Laporan', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                const SizedBox(height: 8),
                DropdownButtonFormField<String>(
                  initialValue: selectedReason,
                  items: reasons.map((r) => DropdownMenuItem(value: r, child: Text(r, style: const TextStyle(fontSize: 13)))).toList(),
                  onChanged: (val) {
                    if (val != null) setDialogState(() => selectedReason = val);
                  },
                  decoration: InputDecoration(
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  ),
                ),
                const SizedBox(height: 12),
                const Text('Detail (opsional)', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                const SizedBox(height: 8),
                TextField(
                  controller: detailController,
                  maxLines: 3,
                  decoration: InputDecoration(
                    hintText: 'Jelaskan kronologi...',
                    hintStyle: TextStyle(color: Colors.grey[400], fontSize: 12.5),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    contentPadding: const EdgeInsets.all(12),
                  ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
            ElevatedButton(
              onPressed: () => Navigator.pop(ctx, true),
              style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
              child: const Text('Kirim Laporan'),
            ),
          ],
        ),
      ),
    );

    if (confirmed != true) return;

    final result = await ApiService.laporkan(
      targetId: rentalId,
      reason: selectedReason,
      detail: detailController.text,
    );

    if (!mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(result['message'] ?? 'Terjadi kesalahan.')),
    );
  }

  // ── Build ──

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Toko Saya'),
        actions: [
          if (_selectedTab == 0)
            TextButton.icon(
              onPressed: () async {
                final result = await Navigator.push(context, MaterialPageRoute(builder: (_) => const TambahBarangScreen()));
                if (result == true) _loadBarang();
              },
              icon: const Icon(Icons.add, size: 18),
              label: const Text('Tambah'),
            ),
        ],
      ),
      body: Column(
        children: [
          // Stats
          _buildStats(),
          // Tab bar
          _buildTabBar(),
          // Content
          Expanded(
            child: _selectedTab == 0 ? _buildBarangTab() : _buildPesananTab(),
          ),
        ],
      ),
    );
  }

  // ── Stats Bar ──

  Widget _buildStats() {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
      child: Row(
        children: [
          _buildStatCard('Barang', '${_statsBarang['total'] ?? 0}', Icons.inventory_2_outlined, _brandColor),
          const SizedBox(width: 8),
          _buildStatCard('Pesanan', '${_statsPesanan['total'] ?? 0}', Icons.receipt_long_outlined, Colors.blue),
          const SizedBox(width: 8),
          _buildStatCard('Lunas', '${_statsPesanan['lunas'] ?? 0}', Icons.check_circle_outline, Colors.green),
          const SizedBox(width: 8),
          _buildStatCard('Menunggu', '${(_statsPesanan['belum_bayar'] ?? 0) + (_statsPesanan['menunggu_konfirmasi'] ?? 0)}', Icons.hourglass_empty, Colors.orange),
        ],
      ),
    );
  }

  Widget _buildStatCard(String label, String value, IconData icon, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 6),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withValues(alpha: 0.2)),
        ),
        child: Column(
          children: [
            Icon(icon, size: 18, color: color),
            const SizedBox(height: 4),
            Text(value, style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: color)),
            const SizedBox(height: 2),
            Text(label, style: TextStyle(fontSize: 9, color: color.withValues(alpha: 0.7))),
          ],
        ),
      ),
    );
  }

  // ── Tab Bar ──

  Widget _buildTabBar() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16),
      decoration: BoxDecoration(
        color: Colors.grey[100],
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          _buildTab(0, Icons.inventory_2_outlined, 'Barang Saya'),
          _buildTab(1, Icons.receipt_long_outlined, 'Pesanan Masuk'),
        ],
      ),
    );
  }

  Widget _buildTab(int index, IconData icon, String label) {
    final selected = _selectedTab == index;
    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => _selectedTab = index),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: selected ? _brandColor : Colors.transparent,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 16, color: selected ? Colors.white : Colors.grey),
              const SizedBox(width: 6),
              Text(label, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: selected ? Colors.white : Colors.grey)),
            ],
          ),
        ),
      ),
    );
  }

  // ═══════════════════════════════════════
  //  TAB: BARANG SAYA
  // ═══════════════════════════════════════

  Widget _buildBarangTab() {
    if (_isLoadingBarang) return const Center(child: CircularProgressIndicator());
    if (_errorBarang != null) return _buildError(_errorBarang!, _loadBarang);

    return RefreshIndicator(
      onRefresh: _loadBarang,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text('${_items.length} barang terdaftar', style: const TextStyle(fontSize: 13, color: Colors.grey)),
          const SizedBox(height: 12),
          if (_items.isEmpty)
            _buildEmpty('Belum ada barang.', 'Klik tombol Tambah untuk mendaftarkan barang.')
          else
            ..._items.map((item) => _buildBarangCard(item)),
        ],
      ),
    );
  }

  Widget _buildBarangCard(dynamic item) {
    final gambarUrl = item['gambar_url'];
    final status = item['status'] ?? 'pending';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey[200]!),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Image
          Stack(
            children: [
              AspectRatio(
                aspectRatio: 16 / 9,
                child: gambarUrl != null
                    ? Image.network(gambarUrl, fit: BoxFit.cover, errorBuilder: (_, _, _) => Container(color: Colors.grey[200], child: const Icon(Icons.image_not_supported_outlined, color: Colors.grey)))
                    : Container(color: Colors.grey[200], child: const Icon(Icons.image_outlined, color: Colors.grey)),
              ),
              Positioned(top: 10, right: 10, child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(color: _statusColor(status).withValues(alpha: 0.9), borderRadius: BorderRadius.circular(20)),
                child: Text(_statusLabel(status), style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w600)),
              )),
              Positioned(top: 10, left: 10, child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(color: Colors.black.withValues(alpha: 0.7), borderRadius: BorderRadius.circular(20)),
                child: Text('${_formatHarga(item['harga'] ?? 0)}/hr', style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w600)),
              )),
            ],
          ),
          Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(item['nama_barang'] ?? '-', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
                const SizedBox(height: 4),
                Row(children: [
                  _infoChip(Icons.sell_outlined, item['kategori'] ?? '-'),
                  const SizedBox(width: 8),
                  _infoChip(Icons.location_on_outlined, item['lokasi'] ?? '-'),
                  const SizedBox(width: 8),
                  _infoChip(Icons.inventory_outlined, 'Stok: ${item['stok'] ?? 0}'),
                ]),
                if ((item['deskripsi'] ?? '').toString().isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(item['deskripsi'], maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12.5, color: Colors.grey)),
                ],
                const SizedBox(height: 10),
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(color: const Color(0xFFF8F9FB), borderRadius: BorderRadius.circular(10)),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _miniStat('Pesanan', '${item['total_pesanan'] ?? 0}'),
                      Container(width: 1, height: 20, color: Colors.grey[300]),
                      _miniStat('Lunas', '${item['pesanan_lunas'] ?? 0}'),
                      Container(width: 1, height: 20, color: Colors.grey[300]),
                      _miniStat('Pendapatan', _formatHarga(item['total_pendapatan'] ?? 0)),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                Row(children: [
                  Expanded(child: OutlinedButton.icon(
                    onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => DetailBarangScreen(itemId: item['id']))),
                    icon: const Icon(Icons.visibility_outlined, size: 16),
                    label: const Text('Lihat', style: TextStyle(fontSize: 12)),
                    style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 10), side: BorderSide(color: _brandColor.withValues(alpha: 0.3)), foregroundColor: _brandColor),
                  )),
                  const SizedBox(width: 8),
                  Expanded(child: OutlinedButton.icon(
                    onPressed: () => _handleDelete(item),
                    icon: const Icon(Icons.delete_outline, size: 16),
                    label: const Text('Hapus', style: TextStyle(fontSize: 12)),
                    style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 10), side: const BorderSide(color: Colors.red), foregroundColor: Colors.red),
                  )),
                ]),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ═══════════════════════════════════════
  //  TAB: PESANAN MASUK
  // ═══════════════════════════════════════

  Widget _buildPesananTab() {
    if (_isLoadingPesanan) return const Center(child: CircularProgressIndicator());
    if (_errorPesanan != null) return _buildError(_errorPesanan!, _loadPesanan);

    return RefreshIndicator(
      onRefresh: _loadPesanan,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text('${_rentals.length} pesanan masuk', style: const TextStyle(fontSize: 13, color: Colors.grey)),
          const SizedBox(height: 12),
          if (_rentals.isEmpty)
            _buildEmpty('Belum ada pesanan.', 'Pesanan akan muncul di sini ketika ada penyewa.')
          else
            ..._rentals.map((r) => _buildRentalCard(r)),
        ],
      ),
    );
  }

  Widget _buildRentalCard(dynamic r) {
    final isLunas = r['status_pembayaran'] == 'lunas';
    final pinjamStatus = r['status_pinjam'] ?? 'belum_mulai';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey[200]!),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header: gambar + info + badge
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Thumbnail
              ClipRRect(
                borderRadius: BorderRadius.circular(10),
                child: SizedBox(
                  width: 60, height: 60,
                  child: r['gambar_url'] != null
                      ? Image.network(r['gambar_url'], fit: BoxFit.cover, errorBuilder: (_, _, _) => Container(color: Colors.grey[200], child: const Icon(Icons.image_outlined, color: Colors.grey, size: 24)))
                      : Container(color: Colors.grey[200], child: const Icon(Icons.image_outlined, color: Colors.grey, size: 24)),
                ),
              ),
              const SizedBox(width: 12),
              // Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(r['nama_barang'] ?? '-', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
                    const SizedBox(height: 4),
                    Row(children: [
                      CircleAvatar(radius: 8, backgroundColor: _brandColor.withValues(alpha: 0.15), child: Text((r['penyewa'] ?? '?').substring(0, 1).toUpperCase(), style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: _brandColor))),
                      const SizedBox(width: 4),
                      Text(r['penyewa'] ?? '-', style: const TextStyle(fontSize: 11, color: Colors.grey)),
                    ]),
                    const SizedBox(height: 4),
                    Text(
                      '${_formatTanggal(r['tanggal_mulai'])} → ${_formatTanggal(r['tanggal_selesai'])}',
                      style: const TextStyle(fontSize: 11, color: Colors.grey),
                    ),
                  ],
                ),
              ),
              // Badge + Harga
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(color: _pembayaranColor(r['status_pembayaran']).withValues(alpha: 0.1), borderRadius: BorderRadius.circular(20)),
                    child: Text(_pembayaranLabel(r['status_pembayaran']), style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: _pembayaranColor(r['status_pembayaran']))),
                  ),
                  const SizedBox(height: 6),
                  Text(_formatHarga(r['total_harga'] ?? 0), style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: _brandColor)),
                  Text('#${_formatId(r['rental_id'])}', style: const TextStyle(fontSize: 10, color: Colors.grey)),
                ],
              ),
            ],
          ),

          // Status peminjaman (hanya untuk yang lunas)
          if (isLunas) ...[
            const SizedBox(height: 12),
            const Divider(height: 1),
            const SizedBox(height: 12),
            const Text('Status Peminjaman', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF374151))),
            const SizedBox(height: 8),
            _buildStatusStepper(pinjamStatus),
            const SizedBox(height: 12),
            // Action buttons
            Row(
              children: [
                if (r['wa_penyewa'] != null && '${r['wa_penyewa']}'.isNotEmpty)
                  _buildWaButton(r['wa_penyewa'], r['penyewa']),
                const Spacer(),
                // Tombol Laporkan Penyewa
                OutlinedButton.icon(
                  onPressed: () => _showReportDialog(r['rental_id'], r['nama_barang'], r['penyewa']),
                  icon: const Icon(Icons.flag_outlined, size: 14),
                  label: const Text('Laporkan', style: TextStyle(fontSize: 10)),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                    side: const BorderSide(color: Colors.red),
                    foregroundColor: Colors.red,
                  ),
                ),
                const SizedBox(width: 6),
                if (pinjamStatus == 'belum_mulai')
                  ElevatedButton.icon(
                    onPressed: () => _handleUpdateStatus(r['rental_id'], 'sedang_dipinjam'),
                    icon: const Icon(Icons.play_arrow, size: 16),
                    label: const Text('Mulai Dipinjam', style: TextStyle(fontSize: 11)),
                    style: ElevatedButton.styleFrom(backgroundColor: Colors.blue, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8)),
                  )
                else if (pinjamStatus == 'sedang_dipinjam')
                  ElevatedButton.icon(
                    onPressed: () => _handleUpdateStatus(r['rental_id'], 'selesai'),
                    icon: const Icon(Icons.check_circle_outline, size: 16),
                    label: const Text('Tandai Selesai', style: TextStyle(fontSize: 11)),
                    style: ElevatedButton.styleFrom(backgroundColor: Colors.green, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8)),
                  )
                else if (pinjamStatus == 'selesai')
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(color: Colors.green.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(8)),
                    child: const Row(mainAxisSize: MainAxisSize.min, children: [
                      Icon(Icons.check_circle, size: 14, color: Colors.green),
                      SizedBox(width: 4),
                      Text('Selesai', style: TextStyle(fontSize: 11, color: Colors.green, fontWeight: FontWeight.w600)),
                    ]),
                  ),
              ],
            ),
          ],

          // Status waiting (belum lunas)
          if (!isLunas) ...[
            const SizedBox(height: 10),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(color: Colors.orange.withValues(alpha: 0.08), borderRadius: BorderRadius.circular(10)),
              child: Row(
                children: [
                  const Icon(Icons.info_outline, size: 14, color: Colors.orange),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      r['status_pembayaran'] == 'pending'
                          ? 'Penyewa belum melakukan pembayaran.'
                          : 'Menunggu konfirmasi pembayaran dari admin.',
                      style: const TextStyle(fontSize: 11.5, color: Colors.orange),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildStatusStepper(String current) {
    final steps = [
      {'label': 'Belum Mulai', 'icon': Icons.radio_button_unchecked},
      {'label': 'Sedang Berjalan', 'icon': Icons.access_time},
      {'label': 'Selesai', 'icon': Icons.check_circle_outline},
    ];
    final currentIdx = current == 'sedang_dipinjam' ? 1 : current == 'selesai' ? 2 : 0;

    return Row(
      children: List.generate(steps.length * 2 - 1, (i) {
        if (i.isOdd) {
          final stepIdx = i ~/ 2;
          final isActive = stepIdx < currentIdx;
          return Expanded(child: Container(height: 2, color: isActive ? _brandColor : Colors.grey[300]));
        }
        final stepIdx = i ~/ 2;
        final isActive = stepIdx <= currentIdx;
        final isCurrent = stepIdx == currentIdx;
        final color = isActive ? _brandColor : Colors.grey;
        return Column(
          children: [
            Icon(steps[stepIdx]['icon'] as IconData, size: 18, color: isCurrent ? _brandColor : color),
            const SizedBox(height: 4),
            Text(steps[stepIdx]['label'] as String, style: TextStyle(fontSize: 9, fontWeight: isCurrent ? FontWeight.w700 : FontWeight.w400, color: isCurrent ? _brandColor : color)),
          ],
        );
      }),
    );
  }

  Widget _buildWaButton(String nomor, String nama) {
    return OutlinedButton.icon(
      onPressed: () async {
        final cleaned = nomor.replaceAll(RegExp(r'[^0-9]'), '');
        final url = Uri.parse('https://wa.me/62${cleaned.startsWith('0') ? cleaned.substring(1) : cleaned}');
        try {
          await ApiService.openUrl(url.toString());
        } catch (_) {}
      },
      icon: const Icon(Icons.chat_outlined, size: 14),
      label: Text('WA $nama', style: const TextStyle(fontSize: 11)),
      style: OutlinedButton.styleFrom(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
        side: const BorderSide(color: Colors.green),
        foregroundColor: Colors.green,
      ),
    );
  }

  // ── Shared widgets ──

  Widget _buildError(String msg, VoidCallback retry) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Icon(Icons.error_outline, size: 48, color: Colors.grey[400]),
          const SizedBox(height: 12),
          Text(msg, textAlign: TextAlign.center, style: const TextStyle(color: Colors.grey)),
          const SizedBox(height: 12),
          TextButton(onPressed: retry, child: const Text('Coba lagi')),
        ]),
      ),
    );
  }

  Widget _buildEmpty(String title, String subtitle) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 60),
      child: Column(children: [
        Icon(Icons.inventory_2_outlined, size: 48, color: Colors.grey[300]),
        const SizedBox(height: 12),
        Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
        const SizedBox(height: 4),
        Text(subtitle, style: const TextStyle(color: Colors.grey, fontSize: 12.5)),
      ]),
    );
  }

  Widget _infoChip(IconData icon, String text) {
    return Row(mainAxisSize: MainAxisSize.min, children: [
      Icon(icon, size: 12, color: Colors.grey),
      const SizedBox(width: 3),
      Text(text, style: const TextStyle(fontSize: 11, color: Colors.grey)),
    ]);
  }

  Widget _miniStat(String label, String value) {
    return Column(children: [
      Text(value, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
      const SizedBox(height: 2),
      Text(label, style: const TextStyle(fontSize: 10, color: Colors.grey)),
    ]);
  }

  String _formatId(int id) => id.toString().padLeft(6, '0');

  String _formatTanggal(String? date) {
    if (date == null) return '-';
    final d = DateTime.tryParse(date);
    if (d == null) return date;
    final months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return '${d.day} ${months[d.month - 1]} ${d.year}';
  }
}