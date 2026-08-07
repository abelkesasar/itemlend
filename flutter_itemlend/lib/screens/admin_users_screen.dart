import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'admin_user_detail_screen.dart';

class AdminUsersScreen extends StatefulWidget {
  const AdminUsersScreen({super.key});

  @override
  State<AdminUsersScreen> createState() => _AdminUsersScreenState();
}

class _AdminUsersScreenState extends State<AdminUsersScreen> {
  List<dynamic> _users = [];
  Map<String, dynamic> _stats = {};
  bool _isLoading = true;
  String _search = '';

  @override
  void initState() {
    super.initState();
    _loadUsers();
  }

  Future<void> _loadUsers() async {
    setState(() => _isLoading = true);
    final result = await ApiService.getAdminUsers();
    if (mounted) {
      setState(() {
        if (result['success'] == true) {
          _users = result['data']['users'] ?? [];
          _stats = result['data']['stats'] ?? {};
        }
        _isLoading = false;
      });
    }
  }

  List<dynamic> get _filteredUsers {
    if (_search.isEmpty) return _users;
    final q = _search.toLowerCase();
    return _users.where((u) {
      final name = (u['username'] ?? '').toString().toLowerCase();
      final id = (u['id'] ?? '').toString();
      return name.contains(q) || id.contains(q);
    }).toList();
  }

  Future<void> _approveUser(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Approve User?'),
        content: const Text('User akan bisa mengakses aplikasi.'),
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
      final result = await ApiService.adminUserAction(userId: id, action: 'approved');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result['message'] ?? 'Selesai'), backgroundColor: Colors.green),
        );
        _loadUsers();
      }
    }
  }

  Future<void> _rejectUser(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Tolak & Hapus User?'),
        content: const Text('User akan dihapus permanen dari sistem.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Reject', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (confirm == true) {
      final result = await ApiService.adminUserAction(userId: id, action: 'rejected');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result['message'] ?? 'Selesai'), backgroundColor: Colors.orange),
        );
        _loadUsers();
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
          'Kelola Users',
          style: TextStyle(color: Color(0xFF1A1D2E), fontWeight: FontWeight.w700, fontSize: 18),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF3D4BFF)))
          : RefreshIndicator(
              onRefresh: _loadUsers,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  // Stats
                  _buildStats(),
                  const SizedBox(height: 16),
                  // Search
                  _buildSearch(),
                  const SizedBox(height: 16),
                  // User List
                  ..._filteredUsers.map((u) => _buildUserCard(u)),
                  if (_filteredUsers.isEmpty)
                    const Padding(
                      padding: EdgeInsets.all(32),
                      child: Center(
                        child: Text('Tidak ada user ditemukan.', style: TextStyle(color: Color(0xFF9CA3AF))),
                      ),
                    ),
                ],
              ),
            ),
    );
  }

  Widget _buildStats() {
    return Row(
      children: [
        _statChip('Total', '${_stats['total'] ?? 0}', const Color(0xFF3D4BFF)),
        const SizedBox(width: 8),
        _statChip('Approved', '${_stats['approved'] ?? 0}', const Color(0xFF16A34A)),
        const SizedBox(width: 8),
        _statChip('Pending', '${_stats['pending'] ?? 0}', const Color(0xFFF59E0B)),
        const SizedBox(width: 8),
        _statChip('Cooldown', '${_stats['cooldown'] ?? 0}', const Color(0xFFEF4444)),
      ],
    );
  }

  Widget _statChip(String label, String value, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 6),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: color.withValues(alpha: 0.2)),
        ),
        child: Column(
          children: [
            Text(value, style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: color)),
            const SizedBox(height: 2),
            Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: color)),
          ],
        ),
      ),
    );
  }

  Widget _buildSearch() {
    return TextField(
      onChanged: (v) => setState(() => _search = v),
      decoration: InputDecoration(
        hintText: 'Cari username atau ID...',
        prefixIcon: const Icon(Icons.search, color: Color(0xFF9CA3AF)),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16),
      ),
    );
  }

  Widget _buildUserCard(Map user) {
    final status = user['status'] ?? 'pending';
    final username = user['username'] ?? '-';
    final id = user['id'] ?? 0;
    final role = user['role'] ?? 'user';
    final alamat = user['alamat'] ?? '';
    final nomorWa = user['nomor_wa'] ?? '';
    final bannedUntil = user['banned_until'];

    Color statusColor;
    String statusLabel;
    switch (status) {
      case 'approved':
        statusColor = const Color(0xFF16A34A);
        statusLabel = 'Approved';
        break;
      case 'cooldown':
        statusColor = const Color(0xFFEF4444);
        statusLabel = 'Cooldown';
        break;
      default:
        statusColor = const Color(0xFFF59E0B);
        statusLabel = 'Pending';
    }

    // Check if permanent ban
    if (status == 'cooldown' && bannedUntil != null) {
      final bannedDate = DateTime.tryParse(bannedUntil.toString());
      if (bannedDate != null && bannedDate.isAfter(DateTime.now().add(const Duration(days: 365 * 3)))) {
        statusLabel = 'Banned';
      }
    }

    return GestureDetector(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(builder: (_) => AdminUserDetailScreen(userId: id)),
        );
      },
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFFE5E7EB)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                // Avatar
                CircleAvatar(
                  radius: 20,
                  backgroundColor: const Color(0xFFEAF0FF),
                  child: Text(
                    username.substring(0, 1).toUpperCase(),
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF3D4BFF),
                      fontSize: 14,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        username,
                        style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Color(0xFF1A1D2E)),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'ID: $id · $role',
                        style: const TextStyle(fontSize: 11, color: Color(0xFF6B7280)),
                      ),
                    ],
                  ),
                ),
                // Status Badge
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: statusColor.withValues(alpha: 0.3)),
                  ),
                  child: Text(
                    statusLabel,
                    style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: statusColor),
                  ),
                ),
              ],
            ),
            if (alamat.isNotEmpty) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.location_on, size: 14, color: Color(0xFF9CA3AF)),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      alamat,
                      style: const TextStyle(fontSize: 12, color: Color(0xFF6B7280)),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ],
            // Actions for pending users
            if (status == 'pending') ...[
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () => _approveUser(id),
                      icon: const Icon(Icons.check, size: 16),
                      label: const Text('Approve'),
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
                      onPressed: () => _rejectUser(id),
                      icon: const Icon(Icons.close, size: 16, color: Colors.red),
                      label: const Text('Reject', style: TextStyle(color: Colors.red)),
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
            // WhatsApp link
            if (nomorWa.isNotEmpty && status != 'pending') ...[
              const SizedBox(height: 8),
              GestureDetector(
                onTap: () {
                  final clean = nomorWa.replaceAll(RegExp(r'[^0-9]'), '');
                  final waNumber = clean.startsWith('0') ? '62${clean.substring(1)}' : clean;
                  ApiService.openUrl('https://wa.me/$waNumber');
                },
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: const Color(0xFFE7F9EF),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: const Color(0xFFBBF7D0)),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.chat, size: 14, color: Color(0xFF16A34A)),
                      SizedBox(width: 4),
                      Text('WhatsApp', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF16A34A))),
                    ],
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
