import 'package:flutter/material.dart';

class RentalHistoryPage extends StatelessWidget {
  const RentalHistoryPage({super.key});

  @override
  Widget build(BuildContext context) {
    // Menggunakan DefaultTabController untuk membuat Tab navigasi
    return DefaultTabController(
      length: 2, 
      child: Scaffold(
        backgroundColor: const Color(0xFFF8F9FA), // Latar abu-abu terang
        appBar: AppBar(
          title: const Text("Riwayat Pesanan", style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF2D3142))),
          backgroundColor: Colors.white,
          elevation: 0,
          centerTitle: true,
          iconTheme: const IconThemeData(color: Color(0xFF3D5AFE)),
          surfaceTintColor: Colors.transparent,
          // Tab Bar di bawah AppBar
          bottom: const TabBar(
            indicatorColor: Color(0xFF3D5AFE),
            indicatorWeight: 3,
            labelColor: Color(0xFF3D5AFE),
            unselectedLabelColor: Colors.grey,
            labelStyle: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
            tabs: [
              Tab(text: "Sedang Berjalan"),
              Tab(text: "Selesai"),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            // TAB 1: SEDANG BERJALAN
            ListView(
              padding: const EdgeInsets.all(24),
              children: [
                _buildHistoryCard(
                  context: context,
                  orderId: "ORD-20260720",
                  itemName: "Sony Camera EOS 700D",
                  vendorName: "John Doe",
                  date: "20 Jul 2026",
                  totalPrice: "Rp 302.000",
                  statusText: "Menunggu Konfirmasi",
                  statusColor: Colors.orange,
                  isActive: true,
                ),
                _buildHistoryCard(
                  context: context,
                  orderId: "ORD-20260718",
                  itemName: "Proyektor Epson EB-X51",
                  vendorName: "Kampus Rent",
                  date: "18 Jul 2026",
                  totalPrice: "Rp 120.000",
                  statusText: "Sedang Disewa",
                  statusColor: Colors.blue,
                  isActive: true,
                ),
              ],
            ),

            // TAB 2: SELESAI
            ListView(
              padding: const EdgeInsets.all(24),
              children: [
                _buildHistoryCard(
                  context: context,
                  orderId: "ORD-20260610",
                  itemName: "Tenda Dome 4 Orang",
                  vendorName: "Outdoor Gear BDG",
                  date: "10 Jun 2026",
                  totalPrice: "Rp 150.000",
                  statusText: "Selesai",
                  statusColor: Colors.green,
                  isActive: false,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  // Widget Pembantu untuk membuat Kartu Riwayat
  Widget _buildHistoryCard({
    required BuildContext context,
    required String orderId,
    required String itemName,
    required String vendorName,
    required String date,
    required String totalPrice,
    required String statusText,
    required Color statusColor,
    required bool isActive,
  }) {
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
          // Header: Tanggal & Badge Status
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    const Icon(Icons.receipt_long_rounded, size: 18, color: Colors.grey),
                    const SizedBox(width: 8),
                    Text(date, style: const TextStyle(color: Colors.grey, fontSize: 13, fontWeight: FontWeight.w500)),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    statusText,
                    style: TextStyle(color: statusColor, fontSize: 12, fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
          ),
          
          const Divider(height: 1, color: Color(0xFFEEEEEE)),

          // Body: Info Barang
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                // Gambar Placeholder
                Container(
                  width: 60,
                  height: 60,
                  decoration: BoxDecoration(
                    color: const Color(0xFFEEF2FF),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.camera_alt_rounded, color: Color(0xFF3D5AFE)),
                ),
                const SizedBox(width: 16),
                // Teks Detail
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(itemName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF2D3142))),
                      const SizedBox(height: 4),
                      Text("Disewa dari: $vendorName", style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                      const SizedBox(height: 8),
                      Text(totalPrice, style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF3D5AFE))),
                    ],
                  ),
                ),
              ],
            ),
          ),

          const Divider(height: 1, color: Color(0xFFEEEEEE)),

          // Footer: Tombol Aksi
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () {
                      // Nanti arahkan ke halaman detail pesanan
                    },
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      side: BorderSide(color: Colors.grey.shade300),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    child: const Text("Detail Pesanan", style: TextStyle(color: Color(0xFF2D3142), fontWeight: FontWeight.bold)),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: () {
                      if (isActive) {
                        Navigator.pushNamed(context, '/chat-detail');
                      } else {
                        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Buka form ulasan...")));
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: isActive ? const Color(0xFF3D5AFE) : Colors.orange, // Biru untuk chat, Oranye untuk ulasan
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      elevation: 0,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    child: Text(
                      isActive ? "Hubungi Vendor" : "Beri Ulasan",
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                    ),
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