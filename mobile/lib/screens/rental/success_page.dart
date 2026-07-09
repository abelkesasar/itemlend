import 'package:flutter/material.dart';

class SuccessPage extends StatelessWidget {
  const SuccessPage({super.key});

  @override
  Widget build(BuildContext context) {

    return Scaffold(

      backgroundColor: Colors.white,

      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),

          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,

            children: [

              Container(
                width: 140,
                height: 140,

                decoration: BoxDecoration(
                  color: Colors.green.shade100,
                  shape: BoxShape.circle,
                ),

                child: Icon(
                  Icons.check,
                  size: 80,
                  color: Colors.green.shade700,
                ),
              ),

              const SizedBox(height: 40),

              const Text(
                "Payment Successful!",
                style: TextStyle(
                  fontSize: 30,
                  fontWeight: FontWeight.bold,
                ),
              ),

              const SizedBox(height: 15),

              const Text(
                "Your rental request has been successfully submitted.",
                textAlign: TextAlign.center,

                style: TextStyle(
                  fontSize: 18,
                  color: Colors.grey,
                  height: 1.5,
                ),
              ),

              const SizedBox(height: 50),

SizedBox(
  width: double.infinity,
  height: 60,
  child: ElevatedButton.icon(
    onPressed: () {
      Navigator.pushNamedAndRemoveUntil(
        context,
        '/rental-history',
        (route) => false,
      );
    },
    icon: const Icon(
      Icons.receipt_long,
      color: Colors.white,
    ),
    label: const Text(
      "View Rental History",
      style: TextStyle(
        fontSize: 18,
        color: Colors.white,
        fontWeight: FontWeight.bold,
      ),
    ),
    style: ElevatedButton.styleFrom(
      backgroundColor: Colors.blue,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
      ),
    ),
  ),
),

const SizedBox(height: 14),

SizedBox(
  width: double.infinity,
  height: 55,
  child: OutlinedButton(
    onPressed: () {
      Navigator.pushNamedAndRemoveUntil(
        context,
        '/home',
        (route) => false,
      );
    },
    style: OutlinedButton.styleFrom(
      side: const BorderSide(color: Colors.blue),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
      ),
    ),
    child: const Text(
      "Back to Home",
      style: TextStyle(
        color: Colors.blue,
        fontSize: 18,
        fontWeight: FontWeight.w600,
      ),
    ),
  ),
),
            ],
          ),
        ),
      ),
    );
  }
}