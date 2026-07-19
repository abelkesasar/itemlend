import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';

class RegisterPage extends StatefulWidget {
  const RegisterPage({super.key});

  @override
  State<RegisterPage> createState() => _RegisterPageState();
}

class _RegisterPageState extends State<RegisterPage> {
  // Controller baru untuk field tambahan
  final usernameController = TextEditingController();
  final emailController = TextEditingController();
  final phoneController = TextEditingController();
  final addressController = TextEditingController();
  final passwordController = TextEditingController();
  
  String selectedRole = "User"; // Default sesuai mockup

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text("ItemLend", style: TextStyle(fontWeight: FontWeight.bold, color: Colors.blue)),
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.blue),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text("Buat Akun Baru", style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
            const Text("Isi data diri kamu dengan lengkap", style: TextStyle(color: Colors.grey)),
            const SizedBox(height: 20),

            _buildLabel("Username"),
            _buildTextField(usernameController, "teri"),
            
            _buildLabel("Email"),
            _buildTextField(emailController, "email@kamu.com"),
            
            _buildLabel("Nomor WhatsApp"),
            _buildTextField(phoneController, "08xxxxxxxxxx"),
            
            _buildLabel("Alamat"),
            _buildTextField(addressController, "Jl. Contoh No. 1, Kota"),

            _buildLabel("Daftar Sebagai"),
            _buildRoleToggle(),
            const SizedBox(height: 15),

            // Row untuk Upload File
            Row(
              children: [
                Expanded(child: _buildUploadField("Upload KTP")),
                const SizedBox(width: 15),
                Expanded(child: _buildUploadField("Upload KTH")),
              ],
            ),
            const SizedBox(height: 15),

            _buildLabel("Password"),
            _buildTextField(passwordController, "******", obscure: true),
            
            const SizedBox(height: 30),
            
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF3D5AFE), // Warna biru ala mockup
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                onPressed: () {
                  // Tambahkan logika register di sini
                },
                child: const Text("Daftar Sekarang", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
              ),
            ),
            
            Center(
              child: TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text("Sudah punya akun? Login"),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // Widget Pembantu agar kode rapi
  Widget _buildLabel(String text) => Padding(
    padding: const EdgeInsets.only(bottom: 8, top: 10),
    child: Text(text, style: const TextStyle(fontWeight: FontWeight.w600)),
  );

  Widget _buildTextField(TextEditingController controller, String hint, {bool obscure = false}) => TextField(
    controller: controller,
    obscureText: obscure,
    decoration: InputDecoration(
      hintText: hint,
      filled: true,
      fillColor: Colors.blue.shade50.withOpacity(0.5),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
    ),
  );

  Widget _buildRoleToggle() => Container(
    padding: const EdgeInsets.all(4),
    decoration: BoxDecoration(color: Colors.blue.shade50, borderRadius: BorderRadius.circular(12)),
    child: Row(
      children: [
        _roleButton("User", selectedRole == "User"),
        _roleButton("Vendor", selectedRole == "Vendor"),
      ],
    ),
  );

  Widget _roleButton(String role, bool isSelected) => Expanded(
    child: GestureDetector(
      onTap: () => setState(() => selectedRole = role),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: isSelected ? Colors.white : Colors.transparent,
          borderRadius: BorderRadius.circular(8),
          border: isSelected ? Border.all(color: Colors.blue) : null,
        ),
        alignment: Alignment.center,
        child: Text(role, style: TextStyle(fontWeight: FontWeight.bold, color: isSelected ? Colors.blue : Colors.grey)),
      ),
    ),
  );

  Widget _buildUploadField(String label) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
      const SizedBox(height: 5),
      OutlinedButton(
        onPressed: () {},
        style: OutlinedButton.styleFrom(minimumSize: const Size(double.infinity, 45)),
        child: const Text("Pilih File"),
      ),
    ],
  );
}