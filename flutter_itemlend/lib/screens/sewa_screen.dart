import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'proses_pembayaran_screen.dart';

const Color _brandColor = Color(0xFF3D4BFF);

class SewaScreen extends StatefulWidget {
  final int itemId;

  const SewaScreen({super.key, required this.itemId});

  @override
  State<SewaScreen> createState() => _SewaScreenState();
}

class _SewaScreenState extends State<SewaScreen> {
  bool _isLoading = true;
  bool _isSubmitting = false;
  String? _errorMessage;
  Map<String, dynamic>? _item;

  DateTime? _startDate;
  DateTime? _endDate;

  @override
  void initState() {
    super.initState();
    _loadItem();
  }

  Future<void> _loadItem() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final result = await ApiService.getItemDetail(widget.itemId);

    if (!mounted) return;

    setState(() {
      _isLoading = false;
      if (result['success'] == true) {
        _item = result['data'];
      } else {
        _errorMessage = result['message'] ?? 'Gagal memuat barang.';
      }
    });
  }

  String _formatHarga(int harga) {
    return 'Rp${harga.toString().replaceAllMapped(
      RegExp(r'(\d)(?=(\d{3})+(?!\d))'),
      (match) => '${match[1]}.',
    )}';
  }

  String _formatTanggalPendek(DateTime? date) {
    if (date == null) return 'mm/dd/yyyy';
    return '${date.month.toString().padLeft(2, '0')}/${date.day.toString().padLeft(2, '0')}/${date.year}';
  }

  int get _durasiHari {
    if (_startDate == null || _endDate == null) return 0;
    final diff = _endDate!.difference(_startDate!).inDays;
    return diff > 0 ? diff : 0;
  }

  int get _totalHarga {
    final hargaPerHari = _item?['harga'] ?? 0;
    return _durasiHari * (hargaPerHari as int);
  }

  Future<void> _pickStartDate() async {
    final today = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _startDate ?? today,
      firstDate: today,
      lastDate: DateTime(today.year + 2),
    );
    if (picked != null) {
      setState(() {
        _startDate = picked;
        // Kalau tanggal selesai sudah dipilih tapi jadi tidak valid, reset
        if (_endDate != null && !_endDate!.isAfter(_startDate!)) {
          _endDate = null;
        }
      });
    }
  }

  Future<void> _pickEndDate() async {
    if (_startDate == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Pilih tanggal mulai dulu.')),
      );
      return;
    }
    final minEnd = _startDate!.add(const Duration(days: 1));
    final picked = await showDatePicker(
      context: context,
      initialDate: _endDate ?? minEnd,
      firstDate: minEnd,
      lastDate: DateTime(minEnd.year + 2),
    );
    if (picked != null) {
      setState(() => _endDate = picked);
    }
  }

  Future<void> _handleSubmit() async {
    if (_startDate == null || _endDate == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Lengkapi tanggal mulai dan selesai.')),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    final startStr = _startDate!.toIso8601String().split('T')[0];
    final endStr = _endDate!.toIso8601String().split('T')[0];

    final result = await ApiService.sewaBarang(
      itemId: widget.itemId,
      start: startStr,
      end: endStr,
    );

    if (!mounted) return;

    setState(() => _isSubmitting = false);

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(result['message'] ?? 'Terjadi kesalahan.')),
    );

    if (result['success'] == true) {
      // Navigasi ke halaman pembayaran
      final data = result['data'] ?? {};
      if (mounted) {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (_) => ProsesPembayaranScreen(
              rentalId: data['rental_id'] ?? 0,
              itemId: widget.itemId,
              namaBarang: _item?['nama_barang'],
              gambarUrl: _item?['gambar_url'],
              durasiHari: data['durasi_hari'] ?? _durasiHari,
              totalHarga: data['total_harga'] ?? _totalHarga,
            ),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Sewa')),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _errorMessage != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.error_outline, size: 48, color: Colors.grey[400]),
                        const SizedBox(height: 12),
                        Text(_errorMessage!, textAlign: TextAlign.center, style: const TextStyle(color: Colors.grey)),
                        const SizedBox(height: 12),
                        TextButton(onPressed: _loadItem, child: const Text('Coba lagi')),
                      ],
                    ),
                  ),
                )
              : _buildContent(),
    );
  }

  Widget _buildContent() {
    final item = _item!;
    final harga = item['harga'] ?? 0;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // --- PREVIEW BARANG ---
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: Colors.grey[200]!),
          ),
          clipBehavior: Clip.antiAlias,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              AspectRatio(
                aspectRatio: 4 / 3,
                child: item['gambar_url'] != null
                    ? Image.network(
                        item['gambar_url'],
                        fit: BoxFit.cover,
                        errorBuilder: (_, _, _) => Container(
                          color: Colors.grey[200],
                          child: const Icon(Icons.image_not_supported_outlined, color: Colors.grey, size: 40),
                        ),
                      )
                    : Container(
                        color: Colors.grey[200],
                        child: const Icon(Icons.image_outlined, color: Colors.grey, size: 40),
                      ),
              ),
              Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item['nama_barang'] ?? '-',
                      style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800),
                    ),
                    const SizedBox(height: 4),
                    if ((item['deskripsi'] ?? '').toString().isNotEmpty)
                      Text(
                        item['deskripsi'],
                        maxLines: 3,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontSize: 12.5, color: Colors.grey, height: 1.5),
                      ),
                    const SizedBox(height: 10),
                    if ((item['lokasi'] ?? '').toString().isNotEmpty)
                      _buildPreviewMetaRow(Icons.location_on_outlined, item['lokasi']),
                    _buildPreviewMetaRow(
                      Icons.person_outline,
                      'Pemilik: ${item['owner']?['username'] ?? '-'}',
                    ),
                    if ((item['kategori'] ?? '').toString().isNotEmpty)
                      _buildPreviewMetaRow(Icons.sell_outlined, item['kategori']),
                    const SizedBox(height: 12),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFFEEF0FF), Color(0xFFE4E8FF)],
                        ),
                        border: Border.all(color: const Color(0xFFC7D0FF)),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.baseline,
                        textBaseline: TextBaseline.alphabetic,
                        children: [
                          Text(
                            _formatHarga(harga),
                            style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: _brandColor),
                          ),
                          const SizedBox(width: 6),
                          const Text('/ hari', style: TextStyle(fontSize: 12.5, color: Colors.grey)),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),

        // --- FORM SEWA ---
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: Colors.grey[200]!),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: const [
                  Icon(Icons.event_available_outlined, size: 20, color: _brandColor),
                  SizedBox(width: 8),
                  Text('Atur Jadwal Sewa', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
                ],
              ),
              const SizedBox(height: 2),
              const Text('Pilih tanggal mulai dan selesai sewamu', style: TextStyle(fontSize: 12.5, color: Colors.grey)),
              const SizedBox(height: 20),

              _buildLabel('Tanggal Mulai'),
              _buildDateField(_formatTanggalPendek(_startDate), _pickStartDate),
              const SizedBox(height: 14),

              _buildLabel('Tanggal Selesai'),
              _buildDateField(_formatTanggalPendek(_endDate), _pickEndDate),

              if (_durasiHari > 0) ...[
                const SizedBox(height: 16),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8F9FB),
                    border: Border.all(color: Colors.grey[200]!),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Column(
                    children: [
                      _buildSummaryRow('Harga per hari', _formatHarga(harga)),
                      _buildSummaryRow('Durasi', '$_durasiHari hari'),
                      const Divider(height: 16),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('Total Estimasi', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                          Text(
                            _formatHarga(_totalHarga),
                            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: _brandColor),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],

              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: _isSubmitting ? null : _handleSubmit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _brandColor,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  icon: _isSubmitting
                      ? const SizedBox(
                          height: 16,
                          width: 16,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Icon(Icons.shopping_cart_outlined, size: 18),
                  label: Text(_isSubmitting ? 'Memproses...' : 'Konfirmasi Sewa'),
                ),
              ),
              const SizedBox(height: 10),
              const Center(
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.shield_outlined, size: 13, color: Colors.green),
                    SizedBox(width: 5),
                    Text('Transaksi aman & terlindungi ItemLend', style: TextStyle(fontSize: 11, color: Colors.grey)),
                  ],
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildLabel(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Text(text, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: Color(0xFF374151))),
    );
  }

  Widget _buildDateField(String label, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
        decoration: BoxDecoration(
          border: Border.all(color: Colors.grey[300]!),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label, style: TextStyle(fontSize: 14, color: label.contains('/') ? Colors.black87 : Colors.grey[400])),
            const Icon(Icons.calendar_today_outlined, size: 16, color: Colors.grey),
          ],
        ),
      ),
    );
  }

  Widget _buildPreviewMetaRow(IconData icon, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        children: [
          Icon(icon, size: 14, color: Colors.grey),
          const SizedBox(width: 6),
          Expanded(child: Text(text, style: const TextStyle(fontSize: 12.5, color: Color(0xFF374151)))),
        ],
      ),
    );
  }

  Widget _buildSummaryRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 12.5, color: Colors.grey)),
          Text(value, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}