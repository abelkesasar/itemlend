import 'package:flutter/material.dart';
import '../services/api_service.dart';

class AdminRentalsScreen extends StatefulWidget {
  const AdminRentalsScreen({super.key});

  @override
  State<AdminRentalsScreen> createState() => _AdminRentalsScreenState();
}

class _AdminRentalsScreenState extends State<AdminRentalsScreen> {
  List<dynamic> _rentals = [];
  Map<String, dynamic> _stats = {};
  Map<String, dynamic> _tabCounts = {};
  bool _isLoading = true;
  String _tab = 'semua';
  String _search = '';
  String _sort = 'terbaru';

  final List<Map<String, String>> _tabs = [
    {'key': 'semua', 'label': 'Semua'},
    {'key': 'pending', 'label': 'Pending'},
    {'key': 'menunggu_konfirmasi', 'label': 'Konfirmasi'},
    {'key': 'lunas', 'label': 'Lunas'},
    {'key': 'ditolak', 'label': 'Ditolak'},
    {'key': 'sedang_dipinjam', 'label': 'Dipinjam'},
    {'key': 'selesai', 'label': 'Selesai'},
  ];

  @override
  void initState() {
    super.initState();
    _loadRentals();
  }

  Future<void> _loadRentals() async {
    setState(() => _isLoading = true);
    final result = await ApiService.getAdminRentals(tab: _tab, search: _search, sort: _sort);
    if (mounted) {
      setState(() {
        if (result['success'] == true) {
          _rentals = result['data']['rentals'] ?? [];
          _stats = result['data']['stats'] ?? {};
          _tabCounts = result['data']['tab_counts'] ?? {};
        }
        _isLoading = false;
      });
    }
  }

