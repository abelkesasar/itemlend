import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import '../services/api_service.dart';

const Color _brandColor = Color(0xFF3D4BFF);

/// Screen untuk proses pembayaran setelah user berhasil membuat rental.
class ProsesPembayaranScreen extends StatefulWidget {
  final int rentalId;
  final int itemId;
  final String? namaBarang;
  final String? gambarUrl;
  final int durasiHari;
  final int totalHarga;

  const ProsesPembayaranScreen({
    super.key,
    required this.rentalId,
    required this.itemId,
    this.namaBarang,
    this.gambarUrl,
    required this.durasiHari,
    required this.totalHarga,
  });

  @override
  State<ProsesPembayaranScreen> createState() => _ProsesPembayaranScreenState();
}

class _ProsesPembayaranScreenState extends State<ProsesPembayaranScreen> {
  String? _selectedMetode;
  File? _buktiFile;
  bool _isSubmitting = false;
  bool _isSuccess = false;
  final ImagePicker _picker = ImagePicker();

  // Data metode pembayaran — ganti no rekening / HP sesuai milikmu
  static const List<Map<String, String>> _metodeList = [
    {
      'key': 'qris',
      'label': 'QRIS',
      'info': 'Scan QRIS di bawah menggunakan aplikasi apapun',
      'detail': '',
    },
    {
      'key': 'mandiri',
      'label': 'Transfer Mandiri',
      'info': 'Transfer ke rekening Mandiri berikut',
      'detail': 'No. Rek: 1234567890\na.n. Nama Admin',
    },
    {
      'key': 'bri',
      'label': 'Transfer BRI',
      'info': 'Transfer ke rekening BRI berikut',
      'detail': 'No. Rek: 0987654321\na.n. Nama Admin',
    },
    {
      'key': 'bca',
      'label': 'Transfer BCA',
      'info': 'Transfer ke rekening BCA berikut',
      'detail': 'No. Rek: 1122334455\na.n. Nama Admin',
    },
    {
      'key': 'gopay',
      'label': 'GoPay',
      'info': 'Transfer ke GoPay berikut',
      'detail': 'No. HP: 08123456789\na.n. Nama Admin',
    },
    {
      'key': 'shopee',
      'label': 'ShopeePay',
      'info': 'Transfer ke ShopeePay berikut',
      'detail': 'No. HP: 08123456789\na.n. Nama Admin',
    },
    {
      'key': 'dana',
      'label': 'DANA',
      'info': 'Transfer ke DANA berikut',
      'detail': 'No. HP: 08123456789\na.n. Nama Admin',
    },
  ];

  static const Map<String, IconData> _metodeIcons = {
    'qris': Icons.qr_code_2,
    'mandiri': Icons.account_balance,
    'bri': Icons.account_balance,
    'bca': Icons.account_balance,
    'gopay': Icons.payment,
    'shopee': Icons.shopping_bag,
    'dana': Icons.account_balance_wallet,
  };

  static const Map<String, Color> _metodeIconBg = {
    'qris': Color(0xFF1a1d2e),
    'mandiri': Color(0xFF003d7a),
    'bri': Color(0xFF005baa),
    'bca': Color(0xFF0066ae),
    'gopay': Color(0xFF00AED6),
    'shopee': Color(0xFFEE4D2D),
    'dana': Color(0xFF108EE9),
  };

  String get _qrisImageUrl =>
      '${baseUrl.replaceAll('/api', '')}/uploads/qris.png';

