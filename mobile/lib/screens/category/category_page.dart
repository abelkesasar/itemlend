import 'package:flutter/material.dart';

class CategoryPage extends StatelessWidget {
  const CategoryPage({super.key});

  @override
  Widget build(BuildContext context) {
    // Data kategori direvisi khusus untuk Event Kampus
    final List<Map<String, dynamic>> _categories = [
      {
        "title": "Dokumentasi", 
        "icon": Icons.camera_alt_rounded, 
        "count": "45+",
        "desc": "Kamera, Lensa, Tripod"
      },
      {
        "title": "Audio & Sound", 
        "icon": Icons.speaker_rounded, 
        "count": "30+",
        "desc": "Mic, Speaker, Mixer"
      },
      {
        "title": "Visual & Presentasi", 
        "icon": Icons.videocam_rounded, 
        "count": "25+",
        "desc": "Proyektor, Screen, Pointer"
      },
      {
        "title": "Komunikasi", 
        "icon": Icons.cell_tower_rounded, 
        "count": "50+",
        "desc": "HT (Handy Talky), Megaphone"
      },
      {
        "title": "Logistik Acara", 
        "icon": Icons.table_restaurant_rounded, 
        "count": "80+",
        "desc": "Meja, Kursi, Tenda Stand"
      },
      {
        "title": "Lighting & Dekorasi", 
        "icon": Icons.lightbulb_rounded, 
        "count": "20+",
        "desc": "Lampu Panggung, Stand Banner"
      },
      {
        "title": "Konsumsi", 
        "icon": Icons.kitchen_rounded, 
        "count": "15+",
        "desc": "Cooler Box, Dispenser"
      },
      {
        "title": "Kostum & Properti", 
        "icon": Icons.checkroom_rounded, 
        "count": "40+",
        "desc": "Baju Tari, Properti Teater"
      },
    ];

    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          "Kategori Event Kampus",
          style: TextStyle(color: Color(0xFF2D3142), fontWeight: FontWeight.bold),
        ),
        iconTheme: const IconThemeData(color: Color(0xFF2D3142)),
        centerTitle: true,
        surfaceTintColor: Colors.transparent,
      ),
      
      body: GridView.builder(
        padding: const EdgeInsets.all(20),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2, 
          crossAxisSpacing: 16,
          mainAxisSpacing: 16,
          childAspectRatio: 0.9, // Disesuaikan agar muat untuk nambah teks deskripsi kecil
        ),
        itemCount: _categories.length,
        itemBuilder: (context, index) {
          final category = _categories[index];
          return _buildCategoryCard(context, category);
        },
      ),
    );
  }

  Widget _buildCategoryCard(BuildContext context, Map<String, dynamic> category) {
    return GestureDetector(
      onTap: () {
        // Nanti kalau diklik, bisa diarahkan ke ItemListPage dengan filter ini
        Navigator.pushNamed(context, '/item-list');
      },
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withAlpha(10),
              blurRadius: 10,
              offset: const Offset(0, 4),
            )
          ],
          border: Border.all(color: Colors.grey.shade100),
        ),
        child: Padding(
          padding: const EdgeInsets.all(12.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(14),
                decoration: const BoxDecoration(
                  color: Color(0xFFEEF2FF),
                  shape: BoxShape.circle,
                ),
                child: Icon(category["icon"], size: 30, color: const Color(0xFF3D5AFE)),
              ),
              const SizedBox(height: 12),
              
              Text(
                category["title"],
                textAlign: TextAlign.center,
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF2D3142)),
              ),
              const SizedBox(height: 4),
              
              // Tambahan deskripsi kecil biar user tau isinya apa aja
              Text(
                category["desc"],
                textAlign: TextAlign.center,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(color: Colors.grey.shade500, fontSize: 10),
              ),
              const SizedBox(height: 8),
              
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: Colors.orange.withAlpha(30),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  "${category["count"]} Barang",
                  style: const TextStyle(color: Colors.orange, fontSize: 10, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}