  Future<void> _konfirmasiBayar(int rentalId, String namaBarang) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Konfirmasi Pembayaran?'),
        content: Text('Pembayaran untuk "$namaBarang" akan ditandai LUNAS.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Konfirmasi', style: TextStyle(color: Color(0xFF3D4BFF))),
          ),
        ],
      ),
    );
    if (confirm == true) {
      final result = await ApiService.adminRentalAction(rentalId: rentalId, aksi: 'konfirmasi_bayar');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result['message'] ?? 'Selesai'), backgroundColor: Colors.green),
        );
        _loadRentals();
      }
    }
  }

  Future<void> _tolakBayar(int rentalId, String namaBarang) async {
    final catatanController = TextEditingController();
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Tolak Pembayaran?'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('Pembayaran untuk "$namaBarang" akan DITOLAK.'),
            const SizedBox(height: 12),
            TextField(
              controller: catatanController,
              decoration: const InputDecoration(
                labelText: 'Alasan penolakan',
                border: OutlineInputBorder(),
                isDense: true,
              ),
              maxLines: 2,
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Tolak', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (confirm == true) {
      final result = await ApiService.adminRentalAction(
        rentalId: rentalId,
        aksi: 'tolak_bayar',
        catatan: catatanController.text.isNotEmpty ? catatanController.text : null,
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result['message'] ?? 'Selesai'), backgroundColor: Colors.orange),
        );
        _loadRentals();
      }
    }
  }

  Color _statusColor(String? status) {
    switch (status) {
      case 'lunas': return const Color(0xFF16A34A);
      case 'pending': return const Color(0xFFF59E0B);
      case 'menunggu_konfirmasi': return const Color(0xFF3D4BFF);
      case 'ditolak': return const Color(0xFFEF4444);
      case 'sedang_dipinjam': return const Color(0xFF7C3AED);
      case 'selesai': return const Color(0xFF0D7377);
      case 'belum_mulai': return const Color(0xFF9CA3AF);
      default: return const Color(0xFF6B7280);
    }
  }

  String _statusLabel(String? status) {
    switch (status) {
      case 'lunas': return 'Lunas';
      case 'pending': return 'Pending';
      case 'menunggu_konfirmasi': return 'Perlu Konfirmasi';
      case 'ditolak': return 'Ditolak';
      case 'sedang_dipinjam': return 'Sedang Dipinjam';
      case 'selesai': return 'Selesai';
      case 'belum_mulai': return 'Belum Mulai';
      default: return status ?? '-';
    }
  }

  String _formatRp(dynamic amount) {
    final val = int.tryParse(amount.toString()) ?? 0;
    return val.toString().replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]}.');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF1A1D2E)),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'Kelola Rental',
          style: TextStyle(color: Color(0xFF1A1D2E), fontWeight: FontWeight.w700, fontSize: 18),
        ),
        actions: [
          PopupMenuButton<String>(
            onSelected: (v) {
              setState(() => _sort = v);
              _loadRentals();
            },
            itemBuilder: (ctx) => [
              const PopupMenuItem(value: 'terbaru', child: Text('Terbaru')),
              const PopupMenuItem(value: 'termahal', child: Text('Harga Tertinggi')),
              const PopupMenuItem(value: 'termurah', child: Text('Harga Terendah')),
            ],
            icon: const Icon(Icons.sort, color: Color(0xFF6B7280)),
          ),
        ],
      ),
      body: Column(
        children: [
          // Stats row
          if (_stats.isNotEmpty)
            Container(
              color: Colors.white,
              padding: const EdgeInsets.fromLTRB(16, 10, 16, 0),
              child: Row(
                children: [
                  _statChip('Total', '${_stats['total'] ?? 0}', const Color(0xFF3D4BFF)),
                  const SizedBox(width: 8),
                  _statChip('Konfirmasi', '${_stats['perlu_konfirmasi'] ?? 0}', const Color(0xFFF59E0B)),
                  const SizedBox(width: 8),
                  _statChip('Revenue', 'Rp${_formatRp(_stats['revenue'] ?? 0)}', const Color(0xFF16A34A)),
                ],
              ),
            ),
          // Tabs (horizontal scroll)
          Container(
            color: Colors.white,
            padding: const EdgeInsets.only(top: 10, left: 16, right: 16),
            child: SizedBox(
              height: 36,
              child: ListView.builder(
                scrollDirection: Axis.horizontal,
                itemCount: _tabs.length,
                itemBuilder: (ctx, i) {
                  final t = _tabs[i];
                  final isActive = _tab == t['key'];
                  final count = _tabCounts[t['key']] ?? 0;
                  return GestureDetector(
                    onTap: () {
                      setState(() => _tab = t['key']!);
                      _loadRentals();
                    },
                    child: Container(
                      margin: const EdgeInsets.only(right: 8),
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      decoration: BoxDecoration(
                        color: isActive ? const Color(0xFF3D4BFF) : Colors.transparent,
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(
                          color: isActive ? const Color(0xFF3D4BFF) : const Color(0xFFE5E7EB),
                        ),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            '${t['label']}',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              color: isActive ? Colors.white : const Color(0xFF6B7280),
                            ),
                          ),
                          if (count > 0) ...[
                            const SizedBox(width: 4),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                              decoration: BoxDecoration(
                                color: isActive ? Colors.white.withValues(alpha: 0.25) : const Color(0xFFF3F4F6),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Text(
                                '$count',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                  color: isActive ? Colors.white : const Color(0xFF6B7280),
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
          ),
          // Search
          Container(
            color: Colors.white,
            padding: const EdgeInsets.fromLTRB(16, 10, 16, 12),
            child: TextField(
              onChanged: (v) {
                _search = v;
                _loadRentals();
              },
              decoration: InputDecoration(
                hintText: 'Cari nama barang, penyewa, atau pemilik...',
                prefixIcon: const Icon(Icons.search, size: 20),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                filled: true,
                fillColor: const Color(0xFFF5F6FA),
                contentPadding: const EdgeInsets.symmetric(horizontal: 14),
                isDense: true,
              ),
            ),
          ),
          // List
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: Color(0xFF3D4BFF)))
                : RefreshIndicator(
                    onRefresh: _loadRentals,
                    child: _rentals.isEmpty
                        ? const Center(child: Text('Tidak ada rental', style: TextStyle(color: Color(0xFF9CA3AF))))
                        : ListView.builder(
                            padding: const EdgeInsets.all(16),
                            itemCount: _rentals.length,
                            itemBuilder: (ctx, i) => _rentalCard(_rentals[i]),
                          ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _statChip(String label, String value, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: color.withValues(alpha: 0.2)),
        ),
        child: Column(
          children: [
            FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(value, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: color)),
            ),
            Text(label, style: TextStyle(fontSize: 9, fontWeight: FontWeight.w600, color: color)),
          ],
        ),
      ),
    );
  }

  Widget _rentalCard(Map r) {
    final namaBarang = r['nama_barang'] ?? '-';
    final pemilik = r['pemilik'] ?? '-';
    final penyewa = r['penyewa'] ?? '-';
    final totalHarga = r['total_harga'] ?? 0;
    final statusBayar = r['status_pembayaran'] ?? '-';
    final statusPinjam = r['status_pinjam'] ?? '-';
    final bukti = r['bukti_pembayaran'];
    final tanggal = r['created_at'] ?? '-';
    final gambar = r['gambar'];

    String? imageUrl;
    if (gambar != null && gambar.toString().isNotEmpty) {
      try {
        final decoded = List<String>.from(gambar is List ? gambar : [gambar]);
        if (decoded.isNotEmpty) imageUrl = 'http://10.0.2.2/itemlend/uploads/${decoded[0]}';
      } catch (_) {}
    }

    final isPendingKonfirmasi = statusBayar == 'menunggu_konfirmasi';

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isPendingKonfirmasi ? const Color(0xFFFDE68A) : const Color(0xFFE5E7EB),
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header: image + info
            Row(
              children: [
                // Thumbnail
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: const Color(0xFFF0F1F5),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: imageUrl != null
                      ? ClipRRect(
                          borderRadius: BorderRadius.circular(8),
                          child: Image.network(imageUrl, fit: BoxFit.cover,
                            errorBuilder: (_, _, _) => const Icon(Icons.shopping_bag, color: Color(0xFFC9CCD4), size: 20),
                          ),
                        )
                      : const Center(child: Icon(Icons.shopping_bag, color: Color(0xFFC9CCD4), size: 20)),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(namaBarang, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700), maxLines: 1, overflow: TextOverflow.ellipsis),
                      const SizedBox(height: 2),
                      Text(
                        '$penyewa → $pemilik',
                        style: const TextStyle(fontSize: 11, color: Color(0xFF6B7280)),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                Text(
                  'Rp ${_formatRp(totalHarga)}',
                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Color(0xFF3D4BFF)),
                ),
              ],
            ),
            const SizedBox(height: 10),
            // Status badges
            Row(
              children: [
                _badge(_statusLabel(statusBayar), _statusColor(statusBayar)),
                const SizedBox(width: 6),
                _badge(_statusLabel(statusPinjam), _statusColor(statusPinjam)),
                const Spacer(),
                if (tanggal != null && tanggal != '-')
                  Text(
                    _formatDate(tanggal),
                    style: const TextStyle(fontSize: 10, color: Color(0xFF9CA3AF)),
                  ),
              ],
            ),
            // Bukti pembayaran
            if (bukti != null && bukti.toString().isNotEmpty) ...[
              const SizedBox(height: 8),
              GestureDetector(
                onTap: () => _showBuktiDialog(bukti),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8F9FF),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: const Color(0xFFE0E3FF)),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.receipt, size: 14, color: Color(0xFF3D4BFF)),
                      SizedBox(width: 6),
                      Text('Lihat Bukti Pembayaran', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF3D4BFF))),
                    ],
                  ),
                ),
              ),
            ],
            // Actions
            if (isPendingKonfirmasi) ...[
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () => _konfirmasiBayar(r['id'], namaBarang),
                      icon: const Icon(Icons.check, size: 16),
                      label: const Text('Konfirmasi Bayar'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF3D4BFF),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        elevation: 0,
                        padding: const EdgeInsets.symmetric(vertical: 8),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => _tolakBayar(r['id'], namaBarang),
                      icon: const Icon(Icons.close, size: 16, color: Colors.red),
                      label: const Text('Tolak', style: TextStyle(color: Colors.red)),
                      style: OutlinedButton.styleFrom(
                        side: const BorderSide(color: Colors.red),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        padding: const EdgeInsets.symmetric(vertical: 8),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _badge(String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(text, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: color)),
    );
  }

  String _formatDate(String date) {
    try {
      final d = DateTime.parse(date);
      return '${d.day}/${d.month}/${d.year}';
    } catch (_) {
      return date;
    }
  }

  void _showBuktiDialog(dynamic bukti) {
    String? imageUrl;
    if (bukti is String && bukti.isNotEmpty) {
      imageUrl = 'http://10.0.2.2/itemlend/uploads/bukti/$bukti';
    } else if (bukti is List && bukti.isNotEmpty) {
      imageUrl = 'http://10.0.2.2/itemlend/uploads/bukti/${bukti[0]}';
    }
    if (imageUrl == null) return;

    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  const Expanded(
                    child: Text('Bukti Pembayaran', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(ctx),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              child: imageUrl != null
                  ? ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: Image.network(
                        imageUrl,
                        fit: BoxFit.contain,
                        errorBuilder: (_, _, _) => const SizedBox(
                          height: 120,
                          child: Center(child: Text('Gagal memuat gambar')),
                        ),
                      ),
                    )
                  : const SizedBox(
                      height: 120,
                      child: Center(child: Text('Tidak ada gambar')),
                    ),
            ),
          ],
        ),
      ),
    );
  }
}
