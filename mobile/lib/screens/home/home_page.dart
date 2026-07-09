import 'package:flutter/material.dart';
import '../../widgets/custom_bottom_navbar.dart';

class HomePage extends StatelessWidget {
  const HomePage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),

      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.white,
        title: const Text(
          "Item Lend",
          style: TextStyle(
            color: Colors.black87,
            fontWeight: FontWeight.bold,
          ),
        ),
        actions: [

  IconButton(
    onPressed: () {
      Navigator.pushNamed(context, '/chat');
    },
    icon: const Icon(
      Icons.chat_bubble_outline,
      color: Colors.black87,
    ),
  ),

  IconButton(
    onPressed: () {
      Navigator.pushNamed(context, '/notification');
    },
    icon: const Icon(
      Icons.notifications_none,
      color: Colors.black87,
    ),
  ),
],
      ),

      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(16),

          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,

            children: [

              TextField(
                decoration: InputDecoration(
                  hintText: "Search event equipment...",
                  prefixIcon: const Icon(Icons.search),
                  filled: true,
                  fillColor: Colors.white,

                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(18),
                    borderSide: BorderSide.none,
                  ),
                ),
              ),

              const SizedBox(height: 20),

              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(20),

                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [
                      Color(0xFF2563EB),
                      Color(0xFF60A5FA),
                    ],
                  ),
                  borderRadius: BorderRadius.circular(24),
                ),

                child: const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [

                    Text(
                      "Welcome to Item Lend 👋",
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                      ),
                    ),

                    SizedBox(height: 8),

                    Text(
                      "Find and rent event equipment easily for your campus activities.",
                      style: TextStyle(
                        color: Colors.white70,
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 30),

              const Text(
                "Categories",
                style: TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                ),
              ),

              const SizedBox(height: 15),

              Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [

                  categoryItem(
                    context,
                    Icons.mic,
                    "Audio",
                  ),

                  categoryItem(
                    context,
                    Icons.lightbulb_outline,
                    "Lighting",
                  ),

                  categoryItem(
                    context,
                    Icons.camera_alt_outlined,
                    "Camera",
                  ),
                ],
              ),

              const SizedBox(height: 30),

              const Text(
                "Popular Items",
                style: TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                ),
              ),

              const SizedBox(height: 15),

              itemCard(
                context,
                "Sony Camera",
                "Rp 150k/day",
              ),

              const SizedBox(height: 16),

              itemCard(
                context,
                "Sound System",
                "Rp 300k/day",
              ),
            ],
          ),
        ),
      ),

      bottomNavigationBar: const CustomBottomNavbar(
        currentIndex: 0,
      ),
    );
  }

  Widget categoryItem(
  BuildContext context,
  IconData icon,
  String title,
) {
  return GestureDetector(
    onTap: () {
      Navigator.pushNamed(context, '/items');
    },

    child: Container(
      width: 105,
      height: 125,

      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),

        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 15,
            offset: const Offset(0, 5),
          ),
        ],
      ),

      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,

        children: [

          Container(
            width: 55,
            height: 55,

            decoration: BoxDecoration(
              color: const Color(0xFFEAF2FF),
              borderRadius: BorderRadius.circular(16),
            ),

            child: Icon(
              icon,
              color: const Color(0xFF2563EB),
              size: 28,
            ),
          ),

          const SizedBox(height: 14),

          Text(
            title,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: Colors.black87,
            ),
          ),
        ],
      ),
    ),
  );
}

  Widget itemCard(
    BuildContext context,
    String title,
    String price,
  ) {
    return GestureDetector(
      onTap: () {
        Navigator.pushNamed(context, '/detail');
      },

      child: Container(
        padding: const EdgeInsets.all(16),

        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),

          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),

        child: Row(
          children: [

            Container(
              width: 85,
              height: 85,

              decoration: BoxDecoration(
                color: const Color(0xFFEAF2FF),
                borderRadius: BorderRadius.circular(16),
              ),

              child: const Icon(
                Icons.image_outlined,
                size: 40,
                color: Color(0xFF2563EB),
              ),
            ),

            const SizedBox(width: 16),

            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,

                children: [

                  Text(
                    title,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),

                  const SizedBox(height: 6),

                  Text(
                    price,
                    style: const TextStyle(
                      color: Color(0xFF2563EB),
                      fontWeight: FontWeight.w600,
                    ),
                  ),

                  const SizedBox(height: 8),

                  Row(
                    children: const [

                      Icon(
                        Icons.star,
                        color: Colors.amber,
                        size: 18,
                      ),

                      SizedBox(width: 4),

                      Text("4.9"),
                    ],
                  ),

                  const SizedBox(height: 8),

                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 6,
                    ),

                    decoration: BoxDecoration(
                      color: const Color(0xFF2563EB),
                      borderRadius: BorderRadius.circular(12),
                    ),

                    child: const Text(
                      "Rent Now",
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                      ),
                    ),
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