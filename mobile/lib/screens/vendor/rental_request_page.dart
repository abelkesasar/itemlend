import 'package:flutter/material.dart';
import '../../widgets/vendor_bottom_navbar.dart';

class RentalRequestPage extends StatefulWidget {
  const RentalRequestPage({super.key});

  @override
  State<RentalRequestPage> createState() => _RentalRequestPageState();
}

class _RentalRequestPageState extends State<RentalRequestPage> {
  final List<Map<String, dynamic>> requests = [
    {
      "item": "Sony Camera",
      "customer": "Danial Alaska",
      "date": "2 Jul 2026 - 4 Jul 2026",
      "price": "Rp300.000",
      "status": "Waiting",
    },
    {
      "item": "Lighting Set",
      "customer": "Aldo Saputra",
      "date": "6 Jul 2026 - 8 Jul 2026",
      "price": "Rp400.000",
      "status": "Waiting",
    },
  ];

  Color statusColor(String status) {
    switch (status) {
      case "Approved":
        return Colors.green;

      case "Rejected":
        return Colors.red;

      default:
        return Colors.orange;
    }
  }

  @override
  Widget build(BuildContext context) {
        return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),

      appBar: AppBar(
        title: const Text("Rental Request"),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 0,
      ),

      body: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: requests.length,
        itemBuilder: (context, index) {

          final request = requests[index];

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
                        Icons.inventory_2_outlined,
                        color: Color(0xFF2563EB),
                        size: 35,
                      ),
                    ),

                    const SizedBox(width: 16),

                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,

                        children: [

                          Text(
                            request["item"],
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),

                          const SizedBox(height: 6),

                          Text(
                            "Customer : ${request["customer"]}",
                          ),

                          Text(
                            request["date"],
                          ),

                          const SizedBox(height: 5),

                          Text(
                            request["price"],
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

                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 8,
                  ),

                  decoration: BoxDecoration(
                    color: statusColor(request["status"])
                        .withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(25),
                  ),

                  child: Text(
                    request["status"],
                    style: TextStyle(
                      color: statusColor(request["status"]),
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),

                const SizedBox(height: 18),

                if (request["status"] == "Waiting")

                  Row(
                    children: [

                      Expanded(
                        child: OutlinedButton(
                          onPressed: () {

                            setState(() {
                              request["status"] = "Rejected";
                            });

                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text("Rental Rejected"),
                              ),
                            );
                          },

                          style: OutlinedButton.styleFrom(
                            foregroundColor: Colors.red,
                          ),

                          child: const Text("Reject"),
                        ),
                      ),

                      const SizedBox(width: 12),

                      Expanded(
                        child: ElevatedButton(
                          onPressed: () {

                            setState(() {
                              request["status"] = "Approved";
                            });

                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text("Rental Approved"),
                              ),
                            );
                          },

                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.green,
                          ),

                          child: const Text(
                            "Approve",
                            style: TextStyle(
                              color: Colors.white,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
              ],
            ),
          );
        },
      ),
       bottomNavigationBar: const VendorBottomNavbar(
        currentIndex: 2,
      ),
    );
  }
}