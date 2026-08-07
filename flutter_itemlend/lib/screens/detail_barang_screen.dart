import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';
import 'sewa_screen.dart';

const Color _brandColor = Color(0xFF3D4BFF);

class DetailBarangScreen extends StatefulWidget {
  final int itemId;

  const DetailBarangScreen({super.key, required this.itemId});

  @override
  State<DetailBarangScreen> createState() => _DetailBarangScreenState();
}

class _DetailBarangScreenState extends State<DetailBarangScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  Map<String, dynamic>? _item;
  int? _loggedInUserId;

  @override
  void initState() {
    super.initState();
    _loadDetail();
  }

  Future<void> _loadDetail() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final token = await ApiService.getToken();
    final result = await ApiService.getItemDetail(widget.itemId);

    if (!mounted) return;

    setState(() {
      _isLoading = false;
      if (result['success'] == true) {
        _item = result['data'];
      } else {
        _errorMessage = result['message'] ?? 'Gagal memuat detail barang.';
      }
    });

    if (token != null && token.isNotEmpty) {
      final id = await ApiService.getUserId();
      if (mounted) setState(() => _loggedInUserId = id);
    }
  }

  String _formatHarga(int harga) {
    return 'Rp${harga.toString().replaceAllMapped(
      RegExp(r'(\d)(?=(\d{3})+(?!\d))'),
      (match) => '${match[1]}.',
    )}';
  }

  String _formatTanggal(String? isoDate) {
    if (isoDate == null) return '-';
    try {
      final date = DateTime.parse(isoDate);
      const bulan = [
        '', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
      ];
      return '${date.day} ${bulan[date.month]} ${date.year}';
    } catch (_) {
      return isoDate;
    }
  }

  Future<void> _openWhatsapp(String nomorWa, String namaOwner, String namaBarang) async {
    final cleaned = nomorWa.replaceAll(RegExp(r'[^0-9]'), '');
    final text = Uri.encodeComponent('Halo $namaOwner aku tertarik menyewa $namaBarang');
    final url = Uri.parse('https://wa.me/$cleaned?text=$text');
    if (await canLaunchUrl(url)) {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Tidak bisa membuka WhatsApp.')),
      );
    }
  }

  void _showPlaceholder(String fitur) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('$fitur belum tersedia')),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(_item?['nama_barang'] ?? 'Detail Barang')),
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
                        TextButton(onPressed: _loadDetail, child: const Text('Coba lagi')),
                      ],
                    ),
                  ),
                )
              : _buildContent(),
    );
  }

  Widget _buildContent() {
    final item = _item!;
    final owner = item['owner'] ?? {};
    final isLoggedIn = _loggedInUserId != null;
    final isOwnItem = isLoggedIn && owner['id'] == _loggedInUserId;
    final otherItems = (item['other_items'] as List?) ?? [];

    return RefreshIndicator(
      onRefresh: _loadDetail,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Stack(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(20),
                child: AspectRatio(
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
              ),
              Positioned(
                top: 12,
                left: 12,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: Colors.green,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.check_circle, color: Colors.white, size: 13),
                      SizedBox(width: 4),
                      Text('Tersedia', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

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
                if ((item['kategori'] ?? '').toString().isNotEmpty)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: const Color(0xFFEEF0FF),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.sell_outlined, size: 12, color: _brandColor),
                        const SizedBox(width: 4),
                        Text(item['kategori'], style: const TextStyle(fontSize: 11, color: _brandColor, fontWeight: FontWeight.w600)),
                      ],
                    ),
                  ),
                const SizedBox(height: 10),

                Text(
                  item['nama_barang'] ?? '-',
                  style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 14),

                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: const Color(0xFFEEF1FF),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: const Color(0xFFC7D0FF)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _formatHarga(item['harga'] ?? 0),
                        style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w800, color: _brandColor),
                      ),
                      const SizedBox(height: 2),
                      const Text('per hari · belum termasuk deposit', style: TextStyle(fontSize: 12, color: Colors.grey)),
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                Row(
                  children: const [
                    Icon(Icons.description_outlined, size: 16, color: _brandColor),
                    SizedBox(width: 6),
                    Text('Deskripsi Barang', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5)),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  (item['deskripsi'] ?? '').toString().isNotEmpty ? item['deskripsi'] : 'Tidak ada deskripsi.',
                  style: const TextStyle(fontSize: 13, color: Color(0xFF374151), height: 1.5),
                ),
                const SizedBox(height: 16),

                if ((item['lokasi'] ?? '').toString().isNotEmpty)
                  _buildMetaItem(Icons.location_on_outlined, 'Lokasi', item['lokasi']),
                _buildMetaItem(Icons.calendar_today_outlined, 'Didaftarkan', _formatTanggal(item['created_at'])),
                _buildMetaItem(Icons.verified_user_outlined, 'Status', 'Terverifikasi Admin', valueColor: Colors.green),

                const Divider(height: 28),

                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.grey[100],
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      CircleAvatar(
                        radius: 20,
                        backgroundColor: _brandColor.withValues(alpha: 0.15),
                        backgroundImage: owner['foto_profil_url'] != null ? NetworkImage(owner['foto_profil_url']) : null,
                        child: owner['foto_profil_url'] == null
                            ? Text(
                                (owner['username'] ?? '?').toString().substring(0, 1).toUpperCase(),
                                style: const TextStyle(color: _brandColor, fontWeight: FontWeight.bold),
                              )
                            : null,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(owner['username'] ?? '-', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5)),
                            const Text('Pemilik barang', style: TextStyle(fontSize: 11, color: Colors.grey)),
                          ],
                        ),
                      ),
                      if ((owner['nomor_wa'] ?? '').toString().isNotEmpty)
                        InkWell(
                          onTap: () => _openWhatsapp(owner['nomor_wa'], owner['username'] ?? '', item['nama_barang'] ?? ''),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                            decoration: BoxDecoration(color: Colors.green, borderRadius: BorderRadius.circular(8)),
                            child: const Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.chat, size: 13, color: Colors.white),
                                SizedBox(width: 4),
                                Text('WA', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w600)),
                              ],
                            ),
                          ),
                        ),
                      if (isLoggedIn && !isOwnItem) ...[
                        const SizedBox(width: 6),
                        InkWell(
                          onTap: () => _showPlaceholder('Fitur chat'),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                            decoration: BoxDecoration(color: _brandColor, borderRadius: BorderRadius.circular(8)),
                            child: const Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.message_outlined, size: 13, color: Colors.white),
                                SizedBox(width: 4),
                                Text('Chat', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w600)),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                _buildMainActionButton(isLoggedIn, isOwnItem, item),

                if (isLoggedIn && !isOwnItem) ...[
                  const SizedBox(height: 10),
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      onPressed: () => _showPlaceholder('Fitur laporkan barang'),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: Colors.red,
                        side: const BorderSide(color: Color(0xFFFECACA)),
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      icon: const Icon(Icons.flag_outlined, size: 16),
                      label: const Text('Laporkan Barang Ini', style: TextStyle(fontSize: 13)),
                    ),
                  ),
                ],

                const SizedBox(height: 12),
                const Center(
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.shield_outlined, size: 14, color: Colors.green),
                      SizedBox(width: 6),
                      Text('Transaksi aman & terlindungi ItemLend', style: TextStyle(fontSize: 11, color: Colors.grey)),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          if (otherItems.isNotEmpty) ...[
            const SizedBox(height: 16),
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
                    children: [
                      const Icon(Icons.inventory_2_outlined, size: 18, color: _brandColor),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Barang Lain dari ${owner['username'] ?? ''}',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    height: 130,
                    child: ListView.separated(
                      scrollDirection: Axis.horizontal,
                      itemCount: otherItems.length,
                      separatorBuilder: (_, _) => const SizedBox(width: 10),
                      itemBuilder: (context, index) {
                        final o = otherItems[index];
                        return InkWell(
                          onTap: () {
                            Navigator.pushReplacement(
                              context,
                              MaterialPageRoute(builder: (_) => DetailBarangScreen(itemId: o['id'])),
                            );
                          },
                          borderRadius: BorderRadius.circular(12),
                          child: Container(
                            width: 110,
                            decoration: BoxDecoration(
                              border: Border.all(color: Colors.grey[200]!),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            clipBehavior: Clip.antiAlias,
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                SizedBox(
                                  height: 70,
                                  width: double.infinity,
                                  child: o['gambar_url'] != null
                                      ? Image.network(o['gambar_url'], fit: BoxFit.cover)
                                      : Container(color: Colors.grey[200], child: const Icon(Icons.image_outlined, color: Colors.grey, size: 20)),
                                ),
                                Padding(
                                  padding: const EdgeInsets.all(6),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        o['nama_barang'] ?? '-',
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600),
                                      ),
                                      Text(
                                        '${_formatHarga(o['harga'] ?? 0)}/hr',
                                        style: const TextStyle(fontSize: 10, color: _brandColor, fontWeight: FontWeight.bold),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  Widget _buildMetaItem(IconData icon, String label, String value, {Color? valueColor}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(color: Colors.grey[100], borderRadius: BorderRadius.circular(8)),
            child: Icon(icon, size: 16, color: Colors.grey),
          ),
          const SizedBox(width: 10),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: const TextStyle(fontSize: 11, color: Colors.grey)),
              Text(value, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: valueColor)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildMainActionButton(bool isLoggedIn, bool isOwnItem, Map<String, dynamic> item) {
    if (!isLoggedIn) {
      return SizedBox(
        width: double.infinity,
        child: ElevatedButton.icon(
          onPressed: () => _showPlaceholder('Silakan login terlebih dahulu untuk menyewa'),
          style: ElevatedButton.styleFrom(
            backgroundColor: _brandColor,
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(vertical: 14),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          ),
          icon: const Icon(Icons.lock_outline, size: 18),
          label: const Text('Login untuk Menyewa'),
        ),
      );
    }

    if (isOwnItem) {
      return Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(vertical: 14),
            decoration: BoxDecoration(
              color: Colors.grey[100],
              border: Border.all(color: Colors.grey[300]!),
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.verified_user_outlined, size: 18, color: Colors.grey),
                SizedBox(width: 8),
                Text('Ini Barang Milikmu', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.w600)),
              ],
            ),
          ),
          const SizedBox(height: 10),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: () => _showPlaceholder('Fitur edit barang'),
              style: OutlinedButton.styleFrom(
                foregroundColor: _brandColor,
                side: const BorderSide(color: _brandColor),
                padding: const EdgeInsets.symmetric(vertical: 12),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              icon: const Icon(Icons.edit_outlined, size: 16),
              label: const Text('Edit Barang', style: TextStyle(fontSize: 13)),
            ),
          ),
        ],
      );
    }

    return SizedBox(
      width: double.infinity,
      child: ElevatedButton.icon(
        onPressed: () async {
          final result = await Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => SewaScreen(itemId: item['id'])),
          );
          if (result == true && mounted) {
            _loadDetail(); // Refresh data setelah pembayaran dikirim
          }
        },
        style: ElevatedButton.styleFrom(
          backgroundColor: _brandColor,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        ),
        icon: const Icon(Icons.shopping_cart_outlined, size: 18),
        label: const Text('Sewa Sekarang'),
      ),
    );
  }
}