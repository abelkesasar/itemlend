import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'login_screen.dart';
import 'register_screen.dart';
import 'tambah_barang_screen.dart';
import 'profile_screen.dart';
import 'detail_barang_screen.dart';
import 'toko_saya_screen.dart';
import 'pesanan_saya_screen.dart';
import 'admin_login_screen.dart';
import 'admin_dashboard_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  Map<String, String?> _userData = {};
  bool _isLoggedIn = false;
  bool _isAdmin = false;
  int? _loggedInUserId;

  List<dynamic> _items = [];
  Map<String, dynamic> _stats = {};
  bool _isLoading = true;
  String? _errorMessage;

  String _selectedKategori = 'Semua';
  String _sortBy = 'Terbaru';

  @override
  void initState() {
    super.initState();
    _loadUser();
    _loadItems();
  }

  Future<void> _loadUser() async {
    final token = await ApiService.getToken();
    final data = await ApiService.getUserData();
    final userId = await ApiService.getUserId();
    final adminLoggedIn = await ApiService.isAdminLoggedIn();
    if (!mounted) return;
    setState(() {
      _isLoggedIn = token != null && token.isNotEmpty;
      _userData = data;
      _loggedInUserId = userId;
      _isAdmin = adminLoggedIn;
    });
  }

  Future<void> _loadItems() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final result = await ApiService.getItems();

    if (!mounted) return;

    setState(() {
      _isLoading = false;
      if (result['success'] == true) {
        _items = result['data'] ?? [];
        _stats = result['stats'] ?? {};
      } else {
        _errorMessage = result['message'] ?? 'Gagal memuat data barang.';
      }
    });
  }

  Future<void> _handleLogout() async {
    await ApiService.logout();
    if (!mounted) return;
    Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const HomeScreen()));
  }

  void _goToLogin() {
    Navigator.push(context, MaterialPageRoute(builder: (_) => const LoginScreen()));
  }

  void _goToRegister() {
    Navigator.push(context, MaterialPageRoute(builder: (_) => const RegisterScreen()));
  }

  /// Dipanggil dari tombol hero "Daftarkan Barangmu" & navbar "Jual/Sewa".
  /// Kalau belum login -> Register. Kalau sudah login, cek dulu kelengkapan
  /// metode pembayaran: belum lengkap -> ke Profil, sudah lengkap -> ke Tambah Barang.
  Future<void> _goToJualSewa() async {
    if (!_isLoggedIn) {
      _goToRegister();
      return;
    }

    final profileResult = await ApiService.getProfile();

    if (!mounted) return;

    if (profileResult['success'] != true) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(profileResult['message'] ?? 'Gagal memuat profil.')),
      );
      return;
    }

    final metodeLengkap = profileResult['data']?['metode_pembayaran_lengkap'] ?? false;

    dynamic result;
    if (!metodeLengkap) {
      result = await Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => const ProfileScreen()),
      );
    } else {
      result = await Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => const TambahBarangScreen()),
      );
    }

    if (!mounted) return;

    if (result == true) {
      _loadItems();
    }
  }

  String _formatHarga(int harga) {
    return 'Rp${harga.toString().replaceAllMapped(
      RegExp(r'(\d)(?=(\d{3})+(?!\d))'),
      (match) => '${match[1]}.',
    )}';
  }

  List<String> get _kategoriList {
    final set = <String>{'Semua'};
    for (final item in _items) {
      if (_loggedInUserId != null && item['user_id'] == _loggedInUserId) continue;
      final k = item['kategori'];
      if (k != null && k.toString().isNotEmpty) set.add(k.toString());
    }
    return set.toList();
  }

  List<dynamic> get _filteredItems {
    var list = _items.where((item) {
      // Sembunyikan barang milik user yang sedang login dari Home
      // (barang sendiri ditampilkan di halaman "Barang Saya", bukan di sini)
      if (_loggedInUserId != null && item['user_id'] == _loggedInUserId) return false;

      if (_selectedKategori == 'Semua') return true;
      return item['kategori'] == _selectedKategori;
    }).toList();

    if (_sortBy == 'Harga Termurah') {
      list.sort((a, b) => (a['harga'] as int).compareTo(b['harga'] as int));
    } else if (_sortBy == 'Harga Termahal') {
      list.sort((a, b) => (b['harga'] as int).compareTo(a['harga'] as int));
    }

    return list;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        elevation: 1,
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        titleSpacing: 16,
        title: Row(
          children: [
            Icon(Icons.inventory_2, color: Theme.of(context).colorScheme.primary, size: 20),
            const SizedBox(width: 6),
            Text(
              'ItemLend',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 16,
                color: Theme.of(context).colorScheme.primary,
              ),
            ),
          ],
        ),
        actions: [
          if (_isLoggedIn) ...[
            TextButton.icon(
              onPressed: _goToJualSewa,
              icon: const Icon(Icons.add, size: 16),
              label: const Text('Jual/Sewa', style: TextStyle(fontSize: 12.5)),
              style: TextButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 6)),
            ),
            const SizedBox(width: 4),
            PopupMenuButton<String>(
              padding: EdgeInsets.zero,
              onSelected: (value) {
                if (value == 'logout') _handleLogout();
                if (value == 'profile') {
                  Navigator.push(context, MaterialPageRoute(builder: (_) => const ProfileScreen()));
                }
                if (value == 'tokosaya') {
                  Navigator.push(context, MaterialPageRoute(builder: (_) => const TokoSayaScreen()));
                }
                if (value == 'pesanansaya') {
                  Navigator.push(context, MaterialPageRoute(builder: (_) => const PesananSayaScreen()));
                }
                if (value == 'admin') {
                  if (_isAdmin) {
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const AdminDashboardScreen()));
                  } else {
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const AdminLoginScreen()));
                  }
                }
              },
              itemBuilder: (context) => [
                PopupMenuItem(
                  enabled: false,
                  child: Text(
                    _userData['username'] ?? '',
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                ),
                const PopupMenuDivider(),
                const PopupMenuItem(value: 'profile', child: Text('Profil Saya')),
                const PopupMenuItem(
                  value: 'tokosaya',
                  child: Row(
                    children: [
                      Icon(Icons.store_outlined, size: 18),
                      SizedBox(width: 8),
                      Text('Toko Saya'),
                    ],
                  ),
                ),
                const PopupMenuItem(
                  value: 'pesanansaya',
                  child: Row(
                    children: [
                      Icon(Icons.shopping_bag_outlined, size: 18),
                      SizedBox(width: 8),
                      Text('Pesanan Saya'),
                    ],
                  ),
                ),
                const PopupMenuDivider(),
                PopupMenuItem(
                  value: 'admin',
                  child: Row(
                    children: [
                      Icon(
                        _isAdmin ? Icons.admin_panel_settings : Icons.lock_outline,
                        size: 18,
                        color: const Color(0xFF3D4BFF),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        _isAdmin ? 'Admin Panel' : 'Login Admin',
                        style: const TextStyle(color: Color(0xFF3D4BFF), fontWeight: FontWeight.w600),
                      ),
                    ],
                  ),
                ),
                const PopupMenuItem(value: 'logout', child: Text('Logout')),
              ],
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8),
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 14,
                      backgroundColor: Theme.of(context).colorScheme.primary,
                      child: Text(
                        (_userData['username'] ?? '?').substring(0, 1).toUpperCase(),
                        style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                      ),
                    ),
                    const SizedBox(width: 4),
                    const Icon(Icons.keyboard_arrow_down, size: 16),
                  ],
                ),
              ),
            ),
          ] else ...[
            TextButton(onPressed: _goToLogin, child: const Text('Login')),
            const SizedBox(width: 4),
            ElevatedButton(onPressed: _goToRegister, child: const Text('Daftar')),
            const SizedBox(width: 12),
          ],
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadItems,
        child: _isLoading && _items.isEmpty
            ? const Center(child: CircularProgressIndicator())
            : ListView(
                padding: EdgeInsets.zero,
                children: [
                  _buildHero(),
                  const SizedBox(height: 24),
                  _buildDaftarBarangHeader(),
                  const SizedBox(height: 12),
                  _buildFilterRow(),
                  const SizedBox(height: 12),
                  _buildItemsSection(),
                  const SizedBox(height: 24),
                ],
              ),
      ),
    );
  }

  Widget _buildHero() {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF6366F1), Color(0xFF8B5CF6)],
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Barang tidak terpakai bisa jadi penghasilan.',
            style: TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold, height: 1.3),
          ),
          const SizedBox(height: 10),
          const Text(
            'Sewa apa saja, dari siapa saja. Marketplace sewa-menyewa yang aman, mudah, dan terpercaya.',
            style: TextStyle(color: Colors.white70, fontSize: 14),
          ),
          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: const Color(0xFF6366F1),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                  ),
                  onPressed: _goToJualSewa,
                  icon: const Icon(Icons.add, size: 16),
                  label: const Text('Daftarkan Barangmu', style: TextStyle(fontSize: 12.5)),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.white,
                    side: const BorderSide(color: Colors.white),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                  ),
                  onPressed: () {},
                  icon: const Icon(Icons.search, size: 16),
                  label: const Text('Jelajahi Barang', style: TextStyle(fontSize: 12.5)),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _buildStatItem('${_stats['total_barang'] ?? 0}+', 'Barang Tersedia'),
              _buildStatItem('${_stats['total_pengguna'] ?? 0}+', 'Pengguna Aktif'),
              _buildStatItem('${_stats['total_transaksi'] ?? 0}+', 'Transaksi Sewa'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatItem(String value, String label) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(value, style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold)),
        Text(label, style: const TextStyle(color: Colors.white70, fontSize: 11)),
      ],
    );
  }

  Widget _buildDaftarBarangHeader() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          const Text('Daftar Barang', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
          Row(
            children: [
              Text('${_filteredItems.length} barang', style: const TextStyle(fontSize: 12, color: Colors.grey)),
              DropdownButton<String>(
                value: _sortBy,
                underline: const SizedBox(),
                items: const [
                  DropdownMenuItem(value: 'Terbaru', child: Text('Terbaru')),
                  DropdownMenuItem(value: 'Harga Termurah', child: Text('Harga Termurah')),
                  DropdownMenuItem(value: 'Harga Termahal', child: Text('Harga Termahal')),
                ],
                onChanged: (value) {
                  if (value != null) setState(() => _sortBy = value);
                },
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildFilterRow() {
    return SizedBox(
      height: 36,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: _kategoriList.length,
        separatorBuilder: (_, _) => const SizedBox(width: 8),
        itemBuilder: (context, index) {
          final kategori = _kategoriList[index];
          final selected = kategori == _selectedKategori;
          return ChoiceChip(
            label: Text(kategori),
            selected: selected,
            onSelected: (_) => setState(() => _selectedKategori = kategori),
            selectedColor: Theme.of(context).colorScheme.primary,
            labelStyle: TextStyle(color: selected ? Colors.white : Colors.black87),
          );
        },
      ),
    );
  }

  Widget _buildItemsSection() {
    if (_errorMessage != null) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 60),
        child: Column(
          children: [
            Icon(Icons.error_outline, size: 48, color: Colors.grey[400]),
            const SizedBox(height: 12),
            Text(_errorMessage!, style: const TextStyle(color: Colors.grey)),
            const SizedBox(height: 12),
            TextButton(onPressed: _loadItems, child: const Text('Coba lagi')),
          ],
        ),
      );
    }

    final items = _filteredItems;

    if (items.isEmpty) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 60),
        child: Column(
          children: [
            Icon(Icons.sentiment_dissatisfied_outlined, size: 48, color: Colors.grey[400]),
            const SizedBox(height: 12),
            const Text('Tidak ada barang', style: TextStyle(fontWeight: FontWeight.w600)),
            const Text('Belum ada barang yang tersedia.', style: TextStyle(color: Colors.grey)),
          ],
        ),
      );
    }

    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      padding: const EdgeInsets.symmetric(horizontal: 16),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        mainAxisSpacing: 12,
        crossAxisSpacing: 12,
        childAspectRatio: 0.62,
      ),
      itemCount: items.length,
      itemBuilder: (context, index) => _buildItemCard(items[index]),
    );
  }

  Widget _buildItemCard(dynamic item) {
    final gambarUrl = item['gambar_url'];
    final ownerUsername = (item['owner_username'] ?? '-').toString();
    final ownerInisial = ownerUsername.isNotEmpty ? ownerUsername.substring(0, 2).toUpperCase() : '-';

    return Card(
      clipBehavior: Clip.antiAlias,
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Stack(
              fit: StackFit.expand,
              children: [
                gambarUrl != null
                    ? Image.network(
                        gambarUrl,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) => Container(
                          color: Colors.grey[200],
                          child: const Icon(Icons.image_not_supported_outlined, color: Colors.grey),
                        ),
                      )
                    : Container(
                        color: Colors.grey[200],
                        child: const Icon(Icons.image_outlined, color: Colors.grey),
                      ),
                Positioned(
                  top: 6,
                  right: 6,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.75),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      '${_formatHarga(item['harga'] ?? 0)}/hr',
                      style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w600),
                    ),
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(10),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item['nama_barang'] ?? '-',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5),
                ),
                const SizedBox(height: 2),
                Text(
                  item['deskripsi'] ?? '',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 11, color: Colors.grey),
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    CircleAvatar(
                      radius: 9,
                      backgroundColor: Theme.of(context).colorScheme.primary.withValues(alpha: 0.15),
                      child: Text(
                        ownerInisial,
                        style: TextStyle(fontSize: 8, fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.primary),
                      ),
                    ),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        ownerUsername,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontSize: 10, color: Colors.grey),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      textStyle: const TextStyle(fontSize: 12),
                    ),
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => DetailBarangScreen(itemId: item['id'])),
                      );
                    },
                    child: const Text('Detail'),
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