import 'package:flutter/material.dart';
import 'dart:convert';
import '../services/api_service.dart';

class AdminApprovalScreen extends StatefulWidget {
  const AdminApprovalScreen({super.key});

  @override
  State<AdminApprovalScreen> createState() => _AdminApprovalScreenState();
}

class _AdminApprovalScreenState extends State<AdminApprovalScreen> {
  List<dynamic> _items = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadPending();
  }

  Future<void> _loadPending() async {
    setState(() => _isLoading = true);
    final result = await ApiService.getAdminItems(status: 'pending');
    if (mounted) {
      setState(() {
        _items = (result['success'] == true) ? (result['data']['items'] ?? []) : [];
        _isLoading = false;
      });
    }
  }

  Future<void> _approveItem(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Approve Barang?'),
        content: const Text('Barang akan tampil di marketplace.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Approve', style: TextStyle(color: Color(0xFF3D4BFF))),
          ),
        ],
      ),
    );
    if (confirm == true) {
      final result = await ApiService.adminItemAction(itemId: id, action: 'approved');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result['message'] ?? 'Selesai'), backgroundColor: Colors.green),
        );
        await Future.delayed(const Duration(milliseconds: 300));
        _loadPending();
      }
    }
  }

  Future<void> _rejectItem(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Tolak Barang?'),
        content: const Text('Status barang akan diubah menjadi rejected.'),
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
      final result = await ApiService.adminItemAction(itemId: id, action: 'rejected');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result['message'] ?? 'Selesai'), backgroundColor: Colors.orange),
        );
        _loadPending();
      }
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
          'Approval Barang',
          style: TextStyle(color: Color(0xFF1A1D2E), fontWeight: FontWeight.w700, fontSize: 18),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF3D4BFF)))
          : _items.isEmpty
              ? const Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.check_circle_outline, size: 64, color: Color(0xFFD1D5DB)),
                      SizedBox(height: 12),
                      Text('Semua barang sudah diproses!', style: TextStyle(color: Color(0xFF6B7280), fontSize: 15, fontWeight: FontWeight.w600)),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadPending,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: _items.length,
                    itemBuilder: (ctx, i) => _approvalCard(_items[i]),
                  ),
                ),
    );
  }

  Widget _approvalCard(Map item) {
    final nama = item['nama_barang'] ?? '-';
    final deskripsi = item['deskripsi'] ?? '-';
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
        if (files.isNotEmpty) imageUrl = 'http://10.0.2.2/itemlend/uploads/${files[0]}';
      } catch (_) {
        imageUrl = 'http://10.0.2.2/itemlend/uploads/$gambar';
      }
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Column(
        children: [
          // Header
          Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                // Thumbnail
                Container(
                  width: 56,
                  height: 56,
                  decoration: BoxDecoration(
                    color: const Color(0xFFF0F1F5),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: imageUrl != null
                      ? ClipRRect(
                          borderRadius: BorderRadius.circular(10),
                          child: Image.network(imageUrl, fit: BoxFit.cover,
                            errorBuilder: (_, _, _) => const Icon(Icons.inventory_2, color: Color(0xFFC9CCD4)),
                          ),
                        )
                      : const Center(child: Icon(Icons.inventory_2, color: Color(0xFFC9CCD4))),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(nama, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700), maxLines: 1, overflow: TextOverflow.ellipsis),
                      const SizedBox(height: 2),
                      Text(
                        deskripsi,
                        style: const TextStyle(fontSize: 12, color: Color(0xFF6B7280)),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          // Owner
                          CircleAvatar(
                            radius: 10,
                            backgroundColor: const Color(0xFFEAF0FF),
                            child: Text(owner.substring(0, 1).toUpperCase(),
                              style: const TextStyle(fontSize: 8, fontWeight: FontWeight.w700, color: Color(0xFF3D4BFF))),
                          ),
                          const SizedBox(width: 6),
                          Text(owner, style: const TextStyle(fontSize: 11, color: Color(0xFF6B7280))),
                          const Spacer(),
                          Text('Rp $harga/hr', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Color(0xFF3D4BFF))),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          // Actions
          Container(
            padding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
            child: Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () => _approveItem(item['id']),
                    icon: const Icon(Icons.check, size: 16),
                    label: const Text('Approve'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF3D4BFF),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      elevation: 0,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => _rejectItem(item['id']),
                    icon: const Icon(Icons.close, size: 16, color: Colors.red),
                    label: const Text('Tolak', style: TextStyle(color: Colors.red)),
                    style: OutlinedButton.styleFrom(
                      side: const BorderSide(color: Colors.red),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
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
}
