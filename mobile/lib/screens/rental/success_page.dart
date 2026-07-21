import 'package:flutter/material.dart';

class SuccessPage extends StatelessWidget {
  const SuccessPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // 1. Ikon Sukses
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: Colors.green.shade50,
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.check_circle_rounded,
                  color: Colors.green,
                  size: 100,
                ),
              ),
              const SizedBox(height: 32),
              
              // 2. Teks Ucapan Selamat
              const Text(
                "Pesanan Berhasil Dibuat!",
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF2D3142),
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              Text(
                "Yay! Permintaan sewa kamu sudah diteruskan ke vendor. Silakan pantau status pesananmu secara berkala.",
                style: TextStyle(
                  fontSize: 15,
                  color: Colors.grey.shade600,
                  height: 1.6,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 40),
              
              // 3. Card ID Pesanan
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8F9FA),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.grey.shade200),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text("ID Pesanan", style: TextStyle(color: Colors.grey.shade600)),
                    const Text("ORD-20260720", style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF2D3142))),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
      
      // 4. Tombol Aksi di Bawah
      bottomNavigationBar: Container(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min, // Agar tingginya menyesuaikan isi
          children: [
            SizedBox(
              width: double.infinity,
              height: 55,
              child: ElevatedButton(
                onPressed: () {
                  // Arahkan ke halaman riwayat pesanan (rental_history_page.dart)
                  // Navigator.pushReplacementNamed(context, '/rental-history');
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF3D5AFE), // Biru utama
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  elevation: 0,
                ),
                child: const Text("Lihat Riwayat Pesanan", style: TextStyle(fontSize: 16, color: Colors.white, fontWeight: FontWeight.bold)),
              ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              height: 55,
              child: TextButton(
                onPressed: () {
                  // Arahkan kembali ke dashboard/home mahasiswa dan hapus riwayat rute sebelumnya
                  // Navigator.pushNamedAndRemoveUntil(context, '/home', (route) => false);
                },
                style: TextButton.styleFrom(
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                ),
                child: const Text("Kembali ke Beranda", style: TextStyle(fontSize: 16, color: Color(0xFF3D5AFE), fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}