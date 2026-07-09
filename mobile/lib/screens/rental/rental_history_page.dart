import 'package:flutter/material.dart';
import '../../widgets/custom_bottom_navbar.dart';

class RentalHistoryPage extends StatelessWidget {
  const RentalHistoryPage({super.key});

  @override
  Widget build(BuildContext context) {
    final List<Map<String, dynamic>> rentals = [
      {
        "item": "Sony Camera",
        "date": "2 Jul 2026 - 4 Jul 2026",
        "price": "Rp 300.000",
        "status": "Waiting Approval",
        "color": Colors.orange,
      },
      {
        "item": "Lighting Set",
        "date": "10 Jun 2026 - 12 Jun 2026",
        "price": "Rp 400.000",
        "status": "Approved",
        "color": Colors.green,
      },
      {
        "item": "Projector Epson",
        "date": "15 Mei 2026 - 16 Mei 2026",
        "price": "Rp 250.000",
        "status": "Rejected",
        "color": Colors.red,
      },
      {
        "item": "Sound System",
        "date": "20 Apr 2026 - 22 Apr 2026",
        "price": "Rp 600.000",
        "status": "Completed",
        "color": Colors.blue,
      },
    ];

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),

      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        title: const Text(
          "Rental History",
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
      ),

      body: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: rentals.length,
        itemBuilder: (context, index) {
          final rental = rentals[index];

          return Container(
            margin: const EdgeInsets.only(bottom: 18),
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

            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,

              children: [

                Row(
                  children: [

                    Container(
                      width: 70,
                      height: 70,

                      decoration: BoxDecoration(
                        color: const Color(0xFFEAF2FF),
                        borderRadius: BorderRadius.circular(16),
                      ),

                      child: const Icon(
                        Icons.image_outlined,
                        color: Color(0xFF2563EB),
                        size: 34,
                      ),
                    ),

                    const SizedBox(width: 16),

                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,

                        children: [

                          Text(
                            rental["item"],
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),

                          const SizedBox(height: 6),

                          Text(
                            rental["date"],
                            style: TextStyle(
                              color: Colors.grey.shade600,
                            ),
                          ),

                          const SizedBox(height: 6),

                          Text(
                            rental["price"],
                            style: const TextStyle(
                              color: Color(0xFF2563EB),
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 18),

                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,

                  children: [

                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 8,
                      ),

                      decoration: BoxDecoration(
                        color: (rental["color"] as Color).withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(30),
                      ),

                      child: Text(
                        rental["status"],
                        style: TextStyle(
                          color: rental["color"],
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),

                    TextButton.icon(
                      onPressed: () {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text("Detail rental akan segera tersedia."),
                          ),
                        );
                      },

                      icon: const Icon(Icons.arrow_forward_ios, size: 16),

                      label: const Text("View Detail"),
                    ),
                  ],
                ),
              ],
            ),
          );
        },
      ),
      
      bottomNavigationBar: const CustomBottomNavbar(
        currentIndex: 1,
      ),
    );
  }
}