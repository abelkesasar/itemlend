import 'package:flutter/material.dart';
import '../services/api_service.dart';

class AdminReportsScreen extends StatefulWidget {
  const AdminReportsScreen({super.key});

  @override
  State<AdminReportsScreen> createState() => _AdminReportsScreenState();
}

class _AdminReportsScreenState extends State<AdminReportsScreen> {
  List<dynamic> _reports = [];
  Map<String, dynamic> _stats = {};
  bool _isLoading = true;
  int _tab = 0; // 0=pending, 1=history

  @override
  void initState() {
    super.initState();
    _loadReports();
  }

  Future<void> _loadReports() async {
    setState(() => _isLoading = true);
    final result = await ApiService.getAdminReports(status: 'all');
    if (mounted) {
      setState(() {
        if (result['success'] == true) {
          _reports = result['data']['reports'] ?? [];
          _stats = result['data']['stats'] ?? {};
        }
        _isLoading = false;
      });
    }
  }

  List<dynamic> get _pendingReports => _reports.where((r) => r['status'] == 'pending').toList();
  List<dynamic> get _historyReports => _reports.where((r) => r['status'] != 'pending').toList();

  Future<void> _processReport(Map r, Map<String, String> form) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Proses Laporan?'),
        content: const Text('Pastikan sanksi sudah dipilih dengan benar. Tindakan ini tidak dapat dibatalkan.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Proses', style: TextStyle(color: Color(0xFF3D4BFF))),
          ),
        ],
      ),
    );
    if (confirm == true) {
      final result = await ApiService.adminReportAction(
        reportId: r['id'],
        sanksiOption: form['sanksi'] ?? 'none',
        refundOption: form['refund'] ?? 'tidak_ada',
        catatanRefund: form['catatan_refund'] ?? '',
        tagihanGantiRugi: form['tagihan_desc'] ?? '',
        amountGantiRugi: int.tryParse(form['amount_ganti_rugi'] ?? '0') ?? 0,
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result['message'] ?? 'Selesai'), backgroundColor: Colors.green),
        );
        _loadReports();
      }
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
          'Kelola Laporan',
          style: TextStyle(color: Color(0xFF1A1D2E), fontWeight: FontWeight.w700, fontSize: 18),
        ),
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
                      _statChip('Total', '${_stats['total'] ?? 0}', const Color(0xFF3D4BFF)),
                      const SizedBox(width: 8),
                      _statChip('Pending', '${_stats['pending'] ?? 0}', const Color(0xFFF59E0B)),
                      const SizedBox(width: 8),
                      _statChip('Selesai', '${_stats['reviewed'] ?? 0}', const Color(0xFF16A34A)),
                      const SizedBox(width: 8),
                      _statChip('Ditolak', '${_stats['dismissed'] ?? 0}', const Color(0xFF6B7280)),
                    ],
                  ),
                ),
                // Tabs
                Container(
                  color: Colors.white,
                  padding: const EdgeInsets.fromLTRB(16, 10, 16, 12),
                  child: Row(
                    children: [
                      _tabBtn('Belum Selesai', 0, _pendingReports.length),
                      const SizedBox(width: 8),
                      _tabBtn('Riwayat', 1, _historyReports.length),
                    ],
                  ),
                ),
                // List
                Expanded(
                  child: RefreshIndicator(
                    onRefresh: _loadReports,
                    child: _tab == 0
                        ? (_pendingReports.isEmpty
                            ? const Center(child: Text('Tidak ada laporan pending', style: TextStyle(color: Color(0xFF9CA3AF))))
                            : ListView.builder(
                                padding: const EdgeInsets.all(16),
                                itemCount: _pendingReports.length,
                                itemBuilder: (ctx, i) => _pendingCard(_pendingReports[i]),
                              ))
                        : (_historyReports.isEmpty
                            ? const Center(child: Text('Belum ada riwayat laporan', style: TextStyle(color: Color(0xFF9CA3AF))))
                            : ListView.builder(
                                padding: const EdgeInsets.all(16),
                                itemCount: _historyReports.length,
                                itemBuilder: (ctx, i) => _historyCard(_historyReports[i]),
                              )),
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
            Text(value, style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: color)),
            Text(label, style: TextStyle(fontSize: 9, fontWeight: FontWeight.w600, color: color)),
          ],
        ),
      ),
    );
  }

  Widget _tabBtn(String label, int index, int count) {
    final isActive = _tab == index;
    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => _tab = index),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color: isActive ? const Color(0xFF3D4BFF) : Colors.transparent,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: isActive ? const Color(0xFF3D4BFF) : const Color(0xFFE5E7EB)),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(label, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: isActive ? Colors.white : const Color(0xFF6B7280))),
              const SizedBox(width: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: isActive ? Colors.white.withOpacity(0.25) : const Color(0xFFF3F4F6),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text('$count', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: isActive ? Colors.white : const Color(0xFF6B7280))),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _personTile(String name, String role, Color color, {String? wa}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          CircleAvatar(
            radius: 16,
            backgroundColor: color.withOpacity(0.1),
            child: Text(name.substring(0, 1).toUpperCase(), style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: color)),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(name, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: color)),
                Text(role, style: const TextStyle(fontSize: 10, color: Color(0xFF9CA3AF))),
              ],
            ),
          ),
          if (wa != null && wa.isNotEmpty)
            GestureDetector(
              onTap: () {
                final clean = wa.replaceAll(RegExp(r'[^0-9]'), '');
                final waNumber = clean.startsWith('0') ? '62${clean.substring(1)}' : clean;
                ApiService.openUrl('https://wa.me/$waNumber');
              },
              child: const Icon(Icons.chat, size: 18, color: Color(0xFF16A34A)),
            ),
        ],
      ),
    );
  }

  Widget _personTileWithBan(String name, String role, Color color, {String? wa, String? bannedUntil, bool isPermanentBan = false}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _personTile(name, role, color, wa: wa),
        if (bannedUntil != null && bannedUntil.isNotEmpty)
          Container(
            margin: const EdgeInsets.only(bottom: 8),
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(
              color: isPermanentBan ? const Color(0xFFFEF2F2) : const Color(0xFFFFF7E6),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: isPermanentBan ? const Color(0xFFFECACA) : const Color(0xFFFDE68A)),
            ),
            child: Text(
              isPermanentBan ? 'Banned Permanen' : 'Cooldown s/d ${_formatDate(bannedUntil)}',
              style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: isPermanentBan ? Colors.red : const Color(0xFFD97706)),
            ),
          ),
      ],
    );
  }

  Widget _pendingCard(Map r) {
    final reporterName = r['reporter_nama'] ?? '-';
    final reporterRole = r['reporter_id'] == r['pemilik_id'] ? 'Pemilik' : 'Penyewa';
    final pemilikName = r['pemilik_nama'] ?? '-';
    final penyewaName = r['penyewa_nama'] ?? '-';
    final namaBarang = r['nama_barang'] ?? '-';
    final reason = r['reason'] ?? '-';
    final detail = r['detail'] ?? '';
    final totalHarga = r['total_harga'] ?? 0;
    final statusPencairan = r['status_pencairan'] ?? '';
    final bukti = r['bukti'];

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFFDE68A)),
        boxShadow: [BoxShadow(color: const Color(0xFFF59E0B).withOpacity(0.08), blurRadius: 6, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFFFFFBEB),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(14)),
              border: Border(bottom: BorderSide(color: const Color(0xFFFDE68A).withOpacity(0.5))),
            ),
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(color: const Color(0xFFFDE68A), borderRadius: BorderRadius.circular(8)),
                  child: const Icon(Icons.flag, color: Color(0xFFD97706), size: 18),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('LAPORAN #${r['id']}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Color(0xFFD97706))),
                      Text(_formatDate(r['created_at'] ?? '-'), style: const TextStyle(fontSize: 10, color: Color(0xFF9CA3AF))),
                    ],
                  ),
                ),
                _badge('Pending', const Color(0xFFF59E0B)),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // People
                const Text('PELAPOR', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF9CA3AF), letterSpacing: 0.5)),
                const SizedBox(height: 6),
                _personTileWithBan(reporterName, reporterRole, const Color(0xFF3D4BFF),
                    wa: r['reporter_wa'], bannedUntil: r['reporter_banned_until'],
                    isPermanentBan: _isPermanentBan(r['reporter_banned_until'])),
                const SizedBox(height: 6),
                const Text('PEMILIK', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF9CA3AF), letterSpacing: 0.5)),
                const SizedBox(height: 6),
                _personTileWithBan(pemilikName, 'Pemilik Barang', const Color(0xFF7C3AED),
                    wa: r['pemilik_wa'], bannedUntil: r['pemilik_banned_until'],
                    isPermanentBan: _isPermanentBan(r['pemilik_banned_until'])),
                const SizedBox(height: 6),
                const Text('PENYEWA', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF9CA3AF), letterSpacing: 0.5)),
                const SizedBox(height: 6),
                _personTileWithBan(penyewaName, 'Penyewa', const Color(0xFF0D7377),
                    wa: r['penyewa_wa'], bannedUntil: r['penyewa_banned_until'],
                    isPermanentBan: _isPermanentBan(r['penyewa_banned_until'])),

                const Divider(height: 20),

                // Rental info
                const Text('RENTAL INFO', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF9CA3AF), letterSpacing: 0.5)),
                const SizedBox(height: 6),
                _infoRow(Icons.shopping_bag, namaBarang),
                _infoRow(Icons.calendar_today, '${_formatDate(r['tanggal_mulai'] ?? '-')} → ${_formatDate(r['tanggal_selesai'] ?? '-')}'),
                _infoRow(Icons.attach_money, 'Rp ${_formatRp(totalHarga)}'),
                _infoRow(Icons.circle, 'Status: ${r['status_pinjam'] ?? '-'}'),
                if (statusPencairan == 'ada_laporan') ...[
                  const SizedBox(height: 8),
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
                        Expanded(child: Text('Pencairan ditahan — selesaikan laporan terlebih dahulu', style: TextStyle(fontSize: 11, color: Color(0xFF92400E)))),
                      ],
                    ),
                  ),
                ],

                const Divider(height: 20),

                // Report reason
                const Text('LAPORAN', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF9CA3AF), letterSpacing: 0.5)),
                const SizedBox(height: 6),
                Text(reason, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
                if (detail.toString().isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(detail, style: const TextStyle(fontSize: 12, color: Color(0xFF6B7280))),
                ],

                // Bukti
                if (bukti != null && bukti.toString().isNotEmpty) ...[
                  const SizedBox(height: 10),
                  GestureDetector(
                    onTap: () => _showBuktiDialog('http://10.0.2.2/itemlend/uploads/$bukti'),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: Image.network(
                        'http://10.0.2.2/itemlend/uploads/$bukti',
                        height: 140,
                        width: double.infinity,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => Container(
                          height: 60,
                          color: const Color(0xFFF5F6FA),
                          child: const Center(child: Text('Gagal memuat bukti')),
                        ),
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),

          // Form sanksi
          _SanksiFormSection(report: r, onProcess: _processReport),
        ],
      ),
    );
  }

  Widget _historyCard(Map r) {
    final status = r['status'] ?? '-';
    final reporterName = r['reporter_nama'] ?? '-';
    final pemilikName = r['pemilik_nama'] ?? '-';
    final penyewaName = r['penyewa_nama'] ?? '-';
    final namaBarang = r['nama_barang'] ?? '-';
    final reason = r['reason'] ?? '-';
    final bukti = r['bukti'];
    final hasGantiRugi = (int.tryParse(r['ganti_rugi_amount']?.toString() ?? '0') ?? 0) > 0;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: status == 'reviewed' ? const Color(0xFFBBF7D0) : const Color(0xFFE5E7EB),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: status == 'reviewed' ? const Color(0xFFF0FDF4) : const Color(0xFFF9FAFB),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(14)),
            ),
            child: Row(
              children: [
                Text('#${r['id']}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Color(0xFF9CA3AF))),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(reason, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600), maxLines: 1, overflow: TextOverflow.ellipsis),
                      Text(_formatDate(r['created_at'] ?? '-'), style: const TextStyle(fontSize: 10, color: Color(0xFF9CA3AF))),
                    ],
                  ),
                ),
                _badge(status == 'reviewed' ? 'Selesai' : 'Ditolak', status == 'reviewed' ? const Color(0xFF16A34A) : const Color(0xFF6B7280)),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 10, 14, 14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // People summary
                Row(
                  children: [
                    _personAvatar(reporterName, const Color(0xFF3D4BFF)),
                    const SizedBox(width: 4),
                    _personAvatar(pemilikName, const Color(0xFF7C3AED)),
                    const SizedBox(width: 4),
                    _personAvatar(penyewaName, const Color(0xFF0D7377)),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        'Laporan $reporterName → $namaBarang',
                        style: const TextStyle(fontSize: 11, color: Color(0xFF6B7280)),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
                // Ganti rugi badge
                if (hasGantiRugi) ...[
                  const SizedBox(height: 8),
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFEF2F2),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: const Color(0xFFFCA5A5)),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.receipt, size: 14, color: Color(0xFFDC2626)),
                        const SizedBox(width: 6),
                        Text('Ganti Rugi: Rp ${_formatRp(r['ganti_rugi_amount'])}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF991B1B))),
                      ],
                    ),
                  ),
                ],
                // Refund info
                if ((r['status_refund'] ?? '') != 'tidak_ada' && (r['status_refund'] ?? '').toString().isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFE0F2FE),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: const Color(0xFF7DD3FC)),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.money, size: 14, color: Color(0xFF0369A1)),
                        const SizedBox(width: 6),
                        Text(
                          'Refund → ${r['refund_ke'] == 'penyewa' ? 'Penyewa' : 'Pemilik'}',
                          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF0369A1)),
                        ),
                      ],
                    ),
                  ),
                ],
                // Bukti laporan
                if (bukti != null && bukti.toString().isNotEmpty) ...[
                  const SizedBox(height: 8),
                  GestureDetector(
                    onTap: () => _showBuktiDialog('http://10.0.2.2/itemlend/uploads/$bukti'),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: Image.network(
                        'http://10.0.2.2/itemlend/uploads/$bukti',
                        height: 80,
                        width: 80,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => const SizedBox(width: 80, height: 80, child: Center(child: Icon(Icons.broken_image))),
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _personAvatar(String name, Color color) {
    return CircleAvatar(
      radius: 12,
      backgroundColor: color.withOpacity(0.1),
      child: Text(name.substring(0, 1).toUpperCase(), style: TextStyle(fontSize: 9, fontWeight: FontWeight.w700, color: color)),
    );
  }

  Widget _infoRow(IconData icon, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        children: [
          Icon(icon, size: 14, color: const Color(0xFF9CA3AF)),
          const SizedBox(width: 8),
          Expanded(child: Text(text, style: const TextStyle(fontSize: 12, color: Color(0xFF374151)))),
        ],
      ),
    );
  }

  Widget _badge(String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
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

  bool _isPermanentBan(dynamic bannedUntil) {
    if (bannedUntil == null || bannedUntil.toString().isEmpty) return false;
    try {
      final d = DateTime.parse(bannedUntil.toString());
      return d.isAfter(DateTime.now().add(const Duration(days: 365 * 3)));
    } catch (_) {
      return false;
    }
  }

  void _showBuktiDialog(String url) {
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
                  const Expanded(child: Text('Bukti Laporan', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700))),
                  IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: Image.network(url, fit: BoxFit.contain,
                  errorBuilder: (_, __, ___) => const SizedBox(height: 120, child: Center(child: Text('Gagal memuat gambar'))),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════
// Sanksi Form Section — StatefulWidget for form state
// ══════════════════════════════════════════════════════════════
class _SanksiFormSection extends StatefulWidget {
  final Map report;
  final Function(Map report, Map<String, String> form) onProcess;

  const _SanksiFormSection({required this.report, required this.onProcess});

  @override
  State<_SanksiFormSection> createState() => _SanksiFormSectionState();
}

class _SanksiFormSectionState extends State<_SanksiFormSection> {
  String _sanksiOption = 'none';
  String _refundOption = 'tidak_ada';
  final _catatanController = TextEditingController();
  final _tagihanDescController = TextEditingController();
  final _tagihanAmountController = TextEditingController();

  static const List<Map<String, String>> _sanksiOptions = [
    {'value': 'none', 'group': 'Tanpa Sanksi', 'label': 'Selesaikan tanpa Sanksi'},
    {'value': 'dismissed', 'group': 'Tanpa Sanksi', 'label': 'Tolak / Batalkan Laporan'},
    {'value': 'penyewa_cooldown', 'group': 'Sanksi Penyewa', 'label': 'Cooldown Penyewa (7 hari)'},
    {'value': 'penyewa_banned', 'group': 'Sanksi Penyewa', 'label': 'Ban Permanen Penyewa'},
    {'value': 'pemilik_cooldown', 'group': 'Sanksi Pemilik', 'label': 'Cooldown Pemilik + Barang (7 hari)'},
    {'value': 'pemilik_banned', 'group': 'Sanksi Pemilik', 'label': 'Ban Permanen Pemilik + Barang'},
    {'value': 'keduanya_cooldown', 'group': 'Sanksi Keduanya', 'label': 'Cooldown Keduanya (7 hari)'},
    {'value': 'keduanya_banned', 'group': 'Sanksi Keduanya', 'label': 'Ban Permanen Keduanya'},
    {'value': 'barang_cooldown', 'group': 'Sanksi Barang', 'label': 'Cooldown Barang Saja (7 hari)'},
    {'value': 'barang_hapus', 'group': 'Sanksi Barang', 'label': 'Hapus Barang Permanen'},
    {'value': 'barang_hapus_pemilik_banned', 'group': 'Sanksi Barang', 'label': 'Hapus Barang + Ban Pemilik'},
    {'value': 'tagihan_ganti_rugi', 'group': 'Tagihan', 'label': 'Tagihan Ganti Rugi'},
  ];

  @override
  Widget build(BuildContext context) {
    final penyewaName = widget.report['penyewa_nama'] ?? '-';
    final pemilikName = widget.report['pemilik_nama'] ?? '-';
    final isGantiRugi = _sanksiOption == 'tagihan_ganti_rugi';
    final isDismissed = _sanksiOption == 'dismissed';

    return Container(
      decoration: const BoxDecoration(
        border: Border(top: BorderSide(color: Color(0xFFF0F1F3))),
      ),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Warning
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: const Color(0xFFFEF3C7),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: const Color(0xFFFDE68A)),
            ),
            child: const Row(
              children: [
                Icon(Icons.warning_amber, size: 16, color: Color(0xFFD97706)),
                SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Sanksi bersifat langsung dan tidak dapat dibatalkan.',
                    style: TextStyle(fontSize: 11, color: Color(0xFF92400E)),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),

          // Sanksi dropdown
          const Text('SANKSI', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF9CA3AF), letterSpacing: 0.5)),
          const SizedBox(height: 6),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 12),
            decoration: BoxDecoration(
              border: Border.all(color: const Color(0xFFD1D5DB)),
              borderRadius: BorderRadius.circular(10),
            ),
            child: DropdownButtonHideUnderline(
              child: DropdownButton<String>(
                value: _sanksiOption,
                isExpanded: true,
                style: const TextStyle(fontSize: 12, color: Color(0xFF374151)),
                items: _buildSanksiItems(),
                onChanged: (v) => setState(() => _sanksiOption = v ?? 'none'),
              ),
            ),
          ),

          // Ganti rugi form
          if (isGantiRugi) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFFEF2F2),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFFFCA5A5)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('TAGIHAN GANTI RUGI', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF991B1B), letterSpacing: 0.5)),
                  const SizedBox(height: 8),
                  TextField(
                    controller: _tagihanDescController,
                    maxLines: 2,
                    decoration: const InputDecoration(
                      hintText: 'Jelaskan kerusakan...',
                      border: OutlineInputBorder(),
                      isDense: true,
                      contentPadding: EdgeInsets.all(10),
                    ),
                    style: const TextStyle(fontSize: 12),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      const Text('Rp ', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Color(0xFF991B1B))),
                      Expanded(
                        child: TextField(
                          controller: _tagihanAmountController,
                          keyboardType: TextInputType.number,
                          decoration: const InputDecoration(hintText: '0', border: OutlineInputBorder(), isDense: true),
                          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Color(0xFF991B1B)),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],

          // Refund section (skip if dismissed)
          if (!isDismissed) ...[
            const SizedBox(height: 12),
            const Text('REFUND (Opsional)', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF9CA3AF), letterSpacing: 0.5)),
            const SizedBox(height: 6),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 12),
              decoration: BoxDecoration(
                border: Border.all(color: const Color(0xFFD1D5DB)),
                borderRadius: BorderRadius.circular(10),
              ),
              child: DropdownButtonHideUnderline(
                child: DropdownButton<String>(
                  value: _refundOption,
                  isExpanded: true,
                  style: const TextStyle(fontSize: 12, color: Color(0xFF374151)),
                  items: [
                    const DropdownMenuItem(value: 'tidak_ada', child: Text('Tidak ada refund')),
                    DropdownMenuItem(value: 'penyewa', child: Text('Refund ke $penyewaName (Penyewa)')),
                    DropdownMenuItem(value: 'pemilik', child: Text('Refund ke $pemilikName (Pemilik)')),
                  ],
                  onChanged: (v) => setState(() => _refundOption = v ?? 'tidak_ada'),
                ),
              ),
            ),
            const SizedBox(height: 8),
            TextField(
              controller: _catatanController,
              maxLines: 2,
              decoration: const InputDecoration(
                hintText: 'Catatan refund (opsional)',
                border: OutlineInputBorder(),
                isDense: true,
                contentPadding: EdgeInsets.all(10),
              ),
              style: const TextStyle(fontSize: 12),
            ),
          ],

          // Submit button
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () {
                widget.onProcess(widget.report, {
                  'sanksi': _sanksiOption,
                  'refund': _refundOption,
                  'catatan_refund': _catatanController.text,
                  'tagihan_desc': _tagihanDescController.text,
                  'amount_ganti_rugi': _tagihanAmountController.text,
                });
              },
              icon: const Icon(Icons.send, size: 16),
              label: const Text('Terapkan'),
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
      ),
    );
  }

  List<DropdownMenuItem<String>> _buildSanksiItems() {
    String? lastGroup;
    final items = <DropdownMenuItem<String>>[];
    for (final opt in _sanksiOptions) {
      final group = opt['group']!;
      if (group != lastGroup) {
        if (lastGroup != null) items.add(const DropdownMenuItem(enabled: false, value: null, child: Divider()));
        items.add(DropdownMenuItem(
          enabled: false,
          value: null,
          child: Text('── $group ──', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF9CA3AF))),
        ));
        lastGroup = group;
      }
      items.add(DropdownMenuItem(value: opt['value'], child: Text(opt['label']!, style: const TextStyle(fontSize: 12))));
    }
    return items;
  }
}
