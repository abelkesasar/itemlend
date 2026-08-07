import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../services/api_service.dart';

class AdminPencairanScreen extends StatefulWidget {
  const AdminPencairanScreen({super.key});

  @override
  State<AdminPencairanScreen> createState() => _AdminPencairanScreenState();
}

class _AdminPencairanScreenState extends State<AdminPencairanScreen> {
  List<dynamic> _items = [];
  Map<String, dynamic> _stats = {};
  bool _isLoading = true;
  int _tab = 0; // 0=belum, 1=sudah, 2=semua
  String _search = '';
  String _sort = 'terbaru';

  @override
  void initState() {
    super.initState();
    _loadPencairan();
  }

  Future<void> _loadPencairan() async {
    setState(() => _isLoading = true);
    final tabKey = ['belum', 'sudah', 'semua'][_tab];
    final result = await ApiService.getAdminPencairan(tab: tabKey, search: _search, sort: _sort);
    if (mounted) {
      setState(() {
        if (result['success'] == true) {
          _items = result['data']['rentals'] ?? [];
          _stats = result['data']['stats'] ?? {};
        }
        _isLoading = false;
      });
    }
  }

  Future<void> _cairkanDana(Map rental) async {
    final picker = ImagePicker();
    final image = await picker.pickImage(source: ImageSource.gallery, imageQuality: 80);

    String? buktiPath;
    if (image != null) buktiPath = image.path;

    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Cairkan Dana?'),
        content: Text(
          'Dana akan dicairkan ke pemilik "${rental['pemilik'] ?? '-'}".\n'
          'Jumlah: Rp ${_formatRp(rental['jumlah_dicairkan'] ?? 0)}',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Cairkan', style: TextStyle(color: Color(0xFF3D4BFF))),
          ),
        ],
      ),
    );
    if (confirm == true) {
      final file = buktiPath != null ? File(buktiPath) : null;
      final result = await ApiService.adminPencairanAction(
        rentalId: rental['id'],
        buktiFile: file,
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'Selesai'),
            backgroundColor: result['success'] == true ? Colors.green : Colors.red,
          ),
        );
        _loadPencairan();
      }
    }
  }

  int _calcDuration(dynamic mulai, dynamic selesai) {
    try {
      final start = DateTime.parse(mulai.toString());
      final end = DateTime.parse(selesai.toString());
      return end.difference(start).inDays;
    } catch (_) {
      return 0;
    }
  }

  int _calcTotal(dynamic totalHarga, dynamic harga, dynamic mulai, dynamic selesai) {
    if (totalHarga != null && int.tryParse(totalHarga.toString()) != null && int.parse(totalHarga.toString()) > 0) {
      return int.parse(totalHarga.toString());
    }
    final h = int.tryParse(harga.toString()) ?? 0;
    final dur = _calcDuration(mulai, selesai);
    return h * dur;
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
          'Pencairan Dana',
          style: TextStyle(color: Color(0xFF1A1D2E), fontWeight: FontWeight.w700, fontSize: 18),
        ),
        actions: [
          PopupMenuButton<String>(
            onSelected: (v) {
              setState(() => _sort = v);
              _loadPencairan();
            },
            itemBuilder: (ctx) => [
              const PopupMenuItem(value: 'terbaru', child: Text('Terbaru')),
              const PopupMenuItem(value: 'terlama', child: Text('Terlama')),
              const PopupMenuItem(value: 'terbesar', child: Text('Nilai Tertinggi')),
            ],
            icon: const Icon(Icons.sort, color: Color(0xFF6B7280)),
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF3D4BFF)))
          : Column(
              children: [
                // Stats
                Container(
                  color: Colors.white,
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                  child: Row(
                    children: [
                      _statChip('Perlu Cair', '${_stats['belum'] ?? 0}', const Color(0xFFF59E0B)),
                      const SizedBox(width: 6),
                      _statChip('Tertahan', 'Rp${_formatRp(_stats['nilai_belum'] ?? 0)}', const Color(0xFFEF4444)),
                      const SizedBox(width: 6),
                      _statChip('Sudah Cair', '${_stats['sudah'] ?? 0}', const Color(0xFF16A34A)),
                      const SizedBox(width: 6),
                      _statChip('Total', 'Rp${_formatRp(_stats['nilai_sudah'] ?? 0)}', const Color(0xFF3D4BFF)),
                    ],
                  ),
                ),
                // Tabs
                Container(
                  color: Colors.white,
                  padding: const EdgeInsets.fromLTRB(16, 10, 16, 12),
                  child: Row(
                    children: [
                      _tabBtn('Belum Dicairkan', 0),
                      const SizedBox(width: 8),
                      _tabBtn('Sudah Dicairkan', 1),
                      const SizedBox(width: 8),
                      _tabBtn('Semua', 2),
                    ],
                  ),
                ),
                // Search
                Container(
                  color: Colors.white,
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
                  child: TextField(
                    onChanged: (v) {
                      _search = v;
                      _loadPencairan();
                    },
                    decoration: InputDecoration(
                      hintText: 'Cari nama barang atau pemilik...',
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
                  child: RefreshIndicator(
                    onRefresh: _loadPencairan,
                    child: _items.isEmpty
                        ? const Center(child: Text('Tidak ada data pencairan', style: TextStyle(color: Color(0xFF9CA3AF))))
                        : ListView.builder(
                            padding: const EdgeInsets.all(16),
                            itemCount: _items.length,
                            itemBuilder: (ctx, i) => _pencairanCard(_items[i]),
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
          color: color.withOpacity(0.08),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: color.withOpacity(0.2)),
        ),
        child: Column(
          children: [
            FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(value, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: color)),
            ),
            Text(label, style: TextStyle(fontSize: 9, fontWeight: FontWeight.w600, color: color)),
          ],
        ),
      ),
    );
  }

  Widget _tabBtn(String label, int index) {
    final isActive = _tab == index;
    return Expanded(
      child: GestureDetector(
        onTap: () {
          setState(() => _tab = index);
          _loadPencairan();
        },
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color: isActive ? const Color(0xFF3D4BFF) : Colors.transparent,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: isActive ? const Color(0xFF3D4BFF) : const Color(0xFFE5E7EB)),
          ),
          child: Text(
            label,
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: isActive ? Colors.white : const Color(0xFF6B7280)),
          ),
        ),
      ),
    );
  }

  Widget _pencairanCard(Map r) {
    final namaBarang = r['nama_barang'] ?? '-';
    final pemilik = r['pemilik'] ?? '-';
    final penyewa = r['penyewa'] ?? '-';
    final harga = r['harga'] ?? 0;
    final mulai = r['tanggal_mulai'] ?? '-';
    final selesai = r['tanggal_selesai'] ?? '-';
    final totalHarga = r['total_harga'] ?? 0;
    final komisiAdmin = r['komisi_admin'];
    final grDeduction = r['ganti_rugi_deduction'] ?? 0;
    final jumlahDicairkan = r['jumlah_dicairkan'] ?? 0;
    final statusPencairan = r['status_pencairan'] ?? 'belum_dicairkan';
    final buktiPencairan = r['bukti_pencairan'];
    final tanggalPencairan = r['tanggal_pencairan'];
    final hasPendingReports = r['has_pending_reports'] == true;

    final total = _calcTotal(totalHarga, harga, mulai, selesai);
    final komisi = komisiAdmin ?? (total * 5 / 100).round();
    final dicairkan = total - komisi + (int.tryParse(grDeduction.toString()) ?? 0);
    final isBelum = statusPencairan == 'belum_dicairkan';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isBelum && hasPendingReports
              ? const Color(0xFFFBBF24)
              : const Color(0xFFE5E7EB),
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: isBelum ? const Color(0xFFFEF3C7) : const Color(0xFFE9F9F0),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(
                    isBelum ? Icons.account_balance_wallet : Icons.check_circle,
                    color: isBelum ? const Color(0xFFD97706) : const Color(0xFF16A34A),
                    size: 20,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(namaBarang, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700), maxLines: 1, overflow: TextOverflow.ellipsis),
                      Text('Pemilik: $pemilik · Penyewa: $penyewa', style: const TextStyle(fontSize: 11, color: Color(0xFF6B7280))),
                    ],
                  ),
                ),
                if (isBelum)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(color: const Color(0xFFFEF3C7), borderRadius: BorderRadius.circular(12)),
                    child: const Text('Belum', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFFD97706))),
                  )
                else
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(color: const Color(0xFFE9F9F0), borderRadius: BorderRadius.circular(12)),
                    child: const Text('Cair', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF16A34A))),
                  ),
              ],
            ),
            const SizedBox(height: 12),

            // Period
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(color: const Color(0xFFF8F9FF), borderRadius: BorderRadius.circular(8)),
              child: Row(
                children: [
                  const Icon(Icons.calendar_today, size: 14, color: Color(0xFF9CA3AF)),
                  const SizedBox(width: 8),
                  Text(
                    '${_formatDate(mulai)} → ${_formatDate(selesai)}',
                    style: const TextStyle(fontSize: 12, color: Color(0xFF374151)),
                  ),
                  const Spacer(),
                  Text(
                    '${_calcDuration(mulai, selesai)} hari',
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF3D4BFF)),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 10),

            // Nominal breakdown
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFF8F9FF),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFFE0E3FF)),
              ),
              child: Column(
                children: [
                  _nominalRow('Total Harga', 'Rp ${_formatRp(total)}', false),
                  const SizedBox(height: 4),
                  _nominalRow('Komisi Admin (5%)', '- Rp ${_formatRp(komisi)}', true),
                  if (int.tryParse(grDeduction.toString()) != null && int.parse(grDeduction.toString()) > 0) ...[
                    const SizedBox(height: 4),
                    _nominalRow('Ganti Rugi', '+ Rp ${_formatRp(grDeduction)}', false),
                  ],
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 6),
                    child: Divider(height: 1),
                  ),
                  _nominalRow(
                    'Dicairkan',
                    'Rp ${_formatRp(isBelum ? dicairkan : jumlahDicairkan)}',
                    false,
                    isBold: true,
                  ),
                ],
              ),
            ),

            // Bukti pencairan (for sudah dicairkan)
            if (!isBelum && buktiPencairan != null && buktiPencairan.toString().isNotEmpty) ...[
              const SizedBox(height: 10),
              Row(
                children: [
                  const Icon(Icons.receipt, size: 14, color: Color(0xFF16A34A)),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      'Bukti: $buktiPencairan',
                      style: const TextStyle(fontSize: 11, color: Color(0xFF6B7280)),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  if (tanggalPencairan != null)
                    Text(_formatDate(tanggalPencairan.toString()), style: const TextStyle(fontSize: 10, color: Color(0xFF9CA3AF))),
                ],
              ),
            ],

            // Pending reports warning
            if (isBelum && hasPendingReports) ...[
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEF3C7),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: const Color(0xFFFBBF24)),
                ),
                child: const Row(
                  children: [
                    Icon(Icons.warning_amber, size: 16, color: Color(0xFFD97706)),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Ada laporan pending — pencairan ditahan.',
                        style: TextStyle(fontSize: 11, color: Color(0xFF92400E)),
                      ),
                    ),
                  ],
                ),
              ),
            ],

            // Action button
            if (isBelum && !hasPendingReports) ...[
              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () => _cairkanDana(r),
                  icon: const Icon(Icons.account_balance_wallet, size: 16),
                  label: const Text('Cairkan Dana'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF3D4BFF),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _nominalRow(String label, String value, bool isNegative, {bool isBold = false}) {
    return Row(
      children: [
        Expanded(
          child: Text(label, style: TextStyle(fontSize: 12, color: isBold ? const Color(0xFF1A1D2E) : const Color(0xFF6B7280), fontWeight: isBold ? FontWeight.w700 : FontWeight.w500)),
        ),
        Text(
          value,
          style: TextStyle(
            fontSize: 12,
            fontWeight: isBold ? FontWeight.w800 : FontWeight.w600,
            color: isBold ? const Color(0xFF3D4BFF) : (isNegative ? const Color(0xFFDC2626) : const Color(0xFF374151)),
          ),
        ),
      ],
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
}
