import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../widgets/custom_bottom_navbar.dart';
import '../../services/auth_service.dart';

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  // Mengambil status login dari AuthService via Provider
  bool get isLoggedIn => Provider.of<AuthService>(context).isLoggedIn;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      
      // endDrawer digunakan agar menu hamburger muncul dari sebelah kanan
      endDrawer: _buildDrawer(context),

      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.white,
        automaticallyImplyLeading: false,
        title: Row(
          children: const [
            Icon(Icons.shopping_bag_outlined, color: Color(0xFF4A44F2)),
            SizedBox(width: 8),
            Text(
              "ItemLend",
              style: TextStyle(
                color: Color(0xFF4A44F2),
                fontWeight: FontWeight.bold,
                fontSize: 20,
              ),
            ),
          ],
        ),
        actions: [
          // Tombol pemanggil Hamburger Menu
          Builder(
            builder: (context) => IconButton(
              icon: const Icon(Icons.menu, color: Colors.black87),
              onPressed: () => Scaffold.of(context).openEndDrawer(),
            ),
          ),
        ],
      ),

      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Banner Utama
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF4A44F2), Color(0xFF6E68F5)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(24),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      "Barang tidak terpakai\nbisa jadi penghasilan.",
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        height: 1.2,
                      ),
                    ),
                    const SizedBox(height: 12),
                    const Text(
                      "Sewa apa saja, dari siapa saja. Marketplace sewa-menyewa yang aman, mudah, dan terpercaya.",
                      style: TextStyle(color: Colors.white70, fontSize: 13),
                    ),
                    const SizedBox(height: 20),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: const Color(0xFF4A44F2),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        onPressed: () {}, 
                        child: const Text("+ Mulai Sekarang", style: TextStyle(fontWeight: FontWeight.bold)),
                      ),
                    ),
                    const SizedBox(height: 10),
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton(
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.white,
                          side: const BorderSide(color: Colors.white),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        onPressed: () {}, 
                        child: const Text("🔍 Jelajahi Barang"),
                      ),
                    ),
                    const SizedBox(height: 20),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                      children: _buildBannerStats(),
                    )
                  ],
                ),
              ),

              const SizedBox(height: 24),
              const Text(
                "Daftar Barang",
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 16),
              
              Row(
                children: [
                  const Text("Filter: ", style: TextStyle(color: Colors.grey)),
                  const SizedBox(width: 8),
                  _buildFilterChip("Semua", true),
                  const SizedBox(width: 8),
                  _buildFilterChip("Sound sistem", false),
                  const SizedBox(width: 8),
                  _buildFilterChip("Microphone", false),
                ],
              ),
              const SizedBox(height: 24),

              itemCard(context, "Kamera Sony A7II", "Rp 150.000/hari"),
              const SizedBox(height: 16),
              itemCard(context, "Tenda Dome Kapasitas 4", "Rp 50.000/hari"),
            ],
          ),
        ),
      ),
      
      bottomNavigationBar: CustomBottomNavbar(
        currentIndex: 0,
        isLoggedIn: isLoggedIn, 
      ),
    );
  }

  // Widget untuk Isi Drawer (Hamburger Menu)
  Widget _buildDrawer(BuildContext context) {
    return Drawer(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 50),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: const [
                    Icon(Icons.shopping_bag_outlined, color: Color(0xFF4A44F2)),
                    SizedBox(width: 8),
                    Text("ItemLend", style: TextStyle(color: Color(0xFF4A44F2), fontWeight: FontWeight.bold, fontSize: 18)),
                  ],
                ),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                )
              ],
            ),
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.home_outlined),
            title: const Text('Beranda'),
            onTap: () => Navigator.pop(context),
          ),
          
          // Logika tampilan berdasarkan status login
          if (!isLoggedIn) ...[
            ListTile(
              leading: const Icon(Icons.login),
              title: const Text('Login'),
              onTap: () {
                Navigator.pop(context);
                Navigator.pushNamed(context, '/login');
              },
            ),
            ListTile(
              leading: const Icon(Icons.person_add_outlined),
              title: const Text('Daftar'),
              onTap: () {
                Navigator.pop(context);
                Navigator.pushNamed(context, '/register');
              },
            ),
          ] else ...[
            ListTile(
              leading: const Icon(Icons.person_outline),
              title: const Text('Profil Saya'),
              onTap: () {},
            ),
            ListTile(
              leading: const Icon(Icons.storefront_outlined),
              title: const Text('Toko Saya'),
              onTap: () {},
            ),
            ListTile(
              leading: const Icon(Icons.logout, color: Colors.red),
              title: const Text('Logout', style: TextStyle(color: Colors.red)),
              onTap: () {
                // Perbaikan: Panggil fungsi logout dari service, jangan pakai setState
                Provider.of<AuthService>(context, listen: false).logout();
                Navigator.pop(context);
              },
            ),
          ]
        ],
      ),
    );
  }

  List<Widget> _buildBannerStats() {
    return [
      _statItem("7+", "Barang\nTersedia"),
      _statItem("5+", "Pengguna\nAktif"),
      _statItem("11+", "Transaksi\nSewa"),
    ];
  }

  Widget _statItem(String value, String label) {
    return Column(
      children: [
        Text(value, style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
        Text(label, textAlign: TextAlign.center, style: const TextStyle(color: Colors.white70, fontSize: 10)),
      ],
    );
  }

  Widget _buildFilterChip(String label, bool isSelected) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      decoration: BoxDecoration(
        color: isSelected ? const Color(0xFF4A44F2) : Colors.transparent,
        border: Border.all(color: isSelected ? const Color(0xFF4A44F2) : Colors.grey.shade300),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: isSelected ? Colors.white : Colors.black87,
          fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
        ),
      ),
    );
  }

  Widget itemCard(BuildContext context, String title, String price) {
    return GestureDetector(
      onTap: () => Navigator.pushNamed(context, '/detail'),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.05),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(color: Colors.grey.shade200, borderRadius: BorderRadius.circular(12)),
              child: const Icon(Icons.image_outlined, size: 40, color: Colors.grey),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 4),
                  Text(price, style: const TextStyle(color: Color(0xFF4A44F2), fontWeight: FontWeight.bold)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}