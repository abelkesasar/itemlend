import 'package:flutter/material.dart';

class RentalRequestPage extends StatefulWidget {
  const RentalRequestPage({super.key});

  @override
  State<RentalRequestPage> createState() => _RentalRequestPageState();
}

class _RentalRequestPageState extends State<RentalRequestPage> {
  // Dummy data pesanan masuk (nanti ini diganti dengan data dari MySQL/API)
  final List<Map<String, dynamic>> _requests = [
    {
      "id": "REQ-001",
      "user_name": "Danial Alaska", // Nama Mahasiswa
      "item_name": "Sony Camera EOS 700D",
      "rental_date": "20 Jul - 22 Jul 2026",
      "price": "Rp 302.000",
      "image_icon": Icons.camera_alt_rounded
    },
    {
      "id": "REQ-002",
      "user_name": "Budi Santoso",
      "item_name": "Tenda Dome 4 Orang",
      "rental_date": "25 Jul - 27 Jul 2026",
      "price": "Rp 150.000",
      "image_icon": Icons.holiday_village_rounded
    }
  ];

  // Fungsi simulasi untuk aksi Terima / Tolak
  void _handleRequest(int index, bool isAccepted) {
    final action = isAccepted ? "diterima" : "ditolak";
    
    // Tampilkan notifikasi (SnackBar)
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text("Pesanan dari ${_requests[index]['user_name']} berhasil $action!"),
        backgroundColor: isAccepted ? Colors.green : Colors.red,
      ),
    );
    
    // Hapus data dari UI
    setState(() {
      _requests.removeAt(index);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA), // Latar abu-abu terang
      
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          "Pesanan Masuk",
          style: TextStyle(color: Color(0xFF2D3142), fontWeight: FontWeight.bold),
        ),
        iconTheme: const IconThemeData(color: Color(0xFF2D3142)),
        centerTitle: true,
        surfaceTintColor: Colors.transparent,
      ),
      
      // Jika _requests kosong, tampilkan layar kosong yang rapi
      body: _requests.isEmpty
          ? _buildEmptyState()
          : ListView.builder(
              padding: const EdgeInsets.all(20),
              itemCount: _requests.length,
              itemBuilder: (context, index) {
                return _buildRequestCard(index);
              },
            ),
    );
  }

  // Widget saat tidak ada pesanan masuk
  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.inbox_rounded, size: 80, color: Colors.grey.shade300),
          const SizedBox(height: 16),
          const Text(
            "Hore! Belum ada pesanan baru.",
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.grey),
          ),
          const SizedBox(height: 8),
          const Text(
            "Pesanan yang masuk akan muncul di sini.",
            style: TextStyle(color: Colors.grey),
          )
        ],
      ),
    );
  }

  // Widget Card untuk setiap pesanan
  Widget _buildRequestCard(int index) {
    final request = _requests[index];
    
    return Container(
      margin: const EdgeInsets.only(bottom: 20),
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
      child: Column(
        children: [
          // Header: Info Mahasiswa Penyewa
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 20,
                  backgroundColor: const Color(0xFFEEF2FF),
                  // Ambil inisial nama untuk avatar
                  child: Text(request["user_name"][0], style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF3D5AFE))),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(request["user_name"], style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF2D3142))),
                      const Text("Mahasiswa", style: TextStyle(color: Colors.grey, fontSize: 12)),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(color: Colors.orange.withOpacity(0.1), borderRadius: BorderRadius.circular(8)),
                  child: const Text("Menunggu", style: TextStyle(color: Colors.orange, fontSize: 12, fontWeight: FontWeight.bold)),
                )
              ],
            ),
          ),
          
          const Divider(height: 1, color: Color(0xFFEEEEEE)),
          
          // Body: Info Barang yang Disewa
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Container(
                  width: 60,
                  height: 60,
                  decoration: BoxDecoration(color: const Color(0xFFEEF2FF), borderRadius: BorderRadius.circular(12)),
                  child: Icon(request["image_icon"], color: const Color(0xFF3D5AFE)),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(request["item_name"], style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF2D3142))),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          const Icon(Icons.calendar_today_rounded, size: 14, color: Colors.grey),
                          const SizedBox(width: 4),
                          Text(request["rental_date"], style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(request["price"], style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF3D5AFE))),
                    ],
                  ),
                ),
              ],
            ),
          ),
          
          const Divider(height: 1, color: Color(0xFFEEEEEE)),
          
          // Footer: Tombol Terima / Tolak
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => _handleRequest(index, false),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      side: const BorderSide(color: Colors.red),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    child: const Text("Tolak", style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: () => _handleRequest(index, true),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF3D5AFE),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      elevation: 0,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    child: const Text("Terima", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}