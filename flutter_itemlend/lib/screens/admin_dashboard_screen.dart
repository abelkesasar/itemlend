import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'admin_users_screen.dart';
import 'admin_items_screen.dart';
import 'admin_approval_screen.dart';
import 'admin_rentals_screen.dart';
import 'admin_reports_screen.dart';
import 'admin_pencairan_screen.dart';

class AdminDashboardScreen extends StatefulWidget {
  const AdminDashboardScreen({super.key});

  @override
  State<AdminDashboardScreen> createState() => _AdminDashboardScreenState();
}

class _AdminDashboardScreenState extends State<AdminDashboardScreen> {
  Map<String, dynamic>? _data;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadDashboard();
  }

  Future<void> _loadDashboard() async {
    setState(() => _isLoading = true);
    final result = await ApiService.getAdminDashboard();
    if (mounted) {
      setState(() {
        _data = result['success'] == true ? result['data'] : null;
        _isLoading = false;
      });
    }
  }

  void _navigateTo(Widget screen) {
    Navigator.push(context, MaterialPageRoute(builder: (_) => screen));
  }

  Future<void> _logout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Logout Admin?'),
        content: const Text('Kamu akan keluar dari panel admin.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Logout', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (confirm == true) {
      await ApiService.logoutAdmin();
      if (mounted) Navigator.of(context).popUntil((route) => route.isFirst);
    }
  }

  @override
  Widget build(BuildContext context) {
    final stats = _data?['stats'] ?? {};
    final revenue = _data?['revenue'] ?? {};

    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Admin Dashboard',
          style: TextStyle(
            color: Color(0xFF1A1D2E),
            fontWeight: FontWeight.w700,
            fontSize: 18,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: Color(0xFF6B7280)),
            onPressed: _loadDashboard,
          ),
          IconButton(
            icon: const Icon(Icons.logout, color: Color(0xFF6B7280)),
            onPressed: _logout,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF3D4BFF)))
          : _data == null
              ? const Center(child: Text('Gagal memuat data dashboard.'))
              : RefreshIndicator(
                  onRefresh: _loadDashboard,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      // Stats Grid
                      _buildStatsGrid(stats),
                      const SizedBox(height: 16),

                      // Revenue
                      _buildRevenueRow(revenue, stats),
                      const SizedBox(height: 16),

                      // Pending Counters
                      _buildPendingSection(stats),
                      const SizedBox(height: 16),

                      // Quick Actions
                      _buildQuickActions(),
                    ],
                  ),
                ),
    );
  }

  Widget _buildStatsGrid(Map stats) {
    return Row(
      children: [
        Expanded(child: _statCard(Icons.people, 'Users', '${stats['total_users'] ?? 0}', const Color(0xFF3D4BFF))),
        const SizedBox(width: 12),
        Expanded(child: _statCard(Icons.inventory_2, 'Items', '${stats['total_items'] ?? 0}', const Color(0xFF16A34A))),
        const SizedBox(width: 12),
        Expanded(child: _statCard(Icons.shopping_cart, 'Rentals', '${stats['total_rentals'] ?? 0}', const Color(0xFF7C3AED))),
      ],
    );
  }

  Widget _statCard(IconData icon, String label, String value, Color color) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 24),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w800,
              color: color,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: const TextStyle(fontSize: 11, color: Color(0xFF6B7280)),
          ),
        ],
      ),
    );
  }

  Widget _buildRevenueRow(Map revenue, Map stats) {
    return Row(
      children: [
        Expanded(
          child: _revenueCard(
            'Revenue Minggu Ini',
            'Rp ${_formatRp(revenue['minggu'] ?? 0)}',
            const Color(0xFF3D4BFF),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _revenueCard(
            'Revenue Total',
            'Rp ${_formatRp(revenue['total'] ?? 0)}',
            const Color(0xFF16A34A),
          ),
        ),
      ],
    );
  }

  Widget _revenueCard(String label, String value, Color color) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: color),
          ),
          const SizedBox(height: 6),
          FittedBox(
            fit: BoxFit.scaleDown,
            child: Text(
              value,
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                color: color,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPendingSection(Map stats) {
    final items = <Map<String, dynamic>>[];

    final pendingUsers = stats['pending_users'] ?? 0;
    final pendingItems = stats['pending_items'] ?? 0;
    final pendingPembayaran = stats['pending_pembayaran'] ?? 0;
    final pendingReports = stats['pending_reports'] ?? 0;
    final pendingPencairan = stats['pending_pencairan'] ?? 0;

    if (pendingUsers > 0) {
      items.add({'label': 'User menunggu approval', 'count': pendingUsers, 'icon': Icons.person_add, 'color': const Color(0xFFF59E0B)});
    }
    if (pendingItems > 0) {
      items.add({'label': 'Barang perlu approval', 'count': pendingItems, 'icon': Icons.inventory_2, 'color': const Color(0xFFF59E0B)});
    }
    if (pendingPembayaran > 0) {
      items.add({'label': 'Pembayaran perlu konfirmasi', 'count': pendingPembayaran, 'icon': Icons.payment, 'color': const Color(0xFFEF4444)});
    }
    if (pendingReports > 0) {
      items.add({'label': 'Laporan pending', 'count': pendingReports, 'icon': Icons.flag, 'color': const Color(0xFFEF4444)});
    }
    if (pendingPencairan > 0) {
      items.add({'label': 'Pencairan tertahan', 'count': pendingPencairan, 'icon': Icons.account_balance_wallet, 'color': const Color(0xFF7C3AED)});
    }

    if (items.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFFE5E7EB)),
        ),
        child: const Center(
          child: Text(
            'Tidak ada yang perlu ditindak saat ini',
            style: TextStyle(color: Color(0xFF6B7280)),
          ),
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Perlu Ditindak',
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: Color(0xFF1A1D2E)),
        ),
        const SizedBox(height: 10),
        ...items.map((item) => Container(
          margin: const EdgeInsets.only(bottom: 8),
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: const Color(0xFFE5E7EB)),
          ),
          child: Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: (item['color'] as Color).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(item['icon'] as IconData, color: item['color'] as Color, size: 20),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  item['label'] as String,
                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: (item['color'] as Color).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  '${item['count']}',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: item['color'] as Color,
                  ),
                ),
              ),
            ],
          ),
        )),
      ],
    );
  }

  Widget _buildQuickActions() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Quick Actions',
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: Color(0xFF1A1D2E)),
        ),
        const SizedBox(height: 10),
        GridView.count(
          crossAxisCount: 3,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 10,
          crossAxisSpacing: 10,
          childAspectRatio: 1.2,
          children: [
            _actionCard(Icons.people, 'Users', const Color(0xFF3D4BFF), () => _navigateTo(const AdminUsersScreen())),
            _actionCard(Icons.inventory_2, 'Items', const Color(0xFF16A34A), () => _navigateTo(const AdminItemsScreen())),
            _actionCard(Icons.approval, 'Approval', const Color(0xFFF59E0B), () => _navigateTo(const AdminApprovalScreen())),
            _actionCard(Icons.shopping_cart, 'Rentals', const Color(0xFF7C3AED), () => _navigateTo(const AdminRentalsScreen())),
            _actionCard(Icons.flag, 'Laporan', const Color(0xFFEF4444), () => _navigateTo(const AdminReportsScreen())),
            _actionCard(Icons.account_balance_wallet, 'Pencairan', const Color(0xFF0D7377), () => _navigateTo(const AdminPencairanScreen())),
          ],
        ),
      ],
    );
  }

  Widget _actionCard(IconData icon, String label, Color color, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFFE5E7EB)),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, color: color, size: 28),
            const SizedBox(height: 8),
            Text(
              label,
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
            ),
          ],
        ),
      ),
    );
  }

  String _formatRp(int amount) {
    return amount.toString().replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (Match m) => '${m[1]}.',
    );
  }
}
