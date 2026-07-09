import 'package:flutter/material.dart';

class NotificationPage extends StatelessWidget {
  const NotificationPage({super.key});

  @override
  Widget build(BuildContext context) {
    final List<Map<String, dynamic>> notifications = [
      {
        "title": "Rental Approved",
        "message": "Your Sony Camera rental has been approved.",
        "time": "5 min ago",
        "icon": Icons.check_circle,
        "color": Colors.green,
      },
      {
        "title": "Waiting Approval",
        "message": "Your Lighting Set rental is waiting for confirmation.",
        "time": "30 min ago",
        "icon": Icons.access_time,
        "color": Colors.orange,
      },
      {
        "title": "Rental Completed",
        "message": "Thank you for using Item Lend.",
        "time": "Yesterday",
        "icon": Icons.done_all,
        "color": Colors.blue,
      },
      {
        "title": "Rental Rejected",
        "message": "Your Projector rental request was rejected.",
        "time": "2 days ago",
        "icon": Icons.cancel,
        "color": Colors.red,
      },
    ];

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),

      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        title: const Text(
          "Notifications",
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
      ),

      body: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: notifications.length,
        itemBuilder: (context, index) {
          final notification = notifications[index];

          return Container(
            margin: const EdgeInsets.only(bottom: 16),
            padding: const EdgeInsets.all(16),

            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(18),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.05),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),

            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [

                CircleAvatar(
                  radius: 24,
                  backgroundColor:
                      (notification["color"] as Color).withValues(alpha: 0.15),

                  child: Icon(
                    notification["icon"] as IconData,
                    color: notification["color"] as Color,
                  ),
                ),

                const SizedBox(width: 16),

                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [

                      Text(
                        notification["title"].toString(),
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 17,
                        ),
                      ),

                      const SizedBox(height: 6),

                      Text(
                        notification["message"].toString(),
                        style: TextStyle(
                          color: Colors.grey.shade700,
                        ),
                      ),

                      const SizedBox(height: 8),

                      Text(
                        notification["time"].toString(),
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey.shade500,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}