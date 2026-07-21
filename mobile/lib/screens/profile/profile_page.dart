import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';
import '../../widgets/custom_bottom_navbar.dart';

class ProfilePage extends StatelessWidget {
  const ProfilePage({super.key});

  @override
  Widget build(BuildContext context) {
    // 1. Ambil seluruh instance AuthService
    final authService = Provider.of<AuthService>(context);
    bool isLoggedIn = authService.isLoggedIn;

    // 2. Ambil data nama dan email dari Map 'user' di AuthService
    // Memanggil key sesuai nama kolom di database kamu ('username' dan 'email')
    String name = authService.user?['username'] ?? "Nama Pengguna";
    String email = authService.user?['email'] ?? "email@domain.com";

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        title: const Text(
          "Profile",
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            const CircleAvatar(
              radius: 50,
              backgroundColor: Color(0xFFEAF2FF),
              child: Icon(Icons.person, size: 60, color: Color(0xFF2563EB)),
            ),
            const SizedBox(height: 16),
            
            Text(
              name,
              style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 6),
            
            Text(
              email,
              style: const TextStyle(color: Colors.grey, fontSize: 15),
            ),
            
            const SizedBox(height: 30),
            
            profileMenu(context, Icons.edit_outlined, "Edit Profile", () {}),
            profileMenu(context, Icons.receipt_long_outlined, "Rental History", () {
              Navigator.pushNamed(context, '/rental-history');
            }),
            profileMenu(context, Icons.favorite_border, "Wishlist", () {
              Navigator.pushNamed(context, '/wishlist');
            }),
            profileMenu(context, Icons.chat_bubble_outline, "Chat", () {
              Navigator.pushNamed(context, '/chat');
            }),
            profileMenu(context, Icons.notifications_none, "Notification", () {
              Navigator.pushNamed(context, '/notification');
            }),
            
            const SizedBox(height: 25),
            SizedBox(
              width: double.infinity,
              height: 55,
              child: ElevatedButton.icon(
                onPressed: () {
                  // Panggil fungsi logout
                  Provider.of<AuthService>(context, listen: false).logout();
                  Navigator.pushNamedAndRemoveUntil(context, '/login', (route) => false);
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.red,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                ),
                icon: const Icon(Icons.logout, color: Colors.white),
                label: const Text(
                  "Logout",
                  style: TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.bold),
                ),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: CustomBottomNavbar(
        currentIndex: 3,
        isLoggedIn: isLoggedIn, 
      ),
    );
  }

  Widget profileMenu(BuildContext context, IconData icon, String title, VoidCallback onTap) {
    return Card(
      elevation: 1,
      margin: const EdgeInsets.only(bottom: 14),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: ListTile(
        leading: Icon(icon, color: const Color(0xFF2563EB)),
        title: Text(title),
        trailing: const Icon(Icons.chevron_right),
        onTap: onTap,
      ),
    );
  }
}