import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../services/api_service.dart';

const Color _brandColor = Color(0xFF3D4BFF);

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _usernameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _alamatController = TextEditingController();
  final _nomorWaController = TextEditingController();

  File? _ktpFile;
  File? _ktmFile;
  bool _isLoading = false;
  bool _obscurePassword = true;

  final ImagePicker _picker = ImagePicker();

  Future<void> _pickImage(bool isKtp) async {
    final picked = await _picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 80,
    );
    if (picked != null) {
      setState(() {
        if (isKtp) {
          _ktpFile = File(picked.path);
        } else {
          _ktmFile = File(picked.path);
        }
      });
    }
  }

  Future<void> _handleRegister() async {
    final username = _usernameController.text.trim();
    final email = _emailController.text.trim();
    final password = _passwordController.text;
    final alamat = _alamatController.text.trim();
    final nomorWa = _nomorWaController.text.trim();

    if (username.isEmpty || email.isEmpty || password.isEmpty) {
      _showMessage('Username, email, dan password wajib diisi.');
      return;
    }

    if (_ktpFile == null || _ktmFile == null) {
      _showMessage('Foto KTP dan KTM wajib diunggah.');
      return;
    }

    setState(() => _isLoading = true);

    final result = await ApiService.register(
      username: username,
      email: email,
      password: password,
      alamat: alamat,
      nomorWa: nomorWa,
      ktpFile: _ktpFile!,
      ktmFile: _ktmFile!,
    );

    setState(() => _isLoading = false);

    if (!mounted) return;

    _showMessage(result['message'] ?? 'Terjadi kesalahan.');

    if (result['success'] == true) {
      Navigator.pop(context); // balik ke LoginScreen setelah berhasil
    }
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  @override
  void dispose() {
    _usernameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _alamatController.dispose();
    _nomorWaController.dispose();
    super.dispose();
  }

  InputDecoration _inputDecoration(String hint) {
    return InputDecoration(
      hintText: hint,
      hintStyle: TextStyle(color: Colors.grey[400], fontSize: 13.5),
      filled: true,
      fillColor: Colors.grey[100],
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: BorderSide.none,
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: _brandColor, width: 1.5),
      ),
    );
  }

  Widget _buildLabel(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 5),
      child: Text(text, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: Color(0xFF374151))),
    );
  }

  Widget _buildFilePicker(String label, File? file, VoidCallback onTap) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildLabel(label),
        InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(10),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              border: Border.all(color: Colors.grey[300]!, style: BorderStyle.solid),
              borderRadius: BorderRadius.circular(10),
              color: Colors.grey[50],
            ),
            child: Row(
              children: [
                Icon(Icons.upload_file_outlined, size: 18, color: Colors.grey[500]),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    file != null ? file.path.split('/').last : 'Pilih file',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(fontSize: 12.5, color: file != null ? Colors.black87 : Colors.grey[500]),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF3D4BFF), Color(0xFF6366F1), Color(0xFF8B5CF6)],
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // --- TOP NAV ---
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: const [
                        Icon(Icons.work_outline, color: Colors.white, size: 20),
                        SizedBox(width: 6),
                        Text('ItemLend', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 18)),
                      ],
                    ),
                    TextButton(
                      onPressed: () => Navigator.pop(context),
                      style: TextButton.styleFrom(
                        side: const BorderSide(color: Colors.white54),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                        foregroundColor: Colors.white,
                      ),
                      child: const Text('Login'),
                    ),
                  ],
                ),
                const SizedBox(height: 28),

                // --- HERO TEXT ---
                const Text(
                  'Bergabung dengan ItemLend 🚀',
                  style: TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800, height: 1.25),
                ),
                const SizedBox(height: 10),
                const Text(
                  'Buat akun dan mulai sewakan barang atau cari barang yang kamu butuhkan dengan aman dan mudah.',
                  style: TextStyle(color: Colors.white70, fontSize: 14, height: 1.5),
                ),
                const SizedBox(height: 20),
                _buildFeatureRow(Icons.shield_outlined, 'Transaksi aman & terverifikasi'),
                const SizedBox(height: 10),
                _buildFeatureRow(Icons.people_outline, 'Komunitas penyewa terpercaya'),
                const SizedBox(height: 10),
                _buildFeatureRow(Icons.attach_money, 'Hasilkan uang dari barang idle'),

                const SizedBox(height: 28),

                // --- FORM CARD ---
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(22),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [
                      BoxShadow(color: Colors.black.withOpacity(0.15), blurRadius: 24, offset: const Offset(0, 12)),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Buat Akun Baru', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: Color(0xFF1A1D2E))),
                      const SizedBox(height: 2),
                      const Text('Isi data diri kamu dengan lengkap', style: TextStyle(fontSize: 12.5, color: Colors.grey)),
                      const SizedBox(height: 20),

                      _buildLabel('Username'),
                      TextField(controller: _usernameController, decoration: _inputDecoration('contoh: johndoe')),
                      const SizedBox(height: 14),

                      _buildLabel('Email'),
                      TextField(
                        controller: _emailController,
                        keyboardType: TextInputType.emailAddress,
                        decoration: _inputDecoration('email@kamu.com'),
                      ),
                      const SizedBox(height: 14),

                      _buildLabel('Nomor WhatsApp'),
                      TextField(
                        controller: _nomorWaController,
                        keyboardType: TextInputType.phone,
                        decoration: _inputDecoration('08xxxxxxxxxx'),
                      ),
                      const SizedBox(height: 14),

                      _buildLabel('Alamat'),
                      TextField(
                        controller: _alamatController,
                        maxLines: 2,
                        decoration: _inputDecoration('Jl. Contoh No. 1, Kota'),
                      ),
                      const SizedBox(height: 16),
                      Divider(color: Colors.grey[200]),
                      const SizedBox(height: 4),

                      _buildFilePicker('Upload KTP', _ktpFile, () => _pickImage(true)),
                      const SizedBox(height: 14),
                      _buildFilePicker('Upload KTM', _ktmFile, () => _pickImage(false)),

                      const SizedBox(height: 16),
                      Divider(color: Colors.grey[200]),
                      const SizedBox(height: 4),

                      _buildLabel('Password'),
                      TextField(
                        controller: _passwordController,
                        obscureText: _obscurePassword,
                        decoration: _inputDecoration('Min. 8 karakter').copyWith(
                          suffixIcon: IconButton(
                            icon: Icon(
                              _obscurePassword ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                              size: 18,
                            ),
                            onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                          ),
                        ),
                      ),
                      const SizedBox(height: 18),

                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton.icon(
                          onPressed: _isLoading ? null : _handleRegister,
                          icon: _isLoading
                              ? const SizedBox(
                                  height: 16,
                                  width: 16,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : const Icon(Icons.person_add_alt_1, size: 18),
                          label: Text(_isLoading ? 'Memproses...' : 'Daftar Sekarang'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: _brandColor,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700),
                          ),
                        ),
                      ),
                      const SizedBox(height: 14),

                      Center(
                        child: RichText(
                          text: TextSpan(
                            style: const TextStyle(color: Colors.grey, fontSize: 13),
                            children: [
                              const TextSpan(text: 'Sudah punya akun? '),
                              WidgetSpan(
                                alignment: PlaceholderAlignment.middle,
                                child: GestureDetector(
                                  onTap: () => Navigator.pop(context),
                                  child: const Text(
                                    'Login',
                                    style: TextStyle(color: _brandColor, fontWeight: FontWeight.w600),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildFeatureRow(IconData icon, String text) {
    return Row(
      children: [
        Container(
          width: 34,
          height: 34,
          decoration: BoxDecoration(
            color: Colors.white.withOpacity(0.15),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(icon, color: Colors.white, size: 18),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Text(text, style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w500)),
        ),
      ],
    );
  }
}