import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'login_screen.dart';
import 'register_screen.dart';
import 'tambah_barang_screen.dart';
import 'profile_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  Map<String, String?> _userData = {};
  bool _isLoggedIn = false;

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
    if (!mounted) return;
    setState(() {
      _isLoggedIn = token != null && token.isNotEmpty;
      _userData = data;
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

    // TambahBarangScreen / ProfileScreen pop dengan `true` kalau barang berhasil ditambahkan -> refresh list
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
      final k = item['kategori'];
      if (k != null && k.toString().isNotEmpty) set.add(k.toString());
    }
    return set.toList();
  }

  List<dynamic> get _filteredItems {
    var list = _items.where((item) {
      if (_selectedKategori == 'Semua') return true;
      return item['kategori'] == _selectedKategori;
    }).toList();

    if (_sortBy == 'Harga Termurah') {
      list.sort((a, b) => (a['harga'] as int).compareTo(b['harga'] as int));
    } else if (_sortBy == 'Harga Termahal') {
      list.sort((a, b) => (b['harga'] as int).compareTo(a['harga'] as int));
    }
    // 'Terbaru' -> data sudah terurut dari API (ORDER BY created_at DESC)

    return list;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        elevation: 1,
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        title: Row(
          children: [
            Icon(Icons.inventory_2, color: Theme.of(context).colorScheme.primary),
            const SizedBox(width: 8),
            Text(
              'ItemLend',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                color: Theme.of(context).colorScheme.primary,
              ),
            ),
          ],
        ),
        actions: [
          if (_isLoggedIn) ...[
            TextButton.icon(
              onPressed: _goToJualSewa,
              icon: const Icon(Icons.add, size: 18),
              label: const Text('Jual/Sewa'),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 8),
              child: Center(child: Text('Halo, ${_userData['username'] ?? ''}')),
            ),
            IconButton(icon: const Icon(Icons.logout), onPressed: _handleLogout),
          ] else ...[
            TextButton(onPressed: _goToLogin, child: const Text('Login')),
            const SizedBox(width: 4),
            ElevatedButton(onPressed: _goToRegister, child: const Text('Daftar')),
            const SizedBox(width: 12),
          ],
        ],
      ),
      floatingActionButton: _isLoggedIn
          ? FloatingActionButton.extended(
              onPressed: _goToJualSewa,
              icon: const Icon(Icons.add),
              label: const Text('Jual/Sewa'),
            )
          : null,
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
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: const Color(0xFF6366F1),
                  ),
                  onPressed: _goToJualSewa,
                  child: const Text('Daftarkan Barangmu'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: OutlinedButton(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.white,
                    side: const BorderSide(color: Colors.white),
                  ),
                  onPressed: () {}, // sudah di halaman ini, tombol dekoratif
                  child: const Text('Jelajahi Barang'),
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
    );
  }

  Widget _buildFilterRow() {
    return SizedBox(
      height: 36,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: _kategoriList.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
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
        childAspectRatio: 0.68,
      ),
      itemCount: items.length,
      itemBuilder: (context, index) => _buildItemCard(items[index]),
    );
  }

  Widget _buildItemCard(dynamic item) {
    final gambarUrl = item['gambar_url'];

    return Card(
      clipBehavior: Clip.antiAlias,
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: gambarUrl != null
                ? Image.network(
                    gambarUrl,
                    width: double.infinity,
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
                  style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
                ),
                const SizedBox(height: 4),
                Text(
                  _formatHarga(item['harga'] ?? 0),
                  style: const TextStyle(color: Colors.green, fontWeight: FontWeight.bold, fontSize: 14),
                ),
                const SizedBox(height: 2),
                Text(
                  item['owner_username'] ?? '-',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 11, color: Colors.grey),
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
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Halaman detail barang belum tersedia')),
                      );
                    },
                    child: const Text('Lihat Detail'),
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