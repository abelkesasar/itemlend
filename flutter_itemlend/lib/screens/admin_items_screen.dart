import 'package:flutter/material.dart';
import 'dart:convert';
import '../services/api_service.dart';
import 'admin_approval_screen.dart';

class AdminItemsScreen extends StatefulWidget {
  const AdminItemsScreen({super.key});

  @override
  State<AdminItemsScreen> createState() => _AdminItemsScreenState();
}

class _AdminItemsScreenState extends State<AdminItemsScreen> {
  List<dynamic> _items = [];
  Map<String, dynamic> _stats = {};
  bool _isLoading = true;
  String _statusTab = 'approved';
  String _search = '';
  String _sort = 'terbaru';

  @override
  void initState() {
    super.initState();
    _loadItems();
  }

  Future<void> _loadItems() async {
    setState(() => _isLoading = true);
    final result = await ApiService.getAdminItems(status: _statusTab, search: _search, sort: _sort);
    if (mounted) {
      setState(() {
        if (result['success'] == true) {
          _items = result['data']['items'] ?? [];
          _stats = result['data']['stats'] ?? {};
        }
        _isLoading = false;
      });
    }
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
          'Kelola Items',
          style: TextStyle(color: Color(0xFF1A1D2E), fontWeight: FontWeight.w700, fontSize: 18),
        ),
        actions: [
          if ((_stats['pending'] ?? 0) > 0)
            GestureDetector(
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const AdminApprovalScreen())),
              child: Container(
                margin: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                padding: const EdgeInsets.symmetric(horizontal: 12),
                decoration: BoxDecoration(
                  color: const Color(0xFFFFF7E6),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: const Color(0xFFFDE68A)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.pending_actions, size: 16, color: Color(0xFFF59E0B)),
                    const SizedBox(width: 6),
                    Text(
                      'Approval (${_stats['pending']})',
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFFF59E0B)),
                    ),
                  ],
                ),
              ),
            ),
          PopupMenuButton<String>(
            onSelected: (v) {
              setState(() => _sort = v);
              _loadItems();
            },
            itemBuilder: (ctx) => [
              const PopupMenuItem(value: 'terbaru', child: Text('Terbaru')),
              const PopupMenuItem(value: 'az', child: Text('A-Z')),
              const PopupMenuItem(value: 'termurah', child: Text('Harga Terendah')),
              const PopupMenuItem(value: 'termahal', child: Text('Harga Tertinggi')),
            ],
            icon: const Icon(Icons.sort, color: Color(0xFF6B7280)),
          ),
        ],
      ),
      body: Column(
        children: [
          // Tabs
          Container(
            color: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            child: Row(
              children: [
                _tabButton('Disetujui', 'approved', _stats['approved'] ?? 0),
                const SizedBox(width: 8),
                _tabButton('Cooldown', 'cooldown', _stats['cooldown'] ?? 0),
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
                _loadItems();
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
          // Items grid
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: Color(0xFF3D4BFF)))
                : RefreshIndicator(
                    onRefresh: _loadItems,
                    child: _items.isEmpty
                        ? const Center(child: Text('Tidak ada barang', style: TextStyle(color: Color(0xFF9CA3AF))))
                        : GridView.builder(
                            padding: const EdgeInsets.all(16),
                            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 2,
                              mainAxisSpacing: 12,
                              crossAxisSpacing: 12,
                              childAspectRatio: 0.85,
                            ),
                            itemCount: _items.length,
                            itemBuilder: (ctx, i) => _itemCard(_items[i]),
                          ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _tabButton(String label, String tab, int count) {
    final isActive = _statusTab == tab;
    return Expanded(
      child: GestureDetector(
        onTap: () {
          setState(() => _statusTab = tab);
          _loadItems();
        },
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color: isActive ? const Color(0xFF3D4BFF) : Colors.transparent,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(
              color: isActive ? const Color(0xFF3D4BFF) : const Color(0xFFE5E7EB),
            ),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                label,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: isActive ? Colors.white : const Color(0xFF6B7280),
                ),
              ),
              const SizedBox(width: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: isActive ? Colors.white.withValues(alpha: 0.25) : const Color(0xFFF3F4F6),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '$count',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: isActive ? Colors.white : const Color(0xFF6B7280),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _itemCard(Map item) {
    final nama = item['nama_barang'] ?? '-';
    final harga = item['harga'] ?? 0;
    final owner = item['owner_name'] ?? '-';
    final gambar = item['gambar'];

    String? imageUrl;
    if (gambar != null && gambar.toString().isNotEmpty) {
      try {
        List<String> files = [];
        if (gambar is List) {
          files = List<String>.from(gambar);
        } else {
          final decoded = jsonDecode(gambar.toString());
          if (decoded is List) {
            files = List<String>.from(decoded);
          } else if (decoded is String) {
            files = [decoded];
          }
        }
        if (files.isNotEmpty) {
          imageUrl = 'http://10.0.2.2/itemlend/uploads/${files[0]}';
        }
      } catch (_) {
        // Fallback: treat as plain filename
        imageUrl = 'http://10.0.2.2/itemlend/uploads/$gambar';
      }
    }

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Thumbnail
          Expanded(
            flex: 3,
            child: Container(
              width: double.infinity,
              decoration: const BoxDecoration(
                color: Color(0xFFF0F1F5),
                borderRadius: BorderRadius.vertical(top: Radius.circular(12)),
              ),
              child: imageUrl != null
                  ? ClipRRect(
                      borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
                      child: Image.network(imageUrl, fit: BoxFit.cover, errorBuilder: (_, _, _) => const Icon(Icons.inventory_2, color: Color(0xFFC9CCD4))),
                    )
                  : const Center(child: Icon(Icons.inventory_2, size: 36, color: Color(0xFFC9CCD4))),
            ),
          ),
          // Info
          Expanded(
            flex: 2,
            child: Padding(
              padding: const EdgeInsets.all(10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    nama,
                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    'Rp $harga/hr',
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF3D4BFF)),
                  ),
                  const Spacer(),
                  Row(
                    children: [
                      CircleAvatar(
                        radius: 10,
                        backgroundColor: const Color(0xFFEAF0FF),
                        child: Text(
                          owner.substring(0, 1).toUpperCase(),
                          style: const TextStyle(fontSize: 8, fontWeight: FontWeight.w700, color: Color(0xFF3D4BFF)),
                        ),
                      ),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          owner,
                          style: const TextStyle(fontSize: 11, color: Color(0xFF6B7280)),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
