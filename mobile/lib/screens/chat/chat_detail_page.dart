import 'package:flutter/material.dart';

class ChatDetailPage extends StatefulWidget {
  const ChatDetailPage({super.key});

  @override
  State<ChatDetailPage> createState() => _ChatDetailPageState();
}

class _ChatDetailPageState extends State<ChatDetailPage> {
  final TextEditingController _messageController = TextEditingController();

  // Dummy data untuk percakapan chat
  final List<Map<String, dynamic>> _messages = [
    {
      "text": "Halo! Apakah Sony Camera EOS 700D untuk tanggal 20 Juli masih tersedia?",
      "isMe": true,
      "time": "10:00"
    },
    {
      "text": "Halo kak! Iya masih tersedia, siap disewa untuk tanggal tersebut. Mau diambil sendiri atau diantar?",
      "isMe": false,
      "time": "10:05"
    },
    {
      "text": "Rencananya mau diambil sendiri aja kak ke toko.",
      "isMe": true,
      "time": "10:06"
    },
  ];

  void _sendMessage() {
    if (_messageController.text.trim().isNotEmpty) {
      setState(() {
        _messages.add({
          "text": _messageController.text,
          "isMe": true,
          "time": "10:10", // Harusnya pakai format waktu realtime (DateTime.now)
        });
      });
      _messageController.clear();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA), // Latar belakang abu-abu sangat terang
      
      // 1. APP BAR KHUSUS CHAT (Ada foto profil & status)
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Color(0xFF2D3142), size: 20),
          onPressed: () => Navigator.pop(context),
        ),
        titleSpacing: 0,
        title: Row(
          children: [
            const CircleAvatar(
              radius: 18,
              backgroundColor: Color(0xFFEEF2FF),
              child: Icon(Icons.storefront_rounded, color: Color(0xFF3D5AFE), size: 20),
            ),
            const SizedBox(width: 12),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text("John Doe (Vendor)", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF2D3142))),
                Row(
                  children: [
                    Container(
                      width: 8,
                      height: 8,
                      decoration: const BoxDecoration(color: Colors.green, shape: BoxShape.circle),
                    ),
                    const SizedBox(width: 6),
                    const Text("Online", style: TextStyle(fontSize: 12, color: Colors.grey)),
                  ],
                ),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.more_vert_rounded, color: Color(0xFF2D3142)),
            onPressed: () {},
          )
        ],
      ),

      // 2. AREA CHAT BUBBLE
      body: Column(
        children: [
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.all(20),
              itemCount: _messages.length,
              itemBuilder: (context, index) {
                final message = _messages[index];
                final isMe = message['isMe'];

                return _buildChatBubble(message['text'], message['time'], isMe);
              },
            ),
          ),

          // 3. AREA INPUT PESAN
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.05),
                  blurRadius: 10,
                  offset: const Offset(0, -5),
                )
              ],
            ),
            child: SafeArea(
              child: Row(
                children: [
                  // Tombol Attachment
                  Container(
                    decoration: BoxDecoration(color: Colors.grey.shade100, shape: BoxShape.circle),
                    child: IconButton(
                      icon: const Icon(Icons.attach_file_rounded, color: Colors.grey),
                      onPressed: () {},
                    ),
                  ),
                  const SizedBox(width: 12),
                  
                  // Kolom Ketik
                  Expanded(
                    child: TextField(
                      controller: _messageController,
                      decoration: InputDecoration(
                        hintText: "Ketik pesan...",
                        hintStyle: TextStyle(color: Colors.grey.shade400),
                        filled: true,
                        fillColor: const Color(0xFFF8F9FA),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(24),
                          borderSide: BorderSide.none,
                        ),
                      ),
                      textInputAction: TextInputAction.send,
                      onSubmitted: (_) => _sendMessage(),
                    ),
                  ),
                  const SizedBox(width: 12),

                  // Tombol Kirim
                  GestureDetector(
                    onTap: _sendMessage,
                    child: Container(
                      padding: const EdgeInsets.all(12),
                      decoration: const BoxDecoration(
                        color: Color(0xFF3D5AFE),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.send_rounded, color: Colors.white, size: 20),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  // Widget Pembantu untuk Chat Bubble
  Widget _buildChatBubble(String text, String time, bool isMe) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Row(
        mainAxisAlignment: isMe ? MainAxisAlignment.end : MainAxisAlignment.start,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          if (!isMe) ...[
            const CircleAvatar(
              radius: 14,
              backgroundColor: Color(0xFFEEF2FF),
              child: Icon(Icons.storefront_rounded, color: Color(0xFF3D5AFE), size: 14),
            ),
            const SizedBox(width: 8),
          ],
          
          Flexible(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                color: isMe ? const Color(0xFF3D5AFE) : Colors.white,
                borderRadius: BorderRadius.only(
                  topLeft: const Radius.circular(20),
                  topRight: const Radius.circular(20),
                  // Ujung bawah yang tajam tergantung siapa yang mengirim
                  bottomLeft: isMe ? const Radius.circular(20) : const Radius.circular(0),
                  bottomRight: isMe ? const Radius.circular(0) : const Radius.circular(20),
                ),
                boxShadow: isMe
                    ? [] // Pesan kita tidak pakai shadow agar terkesan flat
                    : [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 5, offset: const Offset(0, 2))],
              ),
              child: Column(
                crossAxisAlignment: isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
                children: [
                  Text(
                    text,
                    style: TextStyle(
                      color: isMe ? Colors.white : const Color(0xFF2D3142),
                      fontSize: 15,
                      height: 1.4,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    time,
                    style: TextStyle(
                      color: isMe ? Colors.white70 : Colors.grey.shade500,
                      fontSize: 10,
                    ),
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