  String _formatHarga(int harga) {
    return 'Rp${harga.toString().replaceAllMapped(
      RegExp(r'(\d)(?=(\d{3})+(?!\d))'),
      (match) => '${match[1]}.',
    )}';
  }

  Map<String, String>? get _selectedMetodeData {
    if (_selectedMetode == null) return null;
    return _metodeList.firstWhere((m) => m['key'] == _selectedMetode);
  }

  Future<void> _pickBukti(ImageSource source) async {
    final picked = await _picker.pickImage(
      source: source,
      maxWidth: 1200,
      maxHeight: 1200,
      imageQuality: 85,
    );
    if (picked != null) {
      final file = File(picked.path);
      final sizeInBytes = await file.length();
      if (sizeInBytes > 5 * 1024 * 1024) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Ukuran file maksimal 5MB.')),
          );
        }
        return;
      }
      setState(() => _buktiFile = file);
    }
  }

  void _showImageSourcePicker() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey[300],
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(height: 16),
              const Text('Upload Bukti Pembayaran',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              const SizedBox(height: 4),
              const Text('Format: JPG, JPEG, PNG, WebP (maks 5MB)',
                  style: TextStyle(fontSize: 12, color: Colors.grey)),
              const SizedBox(height: 16),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: _brandColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.camera_alt_outlined,
                      color: _brandColor),
                ),
                title: const Text('Ambil Foto',
                    style: TextStyle(fontWeight: FontWeight.w600)),
                subtitle: const Text('Gunakan kamera',
                    style: TextStyle(fontSize: 12)),
                onTap: () {
                  Navigator.pop(ctx);
                  _pickBukti(ImageSource.camera);
                },
              ),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: _brandColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.photo_library_outlined,
                      color: _brandColor),
                ),
                title: const Text('Pilih dari Galeri',
                    style: TextStyle(fontWeight: FontWeight.w600)),
                subtitle: const Text('Pilih dari galeri foto',
                    style: TextStyle(fontSize: 12)),
                onTap: () {
                  Navigator.pop(ctx);
                  _pickBukti(ImageSource.gallery);
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _handleSubmit() async {
    if (_selectedMetode == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('Pilih metode pembayaran terlebih dahulu.')),
      );
      return;
    }
    if (_buktiFile == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('Upload bukti pembayaran terlebih dahulu.')),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    final result = await ApiService.prosesPembayaran(
      rentalId: widget.rentalId,
      metode: _selectedMetode!,
      buktiFile: _buktiFile!,
    );

    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (result['success'] == true) {
      setState(() => _isSuccess = true);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
            content: Text(
                result['message'] ?? 'Gagal mengirim bukti pembayaran.')),
      );
    }
  }

  // ═══════════════════════════════════════════════════════
  // BUILD
  // ═══════════════════════════════════════════════════════
  @override
  Widget build(BuildContext context) {
    if (_isSuccess) return _buildSuccessView();

    return Scaffold(
      appBar: AppBar(title: const Text('Pembayaran')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildRentalSummary(),
          const SizedBox(height: 16),
          _buildMetodeSection(),
          const SizedBox(height: 16),
          _buildBuktiSection(),
          const SizedBox(height: 16),
          // Catatan admin
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFFFFF7E6),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFFFED7AA)),
            ),
            child: const Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(Icons.info_outline, color: Color(0xFF92400E), size: 18),
                SizedBox(width: 10),
                Expanded(
                  child: Text(
                    'Pembayaran masuk ke admin ItemLend terlebih dahulu sebelum diteruskan ke pemilik barang.',
                    style: TextStyle(fontSize: 12.5, color: Color(0xFF92400E)),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          // Tombol kirim
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _isSubmitting ? null : _handleSubmit,
              style: ElevatedButton.styleFrom(
                backgroundColor: _brandColor,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
              icon: _isSubmitting
                  ? const SizedBox(
                      height: 16,
                      width: 16,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white))
                  : const Icon(Icons.send_outlined, size: 18),
              label: Text(
                  _isSubmitting ? 'Mengirim...' : 'Kirim Bukti Pembayaran'),
            ),
          ),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  // ═══════════════════════════════════════════════════════
  // RINGKASAN RENTAL
  // ═══════════════════════════════════════════════════════
  Widget _buildRentalSummary() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.grey[200]!),
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
            decoration: BoxDecoration(
              color: const Color(0xFFF8F9FB),
              border: Border(bottom: BorderSide(color: Colors.grey[200]!)),
            ),
            child: const Row(
              children: [
                Icon(Icons.receipt_outlined, size: 16, color: _brandColor),
                SizedBox(width: 7),
                Text('Ringkasan Pesanan',
                    style:
                        TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
              ],
            ),
          ),
          if (widget.gambarUrl != null)
            AspectRatio(
              aspectRatio: 16 / 7,
              child: Image.network(
                widget.gambarUrl!,
                fit: BoxFit.cover,
                errorBuilder: (_, _, _) => Container(
                  color: Colors.grey[200],
                  child: const Icon(Icons.image_not_supported_outlined,
                      color: Colors.grey, size: 32),
                ),
              ),
            ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(widget.namaBarang ?? 'Barang',
                    style: const TextStyle(
                        fontSize: 16, fontWeight: FontWeight.w800)),
                const SizedBox(height: 14),
                _buildSummaryRow('Durasi sewa', '${widget.durasiHari} hari'),
                const Divider(height: 16),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Total Pembayaran',
                        style: TextStyle(
                            fontSize: 13, fontWeight: FontWeight.w600)),
                    Text(
                      _formatHarga(widget.totalHarga),
                      style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w800,
                          color: _brandColor),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ═══════════════════════════════════════════════════════
  // PILIH METODE + INSTRUKSI PEMBAYARAN
  // ═══════════════════════════════════════════════════════
  Widget _buildMetodeSection() {
    return Container(
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
          const Row(
            children: [
              Icon(Icons.credit_card_outlined, size: 20, color: _brandColor),
              SizedBox(width: 8),
              Text('Pilih Metode Pembayaran',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
            ],
          ),
          const SizedBox(height: 4),
          const Text('Pilih salah satu metode pembayaran',
              style: TextStyle(fontSize: 12.5, color: Colors.grey)),
          const SizedBox(height: 16),

          // Grid metode
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              mainAxisSpacing: 10,
              crossAxisSpacing: 10,
              childAspectRatio: 2.8,
            ),
            itemCount: _metodeList.length,
            itemBuilder: (context, index) {
              final metode = _metodeList[index];
              final isSelected = _selectedMetode == metode['key'];
              final iconBg =
                  _metodeIconBg[metode['key']] ?? Colors.grey;

              return InkWell(
                onTap: () =>
                    setState(() => _selectedMetode = metode['key']),
                borderRadius: BorderRadius.circular(11),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 200),
                  padding: const EdgeInsets.symmetric(
                      horizontal: 14, vertical: 11),
                  decoration: BoxDecoration(
                    color: isSelected
                        ? const Color(0xFFEEF0FF)
                        : Colors.white,
                    borderRadius: BorderRadius.circular(11),
                    border: Border.all(
                      color: isSelected ? _brandColor : Colors.grey[200]!,
                      width: isSelected ? 2 : 1.5,
                    ),
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 34,
                        height: 34,
                        decoration: BoxDecoration(
                          color: isSelected
                              ? iconBg.withValues(alpha: 0.15)
                              : const Color(0xFFF4F5F7),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Icon(
                          _metodeIcons[metode['key']] ?? Icons.payment,
                          size: 18,
                          color: iconBg,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          metode['label']!,
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: isSelected
                                ? _brandColor
                                : const Color(0xFF374151),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),

          // INSTRUKSI PEMBAYARAN
          if (_selectedMetode != null) ...[
            const SizedBox(height: 14),
            _buildInstruksiPembayaran(_selectedMetodeData!),
          ],
        ],
      ),
    );
  }

  // ═══════════════════════════════════════════════════════
  // BOX INSTRUKSI PEMBAYARAN
  // ═══════════════════════════════════════════════════════
  Widget _buildInstruksiPembayaran(Map<String, String> metode) {
    final isQRIS = metode['key'] == 'qris';

    return Container(
      key: ValueKey('instruksi_${metode['key']}'),
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFFF8F9FB),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey[200]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'INSTRUKSI PEMBAYARAN',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: Color(0xFF6B7280),
              letterSpacing: 0.05,
            ),
          ),
          const SizedBox(height: 10),
          Text(
            metode['info'] ?? '',
            style: const TextStyle(fontSize: 13.5, color: Color(0xFF374151)),
          ),
          const SizedBox(height: 10),
          if (isQRIS)
            _buildQRSection()
          else
            _buildAccountDetail(metode['detail'] ?? ''),
          const SizedBox(height: 10),
          // Nominal transfer + tombol salin
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: const Color(0xFFC7D0FF)),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Nominal Transfer',
                        style: TextStyle(
                            fontSize: 12, color: Color(0xFF6B7280))),
                    const SizedBox(height: 2),
                    Text(
                      _formatHarga(widget.totalHarga),
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                        color: _brandColor,
                      ),
                    ),
                  ],
                ),
                InkWell(
                  onTap: _copyNominal,
                  borderRadius: BorderRadius.circular(7),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: const Color(0xFFEEF0FF),
                      borderRadius: BorderRadius.circular(7),
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.copy_rounded,
                            size: 14, color: _brandColor),
                        SizedBox(width: 5),
                        Text('Salin',
                            style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: _brandColor)),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildQRSection() {
    return Column(
      children: [
        Container(
          width: 180,
          height: 180,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.grey[200]!),
          ),
          clipBehavior: Clip.antiAlias,
          child: Image.network(
            _qrisImageUrl,
            fit: BoxFit.cover,
            errorBuilder: (_, _, _) => Container(
              color: const Color(0xFFF0F1F5),
              child: const Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.qr_code_2, size: 64, color: Color(0xFFD1D5DB)),
                  SizedBox(height: 8),
                  Text('QRIS',
                      style: TextStyle(
                          fontSize: 12, color: Color(0xFF9CA3AF))),
                ],
              ),
            ),
          ),
        ),
        const SizedBox(height: 8),
        const Text(
          'Scan menggunakan aplikasi apapun',
          style: TextStyle(fontSize: 12, color: Color(0xFF6B7280)),
        ),
      ],
    );
  }

  Widget _buildAccountDetail(String detail) {
    final lines = detail.split('\n');
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.grey[200]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: lines.map((line) {
          if (line.contains(': ')) {
            final parts = line.split(': ');
            final label = parts[0];
            final value = parts.sublist(1).join(': ');
            return Padding(
              padding: const EdgeInsets.only(bottom: 4),
              child: RichText(
                text: TextSpan(
                  style: const TextStyle(
                      fontSize: 14,
                      color: Color(0xFF1A1D2E),
                      height: 1.6),
                  children: [
                    TextSpan(text: '$label: '),
                    TextSpan(
                      text: value,
                      style:
                          const TextStyle(fontWeight: FontWeight.w700),
                    ),
                  ],
                ),
              ),
            );
          }
          return Padding(
            padding: const EdgeInsets.only(bottom: 4),
            child: Text(line,
                style: const TextStyle(
                    fontSize: 14,
                    color: Color(0xFF1A1D2E),
                    height: 1.6)),
          );
        }).toList(),
      ),
    );
  }

  void _copyNominal() {
    Clipboard.setData(ClipboardData(text: widget.totalHarga.toString()));
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Nominal berhasil disalin!'),
        duration: Duration(seconds: 2),
      ),
    );
  }

  // ═══════════════════════════════════════════════════════
  // UPLOAD BUKTI
  // ═══════════════════════════════════════════════════════
  Widget _buildBuktiSection() {
    return Container(
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
          const Row(
            children: [
              Icon(Icons.upload_outlined, size: 20, color: _brandColor),
              SizedBox(width: 8),
              Text('Upload Bukti Pembayaran',
                  style:
                      TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
            ],
          ),
          const SizedBox(height: 16),
          if (_buktiFile != null) ...[
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: Stack(
                children: [
                  Image.file(_buktiFile!,
                      width: double.infinity,
                      height: 200,
                      fit: BoxFit.cover),
                  Positioned(
                    top: 8,
                    right: 8,
                    child: GestureDetector(
                      onTap: () => setState(() => _buktiFile = null),
                      child: Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.6),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.close,
                            color: Colors.white, size: 16),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: _showImageSourcePicker,
                style: OutlinedButton.styleFrom(
                  foregroundColor: _brandColor,
                  side: const BorderSide(color: _brandColor),
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10)),
                ),
                icon:
                    const Icon(Icons.camera_alt_outlined, size: 16),
                label: const Text('Ganti Foto',
                    style: TextStyle(fontSize: 13)),
              ),
            ),
          ] else ...[
            InkWell(
              onTap: _showImageSourcePicker,
              borderRadius: BorderRadius.circular(12),
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 36),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8F9FB),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                      color: Colors.grey[300]!, width: 1.5),
                ),
                child: Column(
                  children: [
                    Icon(Icons.cloud_upload_outlined,
                        size: 36, color: Colors.grey[400]),
                    const SizedBox(height: 8),
                    Text('Klik atau drag foto bukti transfer',
                        style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: Colors.grey[600])),
                    const SizedBox(height: 4),
                    Text('JPG, PNG · Maks 5MB',
                        style: TextStyle(
                            fontSize: 12, color: Colors.grey[400])),
                  ],
                ),
              ),
            ),
          ],
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
          Text(label,
              style:
                  const TextStyle(fontSize: 12.5, color: Colors.grey)),
          Text(value,
              style: const TextStyle(
                  fontSize: 12.5, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }

  // ═══════════════════════════════════════════════════════
  // TAMPILAN SUKSES
  // ═══════════════════════════════════════════════════════
  Widget _buildSuccessView() {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Pembayaran'),
        automaticallyImplyLeading: false,
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: Colors.green.withValues(alpha: 0.1),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.check_circle_outline,
                    color: Colors.green, size: 64),
              ),
              const SizedBox(height: 24),
              const Text('Bukti Pembayaran Terkirim!',
                  style:
                      TextStyle(fontSize: 22, fontWeight: FontWeight.w800),
                  textAlign: TextAlign.center),
              const SizedBox(height: 12),
              const Text(
                'Bukti pembayaran kamu sudah berhasil dikirim.\n'
                'Admin akan segera memverifikasi pembayaranmu.',
                textAlign: TextAlign.center,
                style: TextStyle(
                    fontSize: 14, color: Colors.grey, height: 1.5),
              ),
              const SizedBox(height: 32),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFFEEF0FF),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFC7D0FF)),
                ),
                child: const Column(
                  children: [
                    Icon(Icons.info_outline, color: _brandColor, size: 20),
                    SizedBox(height: 8),
                    Text(
                      'Status pembayaran: Menunggu Konfirmasi',
                      style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: _brandColor),
                    ),
                    SizedBox(height: 4),
                    Text(
                      'Total akan ditampilkan di sini',
                      style: TextStyle(fontSize: 12, color: Colors.grey),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 32),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () => Navigator.of(context)
                      .popUntil((route) => route.isFirst),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _brandColor,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                  ),
                  icon:
                      const Icon(Icons.home_outlined, size: 18),
                  label: const Text('Kembali ke Beranda'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}