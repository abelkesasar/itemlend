import 'package:flutter/material.dart';

class MyItemsPage extends StatelessWidget {
  const MyItemsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA), // Abu-abu sangat terang untuk kesan clean
      appBar: AppBar(
        title: const Text("Barang Saya", style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF2D3142))),
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        iconTheme: const IconThemeData(color: Color(0xFF3D5AFE)),
        surfaceTintColor: Colors.transparent, // Mencegah warna berubah saat di-scroll
      ),
      body: ListView.builder(
        padding: const EdgeInsets.all(24),
        itemCount: 3, 
        itemBuilder: (context, index) {
          return Container(
            margin: const EdgeInsets.only(bottom: 16),
            padding: const EdgeInsets.all(16),
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
            child: Row(
              children: [
                // Gambar Barang (Mockup Placeholder)
                Container(
                  width: 70,
                  height: 70,
                  decoration: BoxDecoration(
                    color: const Color(0xFFEEF2FF), // Biru sangat muda
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: const Icon(Icons.inventory_2_rounded, color: Color(0xFF3D5AFE), size: 32),
                ),
                const SizedBox(width: 16),
                
                // Info Barang
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text("Barang Sewaan ${index + 1}", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF2D3142))),
                      const SizedBox(height: 4),
                      const Text("Rp 50.000 / hari", style: TextStyle(color: Color(0xFF3D5AFE), fontWeight: FontWeight.bold)),
                      const SizedBox(height: 8),
                      // Label Status
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.green.shade50,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Text("Tersedia", style: TextStyle(color: Colors.green, fontSize: 11, fontWeight: FontWeight.bold)),
                      ),
                    ],
                  ),
                ),
                
                // Action Buttons
                Column(
                  children: [
                    GestureDetector(
                      onTap: () => Navigator.pushNamed(context, '/edit-item'),
                      child: Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(color: Colors.orange.shade50, shape: BoxShape.circle),
                        child: const Icon(Icons.edit_rounded, color: Colors.orange, size: 20),
                      ),
                    ),
                    const SizedBox(height: 10),
                    GestureDetector(
                      onTap: () {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text("Fitur hapus segera hadir.")),
                        );
                      },
                      child: Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(color: Colors.red.shade50, shape: BoxShape.circle),
                        child: const Icon(Icons.delete_rounded, color: Colors.redAccent, size: 20),
                      ),
                    ),
                  ],
                )
              ],
            ),
          );
        },
      ),
      // Tombol Tambah Barang yang lebih lebar dan premium (Extended FAB)
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: const Color(0xFF3D5AFE),
        elevation: 4,
        onPressed: () => Navigator.pushNamed(context, '/add-item'),
        icon: const Icon(Icons.add_rounded, color: Colors.white),
        label: const Text("Tambah", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
      ),
    );
  }
}