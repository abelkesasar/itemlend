import 'package:flutter/material.dart';

class WishlistPage extends StatefulWidget {
  const WishlistPage({super.key});

  @override
  State<WishlistPage> createState() => _WishlistPageState();
}

class _WishlistPageState extends State<WishlistPage> {
  // Dummy data wishlist
  final List<Map<String, dynamic>> _wishlistItems = [
    {
      "id": "1",
      "name": "Sony Camera EOS 700D",
      "category": "Kamera",
      "price": "Rp 151.000",
      "rating": "4.8",
      "icon": Icons.camera_alt_rounded,
    },
    {
      "id": "2",
      "name": "Tenda Dome 4 Orang",
      "category": "Alat Outdoor",
      "price": "Rp 50.000",
      "rating": "4.9",
      "icon": Icons.holiday_village_rounded,
    },
    {
      "id": "3",
      "name": "Proyektor Epson EB-X51",
      "category": "Elektronik",
      "price": "Rp 100.000",
      "rating": "4.7",
      "icon": Icons.videocam_rounded,
    }
  ];

  // Fungsi untuk menghapus barang dari wishlist
  void _removeFromWishlist(int index) {
    final removedItem = _wishlistItems[index]["name"];
    
    setState(() {
      _wishlistItems.removeAt(index);
    });

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text("$removedItem dihapus dari wishlist"),
        backgroundColor: Colors.black87,
        duration: const Duration(seconds: 2),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          "Wishlist Saya",
          style: TextStyle(color: Color(0xFF2D3142), fontWeight: FontWeight.bold),
        ),
        iconTheme: const IconThemeData(color: Color(0xFF2D3142)),
        centerTitle: true,
        surfaceTintColor: Colors.transparent,
      ),
      
      body: _wishlistItems.isEmpty
          ? _buildEmptyState()
          : ListView.builder(
              padding: const EdgeInsets.all(20),
              itemCount: _wishlistItems.length,
              itemBuilder: (context, index) {
                final item = _wishlistItems[index];
                return _buildWishlistCard(item, index);
              },
            ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.favorite_border_rounded, size: 80, color: Colors.grey.shade300),
          const SizedBox(height: 16),
          const Text(
            "Wishlist kamu masih kosong",
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.grey),
          ),
          const SizedBox(height: 8),
          const Text(
            "Yuk cari barang yang mau kamu sewa!",
            style: TextStyle(color: Colors.grey),
          ),
          const SizedBox(height: 24),
          ElevatedButton(
            onPressed: () {
              // Arahkan kembali ke halaman Home/Dashboard
              Navigator.pop(context); 
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF3D5AFE),
              padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            child: const Text("Cari Barang", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  Widget _buildWishlistCard(Map<String, dynamic> item, int index) {
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
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Row(
        children: [
          // Gambar/Icon Barang
          Container(
            width: 100,
            height: 100,
            margin: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFFEEF2FF),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Icon(item["icon"], size: 40, color: const Color(0xFF3D5AFE)),
          ),
          
          // Info Barang
          Expanded(
            child: Padding(
              padding: const EdgeInsets.only(top: 12, bottom: 12, right: 12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Kategori & Rating
                      Row(
                        children: [
                          Text(
                            item["category"],
                            style: const TextStyle(color: Colors.grey, fontSize: 12),
                          ),
                          const SizedBox(width: 8),
                          const Icon(Icons.star_rounded, color: Colors.amber, size: 14),
                          Text(
                            item["rating"],
                            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                      // Tombol Hapus (Icon Hati Merah)
                      GestureDetector(
                        onTap: () => _removeFromWishlist(index),
                        child: const Icon(Icons.favorite_rounded, color: Colors.red, size: 22),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  
                  // Nama Barang
                  Text(
                    item["name"],
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF2D3142)),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 8),
                  
                  // Harga & Tombol Sewa
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        "${item["price"]} /hari",
                        style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF3D5AFE), fontSize: 13),
                      ),
                      SizedBox(
                        height: 32,
                        child: ElevatedButton(
                          onPressed: () {
                            // Nanti diarahkan ke halaman detail barang
                            // Navigator.pushNamed(context, '/item-detail');
                          },
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF3D5AFE),
                            elevation: 0,
                            padding: const EdgeInsets.symmetric(horizontal: 16),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                          ),
                          child: const Text("Sewa", style: TextStyle(color: Colors.white, fontSize: 12)),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}