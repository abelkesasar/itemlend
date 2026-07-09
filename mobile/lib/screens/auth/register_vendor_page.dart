import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

class RegisterVendorPage extends StatefulWidget {
  const RegisterVendorPage({super.key});

  @override
  State<RegisterVendorPage> createState() =>
      _RegisterVendorPageState();
}

class _RegisterVendorPageState extends State<RegisterVendorPage> {
  File? ktpImage;

final ImagePicker picker = ImagePicker();

Future<void> pickKtp() async {
  final XFile? image = await picker.pickImage(
    source: ImageSource.gallery,
  );

  if (image != null) {
    setState(() {
      ktpImage = File(image.path);
    });
  }
}

  Widget buildInput(String hint,
      {bool obscureText = false, TextInputType? keyboardType}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextField(
        obscureText: obscureText,
        keyboardType: keyboardType,
        decoration: InputDecoration(
          hintText: hint,
          filled: true,
          fillColor: Colors.white,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide.none,
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.blue.shade50,
      appBar: AppBar(
        title: const Text("Vendor Registration"),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            buildInput("Vendor Name"),
            buildInput("Owner Name"),
            buildInput("Email"),
            buildInput(
              "Phone Number",
              keyboardType: TextInputType.phone,
            ),
            buildInput("Address"),

            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
              ),
              child: Column(
                children: [
                  const Icon(
                    Icons.credit_card,
                    size: 50,
                    color: Colors.blue,
                  ),
                  const SizedBox(height: 10),
                  const Text(
                    "Upload KTP",
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 10),
                  if (ktpImage != null)
  Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: Image.file(
        ktpImage!,
        height: 180,
        width: double.infinity,
        fit: BoxFit.cover,
      ),
    ),
  ),

ElevatedButton.icon(
  onPressed: pickKtp,
  icon: const Icon(Icons.upload_file),
  label: Text(
    ktpImage == null
        ? "Choose KTP"
        : "Change KTP",
  ),
),
                ],
              ),
            ),

            const SizedBox(height: 16),

            buildInput(
              "Password",
              obscureText: true,
            ),

            buildInput(
              "Confirm Password",
              obscureText: true,
            ),

            const SizedBox(height: 20),

            SizedBox(
              width: double.infinity,
              height: 55,
              child: ElevatedButton(
                onPressed: () {
  ScaffoldMessenger.of(context).showSnackBar(
    const SnackBar(
      content: Text("Registration successful"),
      duration: Duration(seconds: 1),
    ),
  );

  Future.delayed(const Duration(seconds: 1), () {
    Navigator.pushReplacementNamed(
      context,
      '/vendor-dashboard',
    );
  });
},
                child: const Text("Register"),
              ),
            ),
          ],
        ),
      ),
    );
  }
}