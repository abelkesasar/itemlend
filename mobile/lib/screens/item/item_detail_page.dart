import 'package:flutter/material.dart';

class ItemDetailPage extends StatelessWidget {
  const ItemDetailPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA), // Latar belakang abu-abu sangat terang
      
      // Membuat gambar bisa tembus sampai ke belakang status bar (atas layar)
      extendBodyBehindAppBar: true, 
      
      // Tombol Back Melayang (Floating Back Button)
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: Padding(
          padding: const EdgeInsets.all(8.0),
          child: CircleAvatar(
            backgroundColor: Colors.white,
            child: IconButton(
              icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Color(0xFF2D3142), size: 20),
              onPressed: () => Navigator.pop(context),
            ),
          ),
        ),
      ),

      body: SingleChildScrollView(
        child: Column(
          children: [
            // 1. AREA GAMBAR HERO (FOTO BARANG)
            Container(
              width: double.infinity,
              height: 350,
              decoration: BoxDecoration(
                color: const Color(0xFFEEF2FF),
                image: const DecorationImage(
                  image: AssetImage('assets/placeholder_image.png'),
                  fit: BoxFit.cover,
                  // Baris onError dihapus
                ),
              ),
              child: const Center(
                child: Icon(Icons.camera_alt_rounded, size: 80, color: Color(0xFF3D5AFE)),
              ),
            ),

            // 2. AREA KONTEN DETAIL BARANG (Dibuat overlap/menimpa gambar)
            Transform.translate(
              offset: const Offset(0, -30), // Menarik kontainer ke atas sejauh 30 pixel
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.all(24),
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(32)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Baris Judul dan Rating
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Expanded(
                          child: Text(
                            "Sony Camera",
                            style: TextStyle(fontSize: 26, fontWeight: FontWeight.bold, color: Color(0xFF2D3142)),
                          ),
                        ),
                        // Badge Rating
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                          decoration: BoxDecoration(
                            color: Colors.orange.shade50,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Row(
                            children: const [
                              Icon(Icons.star_rounded, color: Colors.orange, size: 18),
                              SizedBox(width: 4),
                              Text("4.9", style: TextStyle(fontWeight: FontWeight.bold, color: Colors.orange)),
                            ],
                          ),
                        ),
                      ],
                    ),
                    
                    const SizedBox(height: 12),

                    // Baris Harga dan Status
                    Row(
                      children: [
                        const Text(
                          "Rp 150k",
                          style: TextStyle(fontSize: 24, color: Color(0xFF3D5AFE), fontWeight: FontWeight.bold),
                        ),
                        const Text(
                          " / day",
                          style: TextStyle(fontSize: 16, color: Colors.grey, fontWeight: FontWeight.w500),
                        ),
                        const Spacer(),
                        // Badge Status Available
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            color: Colors.green.shade50,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: const Text(
                            "Available",
                            style: TextStyle(color: Colors.green, fontSize: 13, fontWeight: FontWeight.bold),
                          ),
                        ),
                      ],
                    ),

                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 20),
                      child: Divider(color: Color(0xFFEEEEEE), thickness: 1.5),
                    ),

                    // Deskripsi Barang
                    const Text(
                      "Description",
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF2D3142)),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      "Professional Sony camera suitable for seminars, concerts, and campus event documentation. Includes 1 extra battery, 64GB SD Card, and a protective carrying case. Must be returned in the same condition.",
                      style: TextStyle(fontSize: 15, height: 1.6, color: Colors.grey.shade700),
                    ),
                    
                    const SizedBox(height: 20), // Memberi ruang kosong di bawah agar tidak mentok
                  ],
                ),
              ),
            ),
          ],
        ),
      ),

      // 3. BOTTOM ACTION BAR (Selalu menempel di bawah)
      bottomNavigationBar: Container(
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.05),
              blurRadius: 20,
              offset: const Offset(0, -5),
            )
          ],
        ),
        child: SafeArea(
          child: Row(
            children: [
              // Tombol Chat
              Expanded(
                flex: 2,
                child: OutlinedButton(
                  onPressed: () {
                    Navigator.pushNamed(context, '/chat-detail');
                  },
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    side: const BorderSide(color: Color(0xFF3D5AFE), width: 1.5),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  ),
                  child: const Icon(Icons.chat_bubble_outline_rounded, color: Color(0xFF3D5AFE)),
                ),
              ),
              const SizedBox(width: 16),
              // Tombol Rent Now
              Expanded(
                flex: 5,
                child: ElevatedButton(
                  onPressed: () {
                    Navigator.pushNamed(context, '/rental');
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF3D5AFE),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    elevation: 0,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  ),
                  child: const Text(
                    "Rent Now",
                    style: TextStyle(fontSize: 16, color: Colors.white, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}