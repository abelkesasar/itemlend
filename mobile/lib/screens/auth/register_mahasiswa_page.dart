import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

class RegisterMahasiswaPage extends StatefulWidget {
  const RegisterMahasiswaPage({super.key});

  @override
  State<RegisterMahasiswaPage> createState() =>
      _RegisterMahasiswaPageState();
}

class _RegisterMahasiswaPageState extends State<RegisterMahasiswaPage> {
  File? ktmImage;

final ImagePicker picker = ImagePicker();

Future<void> pickKtm() async {
  final XFile? image = await picker.pickImage(
    source: ImageSource.gallery,
  );

  if (image != null) {
    setState(() {
      ktmImage = File(image.path);
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
        title: const Text("Student Registration"),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            buildInput("Full Name"),
            buildInput("NIM"),
            buildInput("Major / Department"),
            buildInput("Email"),
            buildInput(
              "Phone Number",
              keyboardType: TextInputType.phone,
            ),

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
                    Icons.badge_outlined,
                    size: 50,
                    color: Colors.blue,
                  ),
                  const SizedBox(height: 10),
                  const Text(
                    "Upload KTM",
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 10),
                  if (ktmImage != null)
  Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: Image.file(
        ktmImage!,
        height: 180,
        width: double.infinity,
        fit: BoxFit.cover,
      ),
    ),
  ),

ElevatedButton.icon(
  onPressed: pickKtm,
  icon: const Icon(Icons.upload_file),
  label: Text(
    ktmImage == null
        ? "Choose KTM"
        : "Change KTM",
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
      '/home',
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