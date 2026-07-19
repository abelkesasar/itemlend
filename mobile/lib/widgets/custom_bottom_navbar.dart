import 'package:flutter/material.dart';

class CustomBottomNavbar extends StatelessWidget {
  final int currentIndex;
  final bool isLoggedIn; // Tambahan: untuk mengecek apakah user sudah login atau belum

  const CustomBottomNavbar({
    super.key,
    required this.currentIndex,
    this.isLoggedIn = false, // Default-nya false (Guest Mode)
  });

  @override
  Widget build(BuildContext context) {
    return BottomNavigationBar(
      currentIndex: currentIndex,
      type: BottomNavigationBarType.fixed,
      selectedItemColor: const Color(0xFF4A44F2), // Warna biru ungu sesuai tema
      unselectedItemColor: Colors.grey,
      selectedLabelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
      
      onTap: (index) {
        if (index == currentIndex) return;

        // PENCEGAHAN: Jika belum login dan klik selain Beranda (index 0)
        if (!isLoggedIn && index != 0) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Silakan login terlebih dahulu untuk mengakses menu ini.'),
              duration: Duration(seconds: 2),
            ),
          );
          Navigator.pushNamed(context, '/login');
          return; // Hentikan proses navigasi tab
        }

        // Navigasi normal jika sudah login atau klik Beranda
        switch (index) {
          case 0:
            Navigator.pushReplacementNamed(context, '/home');
            break;
          case 1:
            Navigator.pushReplacementNamed(context, '/rental-history');
            break;
          case 2:
            Navigator.pushReplacementNamed(context, '/wishlist');
            break;
          case 3:
            Navigator.pushReplacementNamed(context, '/profile');
            break;
        }
      },
      items: const [
        BottomNavigationBarItem(
          icon: Icon(Icons.home_outlined),
          activeIcon: Icon(Icons.home),
          label: "Beranda",
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.storefront_outlined),
          activeIcon: Icon(Icons.storefront),
          label: "Jual/Sewa",
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.receipt_long_outlined),
          activeIcon: Icon(Icons.receipt_long),
          label: "Pesanan",
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.person_outline),
          activeIcon: Icon(Icons.person),
          label: "Akun",
        ),
      ],
    );
  }
}