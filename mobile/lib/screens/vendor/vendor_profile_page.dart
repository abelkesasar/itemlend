import 'package:flutter/material.dart';
import '../../widgets/vendor_bottom_navbar.dart';

class VendorProfilePage extends StatelessWidget {
  const VendorProfilePage({super.key});

  Widget menuTile(
    BuildContext context,
    IconData icon,
    String title,
    VoidCallback onTap,
  ) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: ListTile(
        leading: Container(
          padding: const EdgeInsets.all(8),
          decoration: const BoxDecoration(
            color: Color(0xFFEEF2FF),
            shape: BoxShape.circle,
          ),
          child: Icon(
            icon,
            color: const Color(0xFF3D5AFE),
            size: 24,
          ),
        ),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600, color: Color(0xFF2D3142))),
        trailing: const Icon(Icons.chevron_right, color: Colors.grey),
        onTap: onTap,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      
      appBar: AppBar(
        title: const Text("Profil Vendor", style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF2D3142),
        elevation: 0,
        centerTitle: true,
      ),
      
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            const SizedBox(height: 10),
            
            // Foto Profil Vendor
            Container(
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(color: const Color(0xFF3D5AFE), width: 2),
              ),
              child: const CircleAvatar(
                radius: 50,
                backgroundColor: Color(0xFFEEF2FF),
                child: Icon(
                  Icons.storefront_rounded,
                  size: 50,
                  color: Color(0xFF3D5AFE),
                ),
              ),
            ),
            const SizedBox(height: 16),
            
            // Nama & Email Vendor
            const Text(
              "Danial Rental",
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.bold,
                color: Color(0xFF2D3142)
              ),
            ),
            const SizedBox(height: 5),
            Text(
              "vendor@itemlend.com",
              style: TextStyle(
                color: Colors.grey.shade600,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 30),
            
            // Menu
            menuTile(
              context,
              Icons.inventory_2_rounded,
              "Inventaris Barang",
              () {
                Navigator.pushNamed(context, "/my-items");
              },
            ),
            
            menuTile(
              context,
              Icons.assignment_rounded,
              "Pesanan Masuk",
              () {
                Navigator.pushNamed(context, "/rental-request");
              },
            ),
            
            menuTile(
              context,
              Icons.edit_rounded,
              "Edit Profil",
              () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text("Fitur Edit Profil segera hadir!")),
                );
              },
            ),
            
            menuTile(
              context,
              Icons.settings_rounded,
              "Pengaturan",
              () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text("Fitur Pengaturan segera hadir!")),
                );
              },
            ),
            
            const SizedBox(height: 30),
            
            // Tombol Logout
            SizedBox(
              width: double.infinity,
              height: 55,
              child: ElevatedButton.icon(
                onPressed: () {
                  Navigator.pushNamedAndRemoveUntil(
                    context,
                    "/login",
                    (route) => false,
                  );
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFFFF0F0),
                  foregroundColor: Colors.red,
                  elevation: 0,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                    side: const BorderSide(color: Colors.red, width: 1),
                  ),
                ),
                icon: const Icon(Icons.logout_rounded),
                label: const Text(
                  "Keluar Akun",
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
      
      bottomNavigationBar: const VendorBottomNavbar(
        currentIndex: 3,
      ),
    );
  }
}