import 'package:flutter/material.dart';

class ItemDetailPage extends StatelessWidget {
  const ItemDetailPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(

      backgroundColor: Colors.grey.shade100,

      appBar: AppBar(
        backgroundColor: Colors.blue,

        title: const Text(
          "Item Detail",
          style: TextStyle(
            color: Colors.white,
          ),
        ),
      ),

      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,

          children: [

            Container(
              width: double.infinity,
              height: 250,
              color: Colors.blue.shade100,

              child: const Icon(
                Icons.image,
                size: 100,
              ),
            ),

            Padding(
              padding: const EdgeInsets.all(20),

              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,

                children: [

                  const Text(
                    "Sony Camera",
                    style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.bold,
                    ),
                  ),

                  const SizedBox(height: 10),

                  const Text(
                    "Rp 150k/day",
                    style: TextStyle(
                      fontSize: 22,
                      color: Colors.blue,
                      fontWeight: FontWeight.bold,
                    ),
                  ),

                  const SizedBox(height: 20),

                  Row(
                    children: const [

                      Icon(
                        Icons.star,
                        color: Colors.orange,
                      ),

                      SizedBox(width: 5),

                      Text(
                        "4.9 Rating",
                        style: TextStyle(
                          fontSize: 16,
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 25),

                  const Text(
                    "Description",
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),

                  const SizedBox(height: 10),

                  const Text(
                    "Professional Sony camera suitable for seminars, concerts, and campus event documentation.",
                    style: TextStyle(
                      fontSize: 16,
                      height: 1.5,
                    ),
                  ),

                  const SizedBox(height: 25),

                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 10,
                    ),

                    decoration: BoxDecoration(
                      color: Colors.green.shade100,
                      borderRadius: BorderRadius.circular(12),
                    ),

                    child: const Text(
                      "Available",
                      style: TextStyle(
                        color: Colors.green,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),

                  const SizedBox(height: 40),

                  Row(
  children: [

    Expanded(
      flex: 2,

      child: OutlinedButton.icon(

        onPressed: () {
          Navigator.pushNamed(context, '/chat-detail');
        },

        style: OutlinedButton.styleFrom(
          minimumSize: const Size(double.infinity, 58),

          side: const BorderSide(
            color: Colors.blue,
            width: 1.5,
          ),

          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
        ),

        icon: const Icon(
          Icons.chat_bubble_outline,
          color: Colors.blue,
        ),

        label: const Text(
          "Chat Owner",
          style: TextStyle(
            color: Colors.blue,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
    ),

    const SizedBox(width: 12),

    Expanded(
      flex: 3,

      child: SizedBox(
        height: 58,

        child: ElevatedButton(

          onPressed: () {
            Navigator.pushNamed(context, '/rental');
          },

          style: ElevatedButton.styleFrom(
            backgroundColor: Colors.blue,

            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16),
            ),
          ),

          child: const Text(
            "Rent Now",
            style: TextStyle(
              fontSize: 18,
              color: Colors.white,
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
      ),
    ),
  ],
),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}