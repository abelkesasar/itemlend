import 'package:flutter/material.dart';

class NotificationPage extends StatelessWidget {
  const NotificationPage({super.key});

  @override
  Widget build(BuildContext context) {
    // Dummy data notifikasi (nanti dari MySQL)
    final List<Map<String, dynamic>> notifications = [
      {
        "title": "Pesanan Diterima! 🎉",
        "message": "Hore! Pesanan sewa Sony Camera EOS 700D kamu telah diterima oleh vendor. Silakan ambil barang di toko.",
        "time": "10 menit yang lalu",
        "type": "success",
        "isRead": false,
      },
      {
        "title": "Pengingat Pengembalian ⏰",
        "message": "Waktu sewa Tenda Dome 4 Orang kamu akan habis besok. Jangan lupa dikembalikan ya biar ga kena denda!",
        "time": "Kemarin, 14:30",
        "type": "warning",
        "isRead": false,
      },
      {
        "title": "Pembayaran Berhasil 💳",
        "message": "Pembayaran sebesar Rp 302.000 untuk transaksi TR-991203 telah dikonfirmasi oleh sistem.",
        "time": "18 Jul 2026",
        "type": "payment",
        "isRead": true,
      },
      {
        "title": "Promo Pengguna Baru",
        "message": "Nikmati diskon 10% untuk penyewaan pertamamu menggunakan kode voucher: ITEMLEND10",
        "time": "15 Jul 2026",
        "type": "promo",
        "isRead": true,
      }
    ];

    // Fungsi untuk menentukan warna dan icon berdasarkan tipe notif
    IconData getIcon(String type) {
      switch (type) {
        case "success": return Icons.check_circle_rounded;
        case "warning": return Icons.access_time_filled_rounded;
        case "payment": return Icons.receipt_long_rounded;
        case "promo": return Icons.local_offer_rounded;
        default: return Icons.notifications_rounded;
      }
    }

    Color getIconColor(String type) {
      switch (type) {
        case "success": return Colors.green;
        case "warning": return Colors.orange;
        case "payment": return const Color(0xFF3D5AFE);
        case "promo": return Colors.purple;
        default: return Colors.grey;
      }
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          "Notifikasi",
          style: TextStyle(color: Color(0xFF2D3142), fontWeight: FontWeight.bold),
        ),
        iconTheme: const IconThemeData(color: Color(0xFF2D3142)),
        centerTitle: true,
        surfaceTintColor: Colors.transparent,
      ),
      
      body: notifications.isEmpty
          ? _buildEmptyState()
          : ListView.builder(
              itemCount: notifications.length,
              itemBuilder: (context, index) {
                final notif = notifications[index];
                final bool isUnread = !notif["isRead"];

                return Container(
                  // Jika belum dibaca, backgroundnya jadi biru sangat muda
                  color: isUnread ? const Color(0xFFEEF2FF) : Colors.white,
                  child: Column(
                    children: [
                      ListTile(
                        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                        // crossAxisAlignment: CrossAxisAlignment.start, // <--- BARIS INI DIHAPUS
                        leading: Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            // Ganti withOpacity(0.1) menjadi withAlpha(25) agar garis biru hilang
                            // (0.1 * 255 = 25.5, jadi dibulatkan ke 25)
                            color: getIconColor(notif["type"]).withAlpha(25), 
                            shape: BoxShape.circle,
                          ),
                          child: Icon(
                            getIcon(notif["type"]),
                            color: getIconColor(notif["type"]),
                            size: 24,
                          ),
                        ),
                        title: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Text(
                                notif["title"],
                                style: TextStyle(
                                  fontWeight: isUnread ? FontWeight.bold : FontWeight.w600,
                                  fontSize: 15,
                                  color: const Color(0xFF2D3142),
                                ),
                              ),
                            ),
                            if (isUnread)
                              Container(
                                width: 8,
                                height: 8,
                                decoration: const BoxDecoration(
                                  color: Colors.red,
                                  shape: BoxShape.circle,
                                ),
                              ),
                          ],
                        ),
                        subtitle: Padding(
                          padding: const EdgeInsets.only(top: 6),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                notif["message"],
                                style: TextStyle(
                                  color: Colors.grey.shade700,
                                  height: 1.4,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                notif["time"],
                                style: const TextStyle(
                                  fontSize: 12,
                                  color: Colors.grey,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                        ),
                        onTap: () {
                          // Nanti kalau diklik bisa diarahkan ke detail pesanan
                          print("Tapped on: ${notif["title"]}");
                        },
                      ),
                      const Divider(height: 1, color: Color(0xFFEEEEEE)),
                    ],
                  ),
                );
              },
            ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.notifications_off_rounded, size: 80, color: Colors.grey.shade300),
          const SizedBox(height: 16),
          const Text("Belum ada notifikasi", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.grey)),
        ],
      ),
    );
  }
}