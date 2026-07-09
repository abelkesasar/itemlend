import 'package:flutter/material.dart';

class ChatDetailPage extends StatefulWidget {
  const ChatDetailPage({super.key});

  @override
  State<ChatDetailPage> createState() => _ChatDetailPageState();
}

class _ChatDetailPageState extends State<ChatDetailPage> {

  final TextEditingController messageController =
      TextEditingController();

  final List<Map<String, dynamic>> messages = [
    {
      "me": false,
      "text": "Halo kak, barang masih tersedia?"
    },
    {
      "me": true,
      "text": "Masih kak, silakan diajukan."
    },
    {
      "me": false,
      "text": "Baik kak, saya sewa tanggal 5."
    },
  ];

  @override
  Widget build(BuildContext context) {

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),

      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,

        title: const Row(
          children: [

            CircleAvatar(
              backgroundColor: Colors.blue,
              child: Icon(
                Icons.store,
                color: Colors.white,
              ),
            ),

            SizedBox(width: 12),

            Text(
              "Sony Camera Rental",
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
      ),

      body: Column(
        children: [

          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: messages.length,

              itemBuilder: (context, index) {

                final message = messages[index];

                return Align(
                  alignment: message["me"]
                      ? Alignment.centerRight
                      : Alignment.centerLeft,

                  child: Container(
                    margin: const EdgeInsets.only(bottom: 12),
                    padding: const EdgeInsets.all(14),

                    constraints: const BoxConstraints(
                      maxWidth: 280,
                    ),

                    decoration: BoxDecoration(
                      color: message["me"]
                          ? Colors.blue
                          : Colors.white,

                      borderRadius:
                          BorderRadius.circular(18),
                    ),

                    child: Text(
                      message["text"],

                      style: TextStyle(
                        color: message["me"]
                            ? Colors.white
                            : Colors.black87,
                      ),
                    ),
                  ),
                );
              },
            ),
          ),

          SafeArea(
            child: Container(
              padding: const EdgeInsets.all(12),

              child: Row(
                children: [

                  Expanded(
                    child: TextField(
                      controller: messageController,

                      decoration: InputDecoration(
                        hintText: "Type message...",
                        filled: true,
                        fillColor: Colors.white,

                        border: OutlineInputBorder(
                          borderRadius:
                              BorderRadius.circular(30),
                          borderSide: BorderSide.none,
                        ),
                      ),
                    ),
                  ),

                  const SizedBox(width: 10),

                  CircleAvatar(
                    radius: 26,
                    backgroundColor: Colors.blue,

                    child: IconButton(
                      onPressed: () {

                        if (messageController.text.isEmpty) {
                          return;
                        }

                        setState(() {
                          messages.add({
                            "me": true,
                            "text": messageController.text,
                          });
                        });

                        messageController.clear();
                      },
                      icon: const Icon(
                        Icons.send,
                        color: Colors.white,
                      ),
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