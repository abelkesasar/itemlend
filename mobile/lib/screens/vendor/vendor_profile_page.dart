import 'package:flutter/material.dart';
import '../../widgets/vendor_bottom_navbar.dart';

class VendorProfilePage extends StatelessWidget {
  const VendorProfilePage({super.key});

  Widget menuTile(
    BuildContext context,
    IconData icon,
    String title,
    VoidCallback onTap,
  ) {
    return Card(
      elevation: 1,
      margin: const EdgeInsets.only(bottom: 14),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
      ),
      child: ListTile(
        leading: Icon(
          icon,
          color: const Color(0xFF2563EB),
        ),
        title: Text(title),
        trailing: const Icon(Icons.chevron_right),
        onTap: onTap,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
        return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),

      appBar: AppBar(
        title: const Text("Vendor Profile"),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 0,
      ),

      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),

        child: Column(
          children: [

            const CircleAvatar(
              radius: 50,
              backgroundColor: Color(0xFFEAF2FF),
              child: Icon(
                Icons.store,
                size: 60,
                color: Color(0xFF2563EB),
              ),
            ),

            const SizedBox(height: 16),

            const Text(
              "Danial Rental",
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.bold,
              ),
            ),

            const SizedBox(height: 5),

            const Text(
              "vendor@email.com",
              style: TextStyle(
                color: Colors.grey,
              ),
            ),

            const SizedBox(height: 30),

            menuTile(
              context,
              Icons.inventory_2_outlined,
              "My Items",
              () {
                Navigator.pushNamed(
                  context,
                  "/my-items",
                );
              },
            ),

            menuTile(
              context,
              Icons.assignment_outlined,
              "Rental Request",
              () {
                Navigator.pushNamed(
                  context,
                  "/rental-request",
                );
              },
            ),

            menuTile(
              context,
              Icons.edit_outlined,
              "Edit Profile",
              () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text(
                      "Coming Soon",
                    ),
                  ),
                );
              },
            ),

            menuTile(
              context,
              Icons.settings_outlined,
              "Settings",
              () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text(
                      "Coming Soon",
                    ),
                  ),
                );
              },
            ),

            const SizedBox(height: 25),

            SizedBox(
              width: double.infinity,
              height: 55,

              child: ElevatedButton.icon(
                onPressed: () {

                  Navigator.pushNamedAndRemoveUntil(
                    context,
                    "/login",
                    (route) => false,
                  );
                },

                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.red,
                ),

                icon: const Icon(
                  Icons.logout,
                  color: Colors.white,
                ),

                label: const Text(
                  "Logout",
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 17,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: const VendorBottomNavbar(
        currentIndex: 3,
      ),
    );
  }
}