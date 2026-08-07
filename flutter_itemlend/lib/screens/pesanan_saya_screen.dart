import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'detail_barang_screen.dart';
import 'proses_pembayaran_screen.dart';

const Color _brandColor = Color(0xFF3D4BFF);

class PesananSayaScreen extends StatefulWidget {
  const PesananSayaScreen({super.key});

  @override
  State<PesananSayaScreen> createState() => _PesananSayaScreenState();
}

class _PesananSayaScreenState extends State<PesananSayaScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _rentals = [];
  Map<String, dynamic> _stats = {};

  String _selectedTab = 'semua';

  @override
  void initState() {
    super.initState();
    _loadPesanan();
  }

  Future<void> _loadPesanan() async {
    setState(() { _isLoading = true; _errorMessage = null; });
    final result = await ApiService.getPesananSaya();
    if (!mounted) return;
    setState(() {
      _isLoading = false;
      if (result['success'] == true) {
        _rentals = result['data']?['rentals'] ?? [];
        _stats = result['data']?['stats'] ?? {};
      } else {
        _errorMessage = result['message'] ?? 'Gagal memuat data.';
      }
    });
  }

  // ── Helpers ──

  String _formatHarga(int harga) {
    return 'Rp${harga.toString().replaceAllMapped(RegExp(r'(\d)(?=(\d{3})+(?!\d))'), (m) => '${m[1]}.')}';
  }

  String _formatId(int id) => '#${id.toString().padLeft(6, '0')}';

  String _formatTanggal(String? date) {
    if (date == null) return '-';
    final d = DateTime.tryParse(date);
    if (d == null) return date;
    final months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return '${d.day} ${months[d.month - 1]} ${d.year}';
  }

  String _statusBadgeLabel(dynamic r) {
    final sp = r['status_pembayaran'] ?? 'pending';
    final spj = r['status_pinjam'] ?? 'belum_mulai';
    if (spj == 'selesai') return 'Selesai';
    if (spj == 'sedang_dipinjam') return 'Sedang Dipinjam';
    if (sp == 'lunas') return 'Dibayar';
    if (sp == 'menunggu_konfirmasi') return 'Menunggu Konfirmasi';
    return 'Belum Bayar';
  }

  Color _statusBadgeColor(dynamic r) {
    final sp = r['status_pembayaran'] ?? 'pending';
    final spj = r['status_pinjam'] ?? 'belum_mulai';
    if (spj == 'selesai') return Colors.grey;
    if (spj == 'sedang_dipinjam') return Colors.blue;
    if (sp == 'lunas') return Colors.green;
    if (sp == 'menunggu_konfirmasi') return Colors.orange;
    return Colors.amber.shade700;
  }

  Color _cardBorderColor(dynamic r) {
    final sp = r['status_pembayaran'] ?? 'pending';
    final spj = r['status_pinjam'] ?? 'belum_mulai';
    if (spj == 'selesai') return Colors.grey.shade300;
    if (spj == 'sedang_dipinjam') return Colors.blue.shade200;
    if (sp == 'lunas') return Colors.green.shade200;
    if (sp == 'menunggu_konfirmasi') return Colors.orange.shade200;
    return Colors.amber.shade200;
  }

  bool _canReport(dynamic r) {
    final sp = r['status_pembayaran'] ?? 'pending';
    final spj = r['status_pinjam'] ?? 'belum_mulai';
    return sp == 'lunas' || spj == 'sedang_dipinjam' || spj == 'selesai';
  }

  List<dynamic> get _filteredRentals {
    switch (_selectedTab) {
      case 'menunggu_pembayaran':
        return _rentals.where((r) => r['status_pembayaran'] == 'pending').toList();
      case 'menunggu_konfirmasi':
        return _rentals.where((r) => r['status_pembayaran'] == 'menunggu_konfirmasi').toList();
      case 'lunas':
        return _rentals.where((r) => r['status_pembayaran'] == 'lunas' && r['status_pinjam'] == 'belum_mulai').toList();
      case 'sedang_dipinjam':
        return _rentals.where((r) => r['status_pinjam'] == 'sedang_dipinjam').toList();
      case 'selesai':
        return _rentals.where((r) => r['status_pinjam'] == 'selesai').toList();
      default:
        return _rentals;
    }
  }

  // ── Aksi ──

  Future<void> _showReportDialog(int rentalId, String namaBarang, String pemilik) async {
    final detailController = TextEditingController();
    final reasons = ['Barang tidak dikembalikan', 'Barang rusak', 'Barang hilang', 'Pemilik tidak kooperatif', 'Penipuan', 'Lainnya'];
    String selectedReason = reasons[0];

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDialogState) => AlertDialog(
          title: const Text('Laporkan Pesanan'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Barang: $namaBarang', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                Text('Pemilik: $pemilik', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                const SizedBox(height: 16),
                const Text('Alasan Laporan', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                const SizedBox(height: 8),
                DropdownButtonFormField<String>(
                  initialValue: selectedReason,
                  items: reasons.map((r) => DropdownMenuItem(value: r, child: Text(r, style: const TextStyle(fontSize: 13)))).toList(),
                  onChanged: (val) { if (val != null) setDialogState(() => selectedReason = val); },
                  decoration: InputDecoration(border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)), contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10)),
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

    final result = await ApiService.laporkan(targetId: rentalId, reason: selectedReason, detail: detailController.text);
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(result['message'] ?? 'Terjadi kesalahan.')));
  }

  // ── Build ──

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Pesanan Saya')),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _errorMessage != null
              ? _buildError()
              : RefreshIndicator(
                  onRefresh: _loadPesanan,
                  child: Column(
                    children: [
                      _buildTabBar(),
                      Expanded(child: _buildList()),
                    ],
                  ),
                ),
    );
  }

  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Icon(Icons.error_outline, size: 48, color: Colors.grey[400]),
          const SizedBox(height: 12),
          Text(_errorMessage!, textAlign: TextAlign.center, style: const TextStyle(color: Colors.grey)),
          const SizedBox(height: 12),
          TextButton(onPressed: _loadPesanan, child: const Text('Coba lagi')),
        ]),
      ),
    );
  }

  Widget _buildTabBar() {
    final tabs = [
      {'key': 'semua', 'label': 'Semua', 'count': _stats['semua'] ?? 0},
      {'key': 'menunggu_pembayaran', 'label': 'Belum Bayar', 'count': _stats['belum_bayar'] ?? 0},
      {'key': 'menunggu_konfirmasi', 'label': 'Menunggu Konfirmasi', 'count': _stats['menunggu_konfirmasi'] ?? 0},
      {'key': 'lunas', 'label': 'Dibayar', 'count': _stats['lunas'] ?? 0},
      {'key': 'sedang_dipinjam', 'label': 'Dipinjam', 'count': _stats['sedang_dipinjam'] ?? 0},
      {'key': 'selesai', 'label': 'Selesai', 'count': _stats['selesai'] ?? 0},
    ];

    return SizedBox(
      height: 44,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
        itemCount: tabs.length,
        separatorBuilder: (_, _) => const SizedBox(width: 6),
        itemBuilder: (ctx, i) {
          final t = tabs[i];
          final sel = t['key'] == _selectedTab;
          return GestureDetector(
            onTap: () => setState(() => _selectedTab = t['key'] as String),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12),
              decoration: BoxDecoration(
                color: sel ? _brandColor : Colors.white,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: sel ? _brandColor : Colors.grey.shade300),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(t['label'] as String, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: sel ? Colors.white : Colors.grey.shade600)),
                  const SizedBox(width: 4),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                    decoration: BoxDecoration(color: sel ? Colors.white.withValues(alpha: 0.25) : Colors.grey.shade100, borderRadius: BorderRadius.circular(10)),
                    child: Text('${t['count']}', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: sel ? Colors.white : Colors.grey.shade500)),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildList() {
    final list = _filteredRentals;

    if (list.isEmpty) {
      return ListView(
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 60),
            child: Column(children: [
              Icon(Icons.shopping_bag_outlined, size: 48, color: Colors.grey[300]),
              const SizedBox(height: 12),
              const Text('Tidak ada pesanan', style: TextStyle(fontWeight: FontWeight.w600)),
              const SizedBox(height: 4),
              const Text('Belum ada pesanan di kategori ini.', style: TextStyle(color: Colors.grey, fontSize: 12.5)),
            ]),
          ),
        ],
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: list.length,
      itemBuilder: (ctx, i) => _buildOrderCard(list[i]),
    );
  }

  Widget _buildOrderCard(dynamic r) {
    final sp = r['status_pembayaran'] ?? 'pending';
    final spj = r['status_pinjam'] ?? 'belum_mulai';
    final borderColor = _cardBorderColor(r);

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: borderColor, width: 1.5),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        children: [
          // Main content
          Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Thumbnail
                ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    width: 60, height: 60,
                    color: Colors.grey[200],
                    child: r['gambar_url'] != null
                        ? Image.network(
                            r['gambar_url'],
                            width: 60,
                            height: 60,
                            fit: BoxFit.cover,
                            errorBuilder: (context, error, stackTrace) {
                              return Container(
                                color: Colors.grey[200],
                                child: const Icon(Icons.broken_image_outlined, color: Colors.grey, size: 24),
                              );
                            },
                          )
                        : const Icon(Icons.inventory_2_outlined, color: Colors.grey, size: 24),
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
                        Icon(Icons.person_outline, size: 13, color: Colors.grey.shade500),
                        const SizedBox(width: 3),
                        Text(r['pemilik'] ?? '-', style: TextStyle(fontSize: 11.5, color: Colors.grey.shade600)),
                      ]),
                      const SizedBox(height: 2),
                      Row(children: [
                        Icon(Icons.calendar_today_outlined, size: 13, color: Colors.grey.shade500),
                        const SizedBox(width: 3),
                        Text('${r['durasi_hari']} hari', style: TextStyle(fontSize: 11.5, color: Colors.grey.shade600)),
                        if ((r['lokasi'] ?? '').toString().isNotEmpty) ...[
                          const SizedBox(width: 8),
                          Icon(Icons.location_on_outlined, size: 13, color: Colors.grey.shade500),
                          const SizedBox(width: 3),
                          Flexible(child: Text(r['lokasi'], style: TextStyle(fontSize: 11.5, color: Colors.grey.shade600), overflow: TextOverflow.ellipsis)),
                        ],
                      ]),
                    ],
                  ),
                ),
                // Badge + Harga
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(color: _statusBadgeColor(r).withValues(alpha: 0.1), borderRadius: BorderRadius.circular(20)),
                      child: Text(_statusBadgeLabel(r), style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: _statusBadgeColor(r))),
                    ),
                    const SizedBox(height: 6),
                    Text(_formatHarga(r['total_harga'] ?? 0), style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: _brandColor)),
                    Text(_formatId(r['rental_id']), style: const TextStyle(fontSize: 10, color: Colors.grey)),
                  ],
                ),
              ],
            ),
          ),

          // Progress bar (sedang_dipinjam)
          if (spj == 'sedang_dipinjam') _buildProgressBar(r),

          // Footer
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(color: const Color(0xFFF8F9FB), border: Border(top: BorderSide(color: const Color(0xFFE0E0E0)))),
            child: Column(
              children: [
                // Dates
                Row(
                  children: [
                    Icon(Icons.event_outlined, size: 14, color: Colors.grey.shade400),
                    const SizedBox(width: 4),
                    Flexible(child: Text('${_formatTanggal(r['tanggal_mulai'])} → ${_formatTanggal(r['tanggal_selesai'])}', style: TextStyle(fontSize: 11, color: Colors.grey.shade600), overflow: TextOverflow.ellipsis)),
                  ],
                ),
                const SizedBox(height: 8),
                // Action buttons
                Wrap(
                  spacing: 6,
                  runSpacing: 6,
                  children: _buildActionButtons(r),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildProgressBar(dynamic r) {
    final now = DateTime.now().millisecondsSinceEpoch ~/ 1000;
    final mulai = DateTime.parse(r['tanggal_mulai']).millisecondsSinceEpoch ~/ 1000;
    final selesai = DateTime.parse(r['tanggal_selesai']).millisecondsSinceEpoch ~/ 1000;
    final progress = selesai > mulai ? ((now - mulai) / (selesai - mulai) * 100).clamp(0, 100) : 0;
    final sisaHari = ((selesai - now) / 86400).ceil().clamp(0, 999);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(color: Colors.blue.shade50, border: Border(top: BorderSide(color: const Color(0xFFBBDEFB)))),
      child: Row(
        children: [
          const Icon(Icons.access_time, size: 16, color: Colors.blue),
          const SizedBox(width: 8),
          Expanded(
            child: ClipRRect(
              borderRadius: BorderRadius.circular(20),
              child: LinearProgressIndicator(value: progress / 100, backgroundColor: Colors.blue.shade100, color: _brandColor, minHeight: 6),
            ),
          ),
          const SizedBox(width: 8),
          Text('$sisaHari hari lagi', style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: Colors.blue)),
        ],
      ),
    );
  }

  List<Widget> _buildActionButtons(dynamic r) {
    final sp = r['status_pembayaran'] ?? 'pending';
    final spj = r['status_pinjam'] ?? 'belum_mulai';
    final buttons = <Widget>[];

    if (sp == 'pending') {
      // Bayar sekarang
      buttons.add(
        ElevatedButton.icon(
          onPressed: () async {
            final result = await Navigator.push(context, MaterialPageRoute(builder: (_) => ProsesPembayaranScreen(
              rentalId: r['rental_id'], itemId: r['item_id'], namaBarang: r['nama_barang'],
              gambarUrl: r['gambar_url'], durasiHari: r['durasi_hari'], totalHarga: r['total_harga'],
            )));
            if (result == true) _loadPesanan();
          },
          icon: const Icon(Icons.credit_card, size: 14),
          label: const Text('Bayar', style: TextStyle(fontSize: 11)),
          style: ElevatedButton.styleFrom(backgroundColor: _brandColor, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6)),
        ),
      );
    } else if (sp == 'menunggu_konfirmasi') {
      buttons.add(
        OutlinedButton.icon(
          onPressed: () {
            Navigator.push(context, MaterialPageRoute(builder: (_) => ProsesPembayaranScreen(
              rentalId: r['rental_id'], itemId: r['item_id'], namaBarang: r['nama_barang'],
              gambarUrl: r['gambar_url'], durasiHari: r['durasi_hari'], totalHarga: r['total_harga'],
            )));
          },
          icon: const Icon(Icons.visibility_outlined, size: 14),
          label: const Text('Lihat Status', style: TextStyle(fontSize: 11)),
          style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6)),
        ),
      );
    } else if (sp == 'lunas' || spj == 'sedang_dipinjam') {
      // WA Pemilik
      if ((r['wa_pemilik'] ?? '').toString().isNotEmpty) {
        buttons.add(
          OutlinedButton.icon(
            onPressed: () async {
              final cleaned = r['wa_pemilik'].toString().replaceAll(RegExp(r'[^0-9]'), '');
              final url = 'https://wa.me/62${cleaned.startsWith('0') ? cleaned.substring(1) : cleaned}';
              await ApiService.openUrl(url);
            },
            icon: const Icon(Icons.chat_outlined, size: 14),
            label: const Text('Hubungi', style: TextStyle(fontSize: 11)),
            style: OutlinedButton.styleFrom(foregroundColor: Colors.green, side: const BorderSide(color: Colors.green), padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6)),
          ),
        );
      }
      // Detail
      buttons.add(
        OutlinedButton.icon(
          onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => DetailBarangScreen(itemId: r['item_id']))),
          icon: const Icon(Icons.visibility_outlined, size: 14),
          label: const Text('Detail', style: TextStyle(fontSize: 11)),
          style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6)),
        ),
      );
    } else if (spj == 'selesai') {
      // Sewa lagi
      buttons.add(
        OutlinedButton.icon(
          onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => DetailBarangScreen(itemId: r['item_id']))),
          icon: const Icon(Icons.replay, size: 14),
          label: const Text('Sewa Lagi', style: TextStyle(fontSize: 11)),
          style: OutlinedButton.styleFrom(foregroundColor: _brandColor, side: BorderSide(color: _brandColor.withValues(alpha: 0.4)), padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6)),
        ),
      );
    }

    // Report button
    if (_canReport(r)) {
      buttons.add(
        OutlinedButton.icon(
          onPressed: () => _showReportDialog(r['rental_id'], r['nama_barang'], r['pemilik']),
          icon: const Icon(Icons.flag_outlined, size: 14),
          label: const Text('Laporkan', style: TextStyle(fontSize: 11)),
          style: OutlinedButton.styleFrom(foregroundColor: Colors.red, side: const BorderSide(color: Colors.red), padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6)),
        ),
      );
    }

    return buttons;
  }
}