import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../services/api_service.dart';
import 'tambah_barang_screen.dart';

const Color _brandColor = Color(0xFF3D4BFF);

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _usernameController = TextEditingController();
  final _emailController = TextEditingController();
  final _nomorWaController = TextEditingController();
  final _alamatController = TextEditingController();
  final _namaPenyediaController = TextEditingController();
  final _nomorRekeningController = TextEditingController();
  final _namaPemilikRekeningController = TextEditingController();
  final _passwordController = TextEditingController();

  bool _isLoading = true;
  bool _isSaving = false;
  bool _metodePembayaranLengkap = false;

  File? _fotoProfilFile;
  File? _fotoQrisFile;

  final ImagePicker _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  Future<void> _loadProfile() async {
    setState(() => _isLoading = true);

    final result = await ApiService.getProfile();

    if (!mounted) return;

    if (result['success'] == true) {
      final data = result['data'];
      _usernameController.text = data['username'] ?? '';
      _emailController.text = data['email'] ?? '';
      _nomorWaController.text = data['nomor_wa'] ?? '';
      _alamatController.text = data['alamat'] ?? '';
      _namaPenyediaController.text = data['nama_penyedia'] ?? '';
      _nomorRekeningController.text = data['nomor_rekening'] ?? '';
      _namaPemilikRekeningController.text = data['nama_pemilik_rekening'] ?? '';
      _metodePembayaranLengkap = data['metode_pembayaran_lengkap'] ?? false;
    } else {
      _showMessage(result['message'] ?? 'Gagal memuat profil.');
    }

    setState(() => _isLoading = false);
  }

  Future<void> _pickImage(bool isProfil) async {
    final picked = await _picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
    if (picked != null) {
      setState(() {
        if (isProfil) {
          _fotoProfilFile = File(picked.path);
        } else {
          _fotoQrisFile = File(picked.path);
        }
      });
    }
  }

  Future<void> _handleSave() async {
    setState(() => _isSaving = true);

    final result = await ApiService.updateProfile(
      username: _usernameController.text.trim(),
      email: _emailController.text.trim(),
      nomorWa: _nomorWaController.text.trim(),
      alamat: _alamatController.text.trim(),
      namaPenyedia: _namaPenyediaController.text.trim(),
      nomorRekening: _nomorRekeningController.text.trim(),
      namaPemilikRekening: _namaPemilikRekeningController.text.trim(),
      password: _passwordController.text.isNotEmpty ? _passwordController.text : null,
      fotoProfil: _fotoProfilFile,
      fotoQris: _fotoQrisFile,
    );

    if (!mounted) return;

    setState(() => _isSaving = false);
    _showMessage(result['message'] ?? 'Terjadi kesalahan.');

    if (result['success'] == true) {
      final lengkap = result['data']?['metode_pembayaran_lengkap'] ?? false;

      // Cek apakah metode pembayaran baru saja lengkap (sebelumnya belum, sekarang sudah)
      final baruLengkap = lengkap && !_metodePembayaranLengkap;

      setState(() => _metodePembayaranLengkap = lengkap);

      // Kalau baru saja lengkap pertama kali, lanjut ke Tambah Barang
      if (baruLengkap) {
        final tambahResult = await Navigator.push(
          context,
          MaterialPageRoute(builder: (_) => const TambahBarangScreen()),
        );
        if (!mounted) return;
        if (tambahResult == true) {
          Navigator.pop(context, true); // teruskan sinyal refresh ke Home
        }
      }
    }
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  void dispose() {
    _usernameController.dispose();
    _emailController.dispose();
    _nomorWaController.dispose();
    _alamatController.dispose();
    _namaPenyediaController.dispose();
    _nomorRekeningController.dispose();
    _namaPemilikRekeningController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  InputDecoration _inputDecoration(String hint) {
    return InputDecoration(
      hintText: hint,
      hintStyle: TextStyle(color: Colors.grey[400], fontSize: 13.5),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
    );
  }

  Widget _buildLabel(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 5),
      child: Text(text, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: Color(0xFF374151))),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Profil Saya')),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (!_metodePembayaranLengkap)
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(14),
                      margin: const EdgeInsets.only(bottom: 20),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFFF7E0),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: const Color(0xFFF5D78E)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.warning_amber_rounded, color: Color(0xFFB8860B), size: 20),
                          const SizedBox(width: 10),
                          const Expanded(
                            child: Text(
                              'Lengkapi metode pembayaran dulu sebelum bisa mendaftarkan barang.',
                              style: TextStyle(fontSize: 12.5, color: Color(0xFF7A5C00)),
                            ),
                          ),
                        ],
                      ),
                    ),

                  Center(
                    child: Column(
                      children: [
                        CircleAvatar(
                          radius: 40,
                          backgroundColor: _brandColor,
                          backgroundImage: _fotoProfilFile != null ? FileImage(_fotoProfilFile!) : null,
                          child: _fotoProfilFile == null
                              ? Text(
                                  _usernameController.text.isNotEmpty
                                      ? _usernameController.text.substring(0, 1).toUpperCase()
                                      : '?',
                                  style: const TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.bold),
                                )
                              : null,
                        ),
                        const SizedBox(height: 8),
                        TextButton(
                          onPressed: () => _pickImage(true),
                          child: const Text('Ganti Foto Profil'),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),

                  _buildLabel('Username'),
                  TextField(controller: _usernameController, decoration: _inputDecoration('Username')),
                  const SizedBox(height: 14),

                  _buildLabel('Email'),
                  TextField(controller: _emailController, decoration: _inputDecoration('Email')),
                  const SizedBox(height: 14),

                  _buildLabel('Nomor WhatsApp'),
                  TextField(controller: _nomorWaController, decoration: _inputDecoration('08xxxxxxxxxx')),
                  const SizedBox(height: 14),

                  _buildLabel('Alamat'),
                  TextField(controller: _alamatController, maxLines: 2, decoration: _inputDecoration('Alamat')),

                  const SizedBox(height: 20),
                  const Divider(),
                  const SizedBox(height: 8),
                  const Text('Metode Pembayaran', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.grey)),
                  const SizedBox(height: 14),

                  _buildLabel('Nama Penyedia (Bank/E-Wallet)'),
                  TextField(controller: _namaPenyediaController, decoration: _inputDecoration('contoh: BRI, DANA, QRIS')),
                  const SizedBox(height: 14),

                  _buildLabel('Nomor Rekening / No. HP / ID QRIS'),
                  TextField(controller: _nomorRekeningController, decoration: _inputDecoration('contoh: 1234567890')),
                  const SizedBox(height: 14),

                  _buildLabel('Nama Pemilik Rekening/Akun'),
                  TextField(controller: _namaPemilikRekeningController, decoration: _inputDecoration('contoh: Danil Saputra')),
                  const SizedBox(height: 14),

                  _buildLabel('Foto QRIS (opsional)'),
                  InkWell(
                    onTap: () => _pickImage(false),
                    child: Container(
                      height: 100,
                      width: 100,
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.grey[300]!),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: _fotoQrisFile != null
                          ? ClipRRect(
                              borderRadius: BorderRadius.circular(10),
                              child: Image.file(_fotoQrisFile!, fit: BoxFit.cover),
                            )
                          : const Icon(Icons.qr_code, color: Colors.grey),
                    ),
                  ),

                  const SizedBox(height: 20),
                  const Divider(),
                  const SizedBox(height: 8),

                  _buildLabel('Password Baru (kosongkan jika tidak ingin ganti)'),
                  TextField(
                    controller: _passwordController,
                    obscureText: true,
                    decoration: _inputDecoration('Password baru'),
                  ),

                  const SizedBox(height: 24),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _isSaving ? null : _handleSave,
                      icon: _isSaving
                          ? const SizedBox(
                              height: 16,
                              width: 16,
                              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                            )
                          : const Icon(Icons.save_outlined, size: 18),
                      label: Text(_isSaving ? 'Menyimpan...' : 'Simpan Perubahan'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: _brandColor,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}