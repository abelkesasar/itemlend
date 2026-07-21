import 'package:flutter/material.dart';

class ChatListPage extends StatelessWidget {
  const ChatListPage({super.key});

  @override
  Widget build(BuildContext context) {
    // Dummy data untuk daftar chat (nanti dari MySQL)
    final List<Map<String, dynamic>> chatList = [
      {
        "name": "Toko Kamera Bandung",
        "last_message": "Baik kak, ditunggu kedatangannya.",
        "time": "10:15",
        "unread": 0,
        "isVendor": true,
      },
      {
        "name": "Outdoor Gear BDG",
        "last_message": "Tenda dome kapasitas 4 orang masih ready.",
        "time": "Kemarin",
        "unread": 2,
        "isVendor": true,
      },
      {
        "name": "Budi Santoso",
        "last_message": "Apakah proyektornya bisa diantar?",
        "time": "18 Jul",
        "unread": 0,
        "isVendor": false,
      }
    ];

    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          "Pesan",
          style: TextStyle(color: Color(0xFF2D3142), fontWeight: FontWeight.bold),
        ),
        iconTheme: const IconThemeData(color: Color(0xFF2D3142)),
        centerTitle: true,
        surfaceTintColor: Colors.transparent,
      ),
      
      body: chatList.isEmpty 
        ? _buildEmptyState()
        : ListView.separated(
            padding: const EdgeInsets.symmetric(vertical: 8),
            itemCount: chatList.length,
            separatorBuilder: (context, index) => const Divider(height: 1, color: Color(0xFFEEEEEE)),
            itemBuilder: (context, index) {
              final chat = chatList[index];
              return ListTile(
                contentPadding: const EdgeInsets.symmetric(vertical: 12, horizontal: 20),
                leading: Stack(
                  children: [
                    CircleAvatar(
                      radius: 26,
                      backgroundColor: const Color(0xFFEEF2FF),
                      child: Icon(
                        chat["isVendor"] ? Icons.storefront_rounded : Icons.person_rounded,
                        color: const Color(0xFF3D5AFE),
                      ),
                    ),
                    // Indikator online/unread kecil di sudut avatar
                    if (chat["unread"] > 0)
                      Positioned(
                        right: 0,
                        bottom: 0,
                        child: Container(
                          width: 14,
                          height: 14,
                          decoration: BoxDecoration(
                            color: Colors.green,
                            shape: BoxShape.circle,
                            border: Border.all(color: Colors.white, width: 2),
                          ),
                        ),
                      )
                  ],
                ),
                title: Text(
                  chat["name"],
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF2D3142)),
                ),
                subtitle: Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Text(
                    chat["last_message"],
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      // Teks lebih tebal kalau pesannya belum dibaca
                      color: chat["unread"] > 0 ? const Color(0xFF2D3142) : Colors.grey.shade600,
                      fontWeight: chat["unread"] > 0 ? FontWeight.bold : FontWeight.normal,
                    ),
                  ),
                ),
                trailing: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      chat["time"],
                      style: TextStyle(
                        fontSize: 12,
                        color: chat["unread"] > 0 ? const Color(0xFF3D5AFE) : Colors.grey,
                        fontWeight: chat["unread"] > 0 ? FontWeight.bold : FontWeight.normal,
                      ),
                    ),
                    const SizedBox(height: 8),
                    // Badge jumlah pesan belum terbaca
                    if (chat["unread"] > 0)
                      Container(
                        padding: const EdgeInsets.all(6),
                        decoration: const BoxDecoration(color: Color(0xFF3D5AFE), shape: BoxShape.circle),
                        child: Text(
                          chat["unread"].toString(),
                          style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                        ),
                      ),
                  ],
                ),
                onTap: () {
                  // Arahkan ke halaman detail chat yang kemarin sudah kita buat
                  Navigator.pushNamed(context, '/chat-detail');
                },
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
          Icon(Icons.chat_bubble_outline_rounded, size: 80, color: Colors.grey.shade300),
          const SizedBox(height: 16),
          const Text("Belum ada pesan", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.grey)),
        ],
      ),
    );
  }
}