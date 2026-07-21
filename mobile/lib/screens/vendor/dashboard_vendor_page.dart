import 'package:flutter/material.dart';

class DashboardVendorPage extends StatefulWidget {
  const DashboardVendorPage({super.key});

  @override
  State<DashboardVendorPage> createState() => _DashboardVendorPageState();
}

class _DashboardVendorPageState extends State<DashboardVendorPage> {
  int _selectedIndex = 0; // Untuk animasi Bottom Navigation Bar

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA), // Warna background abu-abu sangat terang (modern)
      
      // Menggunakan Stack agar Card Statistik bisa melayang di atas Header Biru
      body: Stack(
        children: [
          // 1. BACKGROUND HEADER KHUSUS VENDOR
          Container(
            height: 240,
            width: double.infinity,
            padding: const EdgeInsets.only(top: 60, left: 24, right: 24),
            decoration: const BoxDecoration(
              color: Color(0xFF3D5AFE),
              borderRadius: BorderRadius.only(
                bottomLeft: Radius.circular(32),
                bottomRight: Radius.circular(32),
              ),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.2),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: const Text(
                        "Vendor Center",
                        style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                      ),
                    ),
                    const SizedBox(height: 12),
                    const Text(
                      "Halo, Vendor!",
                      style: TextStyle(fontSize: 26, fontWeight: FontWeight.bold, color: Colors.white),
                    ),
                    const Text(
                      "Kelola barang sewaanmu hari ini.",
                      style: TextStyle(color: Colors.white70, fontSize: 14),
                    ),
                  ],
                ),
                // Tombol Logout dipindah ke pojok kanan atas agar lebih rapi
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.2),
                    shape: BoxShape.circle,
                  ),
                  child: IconButton(
                    icon: const Icon(Icons.logout_rounded, color: Colors.white),
                    tooltip: 'Keluar (Logout)',
                    onPressed: () => Navigator.pushReplacementNamed(context, '/login'),
                  ),
                ),
              ],
            ),
          ),

          // 2. KONTEN UTAMA YANG BISA DI-SCROLL
          SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.only(top: 130), // Memberi jarak agar tidak menutupi teks header
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Card Statistik Melayang
                    Row(
                      children: [
                        _buildStatCard(
                          title: "Barang Saya",
                          value: "12",
                          icon: Icons.inventory_2_rounded,
                          iconColor: const Color(0xFF3D5AFE),
                          bgColor: Colors.blue.shade50,
                        ),
                        const SizedBox(width: 16),
                        _buildStatCard(
                          title: "Pesanan Baru",
                          value: "3",
                          icon: Icons.notifications_active_rounded,
                          iconColor: const Color(0xFFFF9100), // Warna oranye untuk notifikasi
                          bgColor: Colors.orange.shade50,
                        ),
                      ],
                    ),
                    const SizedBox(height: 32),

                    const Text(
                      "Menu Manajemen",
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF2D3142)),
                    ),
                    const SizedBox(height: 16),

                    // Menu Aksi Modern (Tombol Logout besar di bawah sudah dihapus)
                    _buildMenuTile(context, "Kelola Barang", "Tambah & edit barang sewaan", Icons.list_alt_rounded, '/my-items'),
                    _buildMenuTile(context, "Permintaan Sewa", "Cek siapa yang mau menyewa", Icons.handshake_rounded, '/rental-request'),
                    _buildMenuTile(context, "Profil Vendor", "Atur info toko kamu", Icons.person_rounded, '/vendor-profile'),
                    
                    const SizedBox(height: 40), 
                  ],
                ),
              ),
            ),
          ),
        ],
      ),

      // 3. BOTTOM NAVIGATION BAR TAMBAHAN
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 20, offset: const Offset(0, -5))],
        ),
        child: ClipRRect(
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          child: BottomNavigationBar(
            currentIndex: _selectedIndex,
            onTap: (index) => setState(() => _selectedIndex = index),
            backgroundColor: Colors.white,
            selectedItemColor: const Color(0xFF3D5AFE),
            unselectedItemColor: Colors.grey.shade400,
            showUnselectedLabels: true,
            type: BottomNavigationBarType.fixed,
            items: const [
              BottomNavigationBarItem(icon: Icon(Icons.dashboard_rounded), label: "Dashboard"),
              BottomNavigationBarItem(icon: Icon(Icons.inventory_2_outlined), label: "Barang"),
              BottomNavigationBarItem(icon: Icon(Icons.receipt_long_rounded), label: "Pesanan"),
              BottomNavigationBarItem(icon: Icon(Icons.person_outline_rounded), label: "Profil"),
            ],
          ),
        ),
      ),
    );
  }

  // Fungsi Pembuat Card Statistik
  Widget _buildStatCard({required String title, required String value, required IconData icon, required Color iconColor, required Color bgColor}) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.06),
              blurRadius: 15,
              offset: const Offset(0, 8),
            )
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: bgColor, shape: BoxShape.circle),
              child: Icon(icon, color: iconColor, size: 28),
            ),
            const SizedBox(height: 16),
            Text(value, style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: Color(0xFF2D3142))),
            const SizedBox(height: 4),
            Text(title, style: const TextStyle(color: Colors.grey, fontSize: 13, fontWeight: FontWeight.w500)),
          ],
        ),
      ),
    );
  }

  // Fungsi Pembuat Menu List (Slicing Hi-Fi)
  Widget _buildMenuTile(BuildContext context, String title, String subtitle, IconData icon, String route) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 10,
            offset: const Offset(0, 4),
          )
        ],
      ),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
        leading: Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: Colors.blue.shade50,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, color: const Color(0xFF3D5AFE)),
        ),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF2D3142))),
        subtitle: Text(subtitle, style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
        trailing: Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(color: Colors.grey.shade100, shape: BoxShape.circle),
          child: const Icon(Icons.arrow_forward_ios, size: 14, color: Colors.grey),
        ),
        onTap: () => Navigator.pushNamed(context, route),
      ),
    );
  }
}