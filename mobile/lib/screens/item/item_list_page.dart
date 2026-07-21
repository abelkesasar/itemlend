import 'package:flutter/material.dart';

class ItemListPage extends StatefulWidget {
  const ItemListPage({super.key});

  @override
  State<ItemListPage> createState() => _ItemListPageState();
}

class _ItemListPageState extends State<ItemListPage> {
  // Filter kategori yang aktif
  int _selectedCategoryIndex = 0;
  final List<String> _categories = ["Semua", "Kamera", "Outdoor", "Elektronik"];

  // Dummy data semua barang (nanti diganti dengan get dari MySQL)
  final List<Map<String, dynamic>> _allItems = [
    {
      "id": "1",
      "name": "Sony Camera EOS 700D",
      "category": "Kamera",
      "price": "151.000",
      "rating": "4.8",
      "icon": Icons.camera_alt_rounded,
    },
    {
      "id": "2",
      "name": "Tenda Dome 4 Orang",
      "category": "Outdoor",
      "price": "50.000",
      "rating": "4.9",
      "icon": Icons.holiday_village_rounded,
    },
    {
      "id": "3",
      "name": "Proyektor Epson EB-X51",
      "category": "Elektronik",
      "price": "100.000",
      "rating": "4.7",
      "icon": Icons.videocam_rounded,
    },
    {
      "id": "4",
      "name": "Drone DJI Mavic Pro",
      "category": "Kamera",
      "price": "250.000",
      "rating": "4.9",
      "icon": Icons.flight_rounded, // Menggunakan icon pesawat/drone sbg ilustrasi
    },
    {
      "id": "5",
      "name": "Carrier Eiger 60L",
      "category": "Outdoor",
      "price": "40.000",
      "rating": "4.8",
      "icon": Icons.backpack_rounded,
    },
    {
      "id": "6",
      "name": "Speaker Portable JBL",
      "category": "Elektronik",
      "price": "85.000",
      "rating": "4.6",
      "icon": Icons.speaker_rounded,
    },
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          "Cari Barang",
          style: TextStyle(color: Color(0xFF2D3142), fontWeight: FontWeight.bold),
        ),
        iconTheme: const IconThemeData(color: Color(0xFF2D3142)),
        centerTitle: true,
        surfaceTintColor: Colors.transparent,
      ),
      
      body: Column(
        children: [
          // Bagian Atas: Kolom Pencarian & Filter
          Container(
            color: Colors.white,
            padding: const EdgeInsets.only(left: 20, right: 20, bottom: 16),
            child: Column(
              children: [
                // Search Bar
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF5F7FA),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: Colors.grey.shade200),
                  ),
                  child: const TextField(
                    decoration: InputDecoration(
                      icon: Icon(Icons.search_rounded, color: Colors.grey),
                      hintText: "Mau sewa apa hari ini?",
                      hintStyle: TextStyle(color: Colors.grey, fontSize: 14),
                      border: InputBorder.none,
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                
                // Kategori Chips (Bisa digeser ke samping)
                SizedBox(
                  height: 36,
                  child: ListView.builder(
                    scrollDirection: Axis.horizontal,
                    itemCount: _categories.length,
                    itemBuilder: (context, index) {
                      final isSelected = _selectedCategoryIndex == index;
                      return GestureDetector(
                        onTap: () {
                          setState(() {
                            _selectedCategoryIndex = index;
                          });
                        },
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 200),
                          margin: const EdgeInsets.only(right: 12),
                          padding: const EdgeInsets.symmetric(horizontal: 20),
                          alignment: Alignment.center,
                          decoration: BoxDecoration(
                            color: isSelected ? const Color(0xFF3D5AFE) : Colors.white,
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(
                              color: isSelected ? const Color(0xFF3D5AFE) : Colors.grey.shade300,
                            ),
                          ),
                          child: Text(
                            _categories[index],
                            style: TextStyle(
                              color: isSelected ? Colors.white : Colors.grey.shade700,
                              fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                              fontSize: 13,
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
          
          // Bagian Bawah: Grid Daftar Barang
          Expanded(
            child: GridView.builder(
              padding: const EdgeInsets.all(20),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2, // 2 kolom
                crossAxisSpacing: 16,
                mainAxisSpacing: 16,
                childAspectRatio: 0.72, // Mengatur tinggi card (makin kecil makin tinggi)
              ),
              itemCount: _allItems.length,
              itemBuilder: (context, index) {
                final item = _allItems[index];
                return _buildItemCard(item);
              },
            ),
          ),
        ],
      ),
    );
  }

  // Widget untuk Card Barang
  Widget _buildItemCard(Map<String, dynamic> item) {
    return GestureDetector(
      onTap: () {
        // Nanti arahkan ke halaman detail barang
        // Navigator.pushNamed(context, '/item-detail');
      },
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withAlpha(10), // pakai withAlpha agar aman dari warning IDE
              blurRadius: 10,
              offset: const Offset(0, 4),
            )
          ],
          border: Border.all(color: Colors.grey.shade100),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Bagian Gambar (Sementara pakai Icon)
            Expanded(
              child: Container(
                width: double.infinity,
                decoration: const BoxDecoration(
                  color: Color(0xFFEEF2FF),
                  borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
                ),
                child: Icon(item["icon"], size: 50, color: const Color(0xFF3D5AFE)),
              ),
            ),
            
            // Bagian Info Barang
            Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Kategori & Rating
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        item["category"],
                        style: const TextStyle(fontSize: 10, color: Colors.grey),
                      ),
                      Row(
                        children: [
                          const Icon(Icons.star_rounded, color: Colors.amber, size: 12),
                          const SizedBox(width: 2),
                          Text(item["rating"], style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                        ],
                      )
                    ],
                  ),
                  const SizedBox(height: 6),
                  
                  // Nama Barang
                  Text(
                    item["name"],
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF2D3142)),
                  ),
                  const SizedBox(height: 8),
                  
                  // Harga
                  Text(
                    "Rp ${item["price"]}",
                    style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF3D5AFE), fontSize: 13),
                  ),
                  const Text(
                    "/hari",
                    style: TextStyle(color: Colors.grey, fontSize: 10),
                  ),
                ],
              ),
            )
          ],
        ),
      ),
    );
  }
}