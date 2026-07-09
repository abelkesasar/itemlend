import 'package:flutter/material.dart';

class VendorBottomNavbar extends StatelessWidget {
  final int currentIndex;

  const VendorBottomNavbar({
    super.key,
    required this.currentIndex,
  });

  @override
  Widget build(BuildContext context) {
    return BottomNavigationBar(
      currentIndex: currentIndex,
      type: BottomNavigationBarType.fixed,
      selectedItemColor: const Color(0xFF2563EB),
      unselectedItemColor: Colors.grey,

      onTap: (index) {
        if (index == currentIndex) return;

        switch (index) {
          case 0:
            Navigator.pushReplacementNamed(
              context,
              '/vendor-dashboard',
            );
            break;

          case 1:
            Navigator.pushReplacementNamed(
              context,
              '/my-items',
            );
            break;

          case 2:
            Navigator.pushReplacementNamed(
              context,
              '/rental-request',
            );
            break;

          case 3:
            Navigator.pushReplacementNamed(
              context,
              '/vendor-profile',
            );
            break;
        }
      },

      items: const [
        BottomNavigationBarItem(
          icon: Icon(Icons.dashboard_outlined),
          label: "Dashboard",
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.inventory_2_outlined),
          label: "My Items",
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.assignment_outlined),
          label: "Requests",
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.person_outline),
          label: "Profile",
        ),
      ],
    );
  }
}