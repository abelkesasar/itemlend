import 'package:flutter/material.dart';

class SplashPage extends StatefulWidget {
  const SplashPage({super.key});

  @override
  State<SplashPage> createState() => _SplashPageState();
}

class _SplashPageState extends State<SplashPage> {
  @override
  void initState() {
    super.initState();
    // Menunggu selama 2.5 detik, lalu pindah ke halaman Home
    Future.delayed(const Duration(milliseconds: 2500), () {
      if (mounted) {
        Navigator.pushReplacementNamed(context, '/home');
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      // Menggunakan warna biru/ungu sesuai tema desain ItemLend di gambar
      backgroundColor: Color(0xFF4A44F2), 
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Nanti bisa diganti dengan Image.asset('assets/logo.png') jika kamu punya file logonya
            Icon(
              Icons.shopping_bag_outlined, // Icon sementara
              size: 80,
              color: Colors.white,
            ),
            SizedBox(height: 16),
            Text(
              'ItemLend',
              style: TextStyle(
                fontSize: 32,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
          ],
        ),
      ),
    );
  }
}