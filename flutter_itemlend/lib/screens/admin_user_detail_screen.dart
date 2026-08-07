import 'package:flutter/material.dart';
import '../services/api_service.dart';

class AdminUserDetailScreen extends StatefulWidget {
  final int userId;
  const AdminUserDetailScreen({super.key, required this.userId});

  @override
  State<AdminUserDetailScreen> createState() => _AdminUserDetailScreenState();
}

class _AdminUserDetailScreenState extends State<AdminUserDetailScreen> {
  Map<String, dynamic>? _data;
  bool _isLoading = true;
  int _selectedTab = 0;

  @override
  void initState() {
    super.initState();
    _loadDetail();
  }

  Future<void> _loadDetail() async {
    setState(() => _isLoading = true);
    final result = await ApiService.getAdminUserDetail(widget.userId);
    if (mounted) {
      setState(() {
        _data = result['success'] == true ? result['data'] : null;
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = _data?['user'];
    final items = _data?['items'] ?? [];
    final borrower = _data?['rentals_borrower'] ?? [];
    final owner = _data?['rentals_owner'] ?? [];
    final reports = _data?['reports'] ?? [];
    final revenue = _data?['revenue'] ?? {};

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
          'Detail User',
          style: TextStyle(color: Color(0xFF1A1D2E), fontWeight: FontWeight.w700, fontSize: 18),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF3D4BFF)))
          : user == null
              ? const Center(child: Text('User tidak ditemukan.'))
              : RefreshIndicator(
                  onRefresh: _loadDetail,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      _buildProfileHero(user, revenue),
                      const SizedBox(height: 16),
                      _buildInfoSection(user),
                      const SizedBox(height: 16),
                      _buildMiniStats(items, borrower, owner),
                      const SizedBox(height: 16),
                      _buildTabSection(items, borrower, owner, reports),
                    ],
                  ),
                ),
    );
  }

  Widget _buildProfileHero(Map user, Map revenue) {
    final username = user['username'] ?? '-';
    final status = user['status'] ?? 'pending';
    final role = user['role'] ?? 'user';
    final bannedUntil = user['banned_until'];

    Color statusColor;
    String statusLabel;
    switch (status) {
      case 'approved': statusColor = const Color(0xFF16A34A); statusLabel = 'Approved'; break;
      case 'cooldown': statusColor = const Color(0xFFEF4444); statusLabel = 'Cooldown'; break;
      default: statusColor = const Color(0xFFF59E0B); statusLabel = 'Pending';
    }

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 28,
            backgroundColor: const Color(0xFFEAF0FF),
            child: Text(
              username.substring(0, 1).toUpperCase(),
              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700, color: Color(0xFF3D4BFF)),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(username, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                const SizedBox(height: 4),
                Row(
                  children: [
                    _tag(role, const Color(0xFF3D4BFF)),
                    const SizedBox(width: 6),
                    _tag(statusLabel, statusColor),
                    const SizedBox(width: 6),
                    Text('ID #${user['id']}', style: const TextStyle(fontSize: 11, color: Color(0xFF9CA3AF))),
                  ],
                ),
                if (status == 'cooldown' && bannedUntil != null) ...[
                  const SizedBox(height: 6),
                  Text(
                    'Cooldown s/d $bannedUntil',
                    style: const TextStyle(fontSize: 11, color: Color(0xFFEF4444)),
                  ),
                ],
              ],
            ),
          ),
          // Revenue box
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF3D4BFF), Color(0xFF6C78FF)],
              ),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Column(
              children: [
                const Text('Revenue', style: TextStyle(fontSize: 10, color: Colors.white70)),
                const SizedBox(height: 4),
                Text(
                  'Rp ${_formatRp(revenue['total_revenue'] ?? 0)}',
                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Colors.white),
                ),
                Text(
                  '${revenue['total_transaksi'] ?? 0} transaksi',
                  style: const TextStyle(fontSize: 9, color: Colors.white60),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _tag(String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(text, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: color)),
    );
  }

  Widget _buildInfoSection(Map user) {
    final docs = <Map<String, dynamic>>[];
    if ((user['foto_profil'] ?? '').toString().isNotEmpty) {
      docs.add({'label': 'Foto Profil', 'file': user['foto_profil']});
    }
    if ((user['ktm'] ?? '').toString().isNotEmpty) {
      docs.add({'label': 'KTM', 'file': user['ktm']});
    }
    if ((user['ktp'] ?? '').toString().isNotEmpty) {
      docs.add({'label': 'KTP', 'file': user['ktp']});
    }

    return Column(
      children: [
        // Info Card
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: const Color(0xFFE5E7EB)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _infoRow(Icons.phone, 'WhatsApp', user['nomor_wa'] ?? '-'),
              const SizedBox(height: 10),
              _infoRow(Icons.location_on, 'Alamat', user['alamat'] ?? '-'),
              const SizedBox(height: 10),
              _infoRow(Icons.store, 'Deskripsi Vendor', user['deskripsi_vendor'] ?? '-'),
            ],
          ),
        ),
        if (docs.isNotEmpty) ...[
          const SizedBox(height: 10),
          // Documents
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFFE5E7EB)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Dokumen', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
                const SizedBox(height: 10),
                ...docs.map((doc) {
                  final url = 'http://10.0.2.2/itemlend/uploads/${doc['file']}';
                  return GestureDetector(
                    onTap: () => ApiService.openUrl(url),
                    child: Container(
                      margin: const EdgeInsets.only(bottom: 8),
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8F9FF),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: const Color(0xFFE0E3FF)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.description, size: 20, color: Color(0xFF3D4BFF)),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(doc['label'], style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                                const Text('Klik untuk melihat', style: TextStyle(fontSize: 10, color: Color(0xFF9CA3AF))),
                              ],
                            ),
                          ),
                          const Icon(Icons.open_in_new, size: 16, color: Color(0xFF9CA3AF)),
                        ],
                      ),
                    ),
                  );
                }),
              ],
            ),
          ),
        ],
      ],
    );
  }

  Widget _infoRow(IconData icon, String label, String value) {
    return Row(
      children: [
        Icon(icon, size: 18, color: const Color(0xFF9CA3AF)),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Color(0xFF9CA3AF))),
              Text(
                value.isEmpty ? '-' : value,
                style: TextStyle(fontSize: 13, color: value.isEmpty ? const Color(0xFF9CA3AF) : const Color(0xFF1A1D2E)),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildMiniStats(List items, List borrower, List owner) {
    return Row(
      children: [
        _miniStat('Barang', '${items.length}', const Color(0xFF3D4BFF)),
        const SizedBox(width: 10),
        _miniStat('Meminjam', '${borrower.length}', const Color(0xFF7C3AED)),
        const SizedBox(width: 10),
        _miniStat('Disewa', '${owner.length}', const Color(0xFF16A34A)),
      ],
    );
  }

  Widget _miniStat(String label, String value, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.06),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: color.withValues(alpha: 0.15)),
        ),
        child: Column(
          children: [
            Text(value, style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: color)),
            Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: color)),
          ],
        ),
      ),
    );
  }

  Widget _buildTabSection(List items, List borrower, List owner, List reports) {
    final tabs = ['Barang', 'Pinjam', 'Masuk', 'Laporan'];
    final counts = [items.length, borrower.length, owner.length, reports.length];

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Column(
        children: [
          // Tab bar
          Row(
            children: List.generate(tabs.length, (i) {
              final isActive = _selectedTab == i;
              return Expanded(
                child: GestureDetector(
                  onTap: () => setState(() => _selectedTab = i),
                  child: Container(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    decoration: BoxDecoration(
                      border: Border(
                        bottom: BorderSide(
                          color: isActive ? const Color(0xFF3D4BFF) : Colors.transparent,
                          width: 2,
                        ),
                      ),
                    ),
                    child: Text(
                      '${tabs[i]} (${counts[i]})',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: isActive ? const Color(0xFF3D4BFF) : const Color(0xFF6B7280),
                      ),
                    ),
                  ),
                ),
              );
            }),
          ),
          // Tab content
          if (_selectedTab == 0) _buildItemsTab(items),
          if (_selectedTab == 1) _buildBorrowerTab(borrower),
          if (_selectedTab == 2) _buildOwnerTab(owner),
          if (_selectedTab == 3) _buildReportsTab(reports),
        ],
      ),
    );
  }

  Widget _buildItemsTab(List items) {
    if (items.isEmpty) return const Padding(padding: EdgeInsets.all(32), child: Center(child: Text('Belum ada barang', style: TextStyle(color: Color(0xFF9CA3AF)))));
    return Column(
      children: items.map((it) {
        final status = it['status'] ?? 'pending';
        Color sc;
        switch (status) {
          case 'approved': sc = const Color(0xFF16A34A); break;
          case 'cooldown': sc = const Color(0xFFEF4444); break;
          default: sc = const Color(0xFFF59E0B);
        }
        return ListTile(
          leading: CircleAvatar(backgroundColor: const Color(0xFFEAF0FF), child: Text('${it['id']}', style: const TextStyle(fontSize: 10, color: Color(0xFF3D4BFF)))),
          title: Text(it['nama_barang'] ?? '-', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
          subtitle: Text('${it['kategori'] ?? '-'} · Rp ${_formatRp(it['harga'] ?? 0)}/hr', style: const TextStyle(fontSize: 11)),
          trailing: Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(color: sc.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(20)),
            child: Text(status, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: sc)),
          ),
        );
      }).toList(),
    );
  }

  Widget _buildBorrowerTab(List rentals) {
    if (rentals.isEmpty) return const Padding(padding: EdgeInsets.all(32), child: Center(child: Text('Belum pernah meminjam', style: TextStyle(color: Color(0xFF9CA3AF)))));
    return Column(
      children: rentals.map((r) => ListTile(
        title: Text(r['nama_barang'] ?? '-', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
        subtitle: Text('Pemilik: ${r['pemilik'] ?? '-'}', style: const TextStyle(fontSize: 11)),
        trailing: Text('Rp ${_formatRp(r['total_harga'] ?? 0)}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
      )).toList(),
    );
  }

  Widget _buildOwnerTab(List rentals) {
    if (rentals.isEmpty) return const Padding(padding: EdgeInsets.all(32), child: Center(child: Text('Belum ada yang menyewa', style: TextStyle(color: Color(0xFF9CA3AF)))));
    return Column(
      children: rentals.map((r) => ListTile(
        title: Text(r['nama_barang'] ?? '-', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
        subtitle: Text('Penyewa: ${r['peminjam'] ?? '-'}', style: const TextStyle(fontSize: 11)),
        trailing: Text('Rp ${_formatRp(r['total_harga'] ?? 0)}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
      )).toList(),
    );
  }

  Widget _buildReportsTab(List reports) {
    if (reports.isEmpty) return const Padding(padding: EdgeInsets.all(32), child: Center(child: Text('Tidak ada laporan', style: TextStyle(color: Color(0xFF9CA3AF)))));
    return Column(
      children: reports.map((rp) => ListTile(
        title: Text('#${rp['id']} — ${rp['nama_barang'] ?? '-'}', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
        subtitle: Text(rp['reason'] ?? '-', style: const TextStyle(fontSize: 11), overflow: TextOverflow.ellipsis),
        trailing: Text(rp['status'] ?? '-', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600)),
      )).toList(),
    );
  }

  String _formatRp(int amount) {
    return amount.toString().replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]}.');
  }
}
