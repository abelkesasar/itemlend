import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../services/api_service.dart';

const Color _brandColor = Color(0xFF3D4BFF);

class TambahBarangScreen extends StatefulWidget {
  const TambahBarangScreen({super.key});

  @override
  State<TambahBarangScreen> createState() => _TambahBarangScreenState();
}

class _TambahBarangScreenState extends State<TambahBarangScreen> {
  final _namaController = TextEditingController();
  final _deskripsiController = TextEditingController();
  final _hargaController = TextEditingController();
  final _stokController = TextEditingController(text: '1');
  final _kategoriController = TextEditingController();
  final _lokasiController = TextEditingController();

  final List<File> _gambarFiles = [];
  bool _isLoading = false;

  final ImagePicker _picker = ImagePicker();

  Future<void> _pickImages() async {
    final picked = await _picker.pickMultiImage(imageQuality: 80);
    if (picked.isNotEmpty) {
      setState(() {
        _gambarFiles.addAll(picked.map((x) => File(x.path)));
      });
    }
  }

  void _removeImage(int index) {
    setState(() => _gambarFiles.removeAt(index));
  }

  Future<void> _handleSubmit() async {
    final nama = _namaController.text.trim();
    final harga = int.tryParse(_hargaController.text.trim()) ?? 0;

    if (nama.isEmpty || harga <= 0) {
      _showMessage('Nama barang dan harga wajib diisi.');
      return;
    }

    setState(() => _isLoading = true);

    final result = await ApiService.tambahBarang(
      namaBarang: nama,
      deskripsi: _deskripsiController.text.trim(),
      harga: harga,
      stok: int.tryParse(_stokController.text.trim()) ?? 1,
      kategori: _kategoriController.text.trim(),
      lokasi: _lokasiController.text.trim(),
      gambarFiles: _gambarFiles,
    );

    setState(() => _isLoading = false);

    if (!mounted) return;

    _showMessage(result['message'] ?? 'Terjadi kesalahan.');

    if (result['success'] == true) {
      Navigator.pop(context, true); // balik ke Home, bisa dipakai buat trigger refresh
    }
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  @override
  void dispose() {
    _namaController.dispose();
    _deskripsiController.dispose();
    _hargaController.dispose();
    _stokController.dispose();
    _kategoriController.dispose();
    _lokasiController.dispose();
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Tambah Barang'),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 1,
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildLabel('Nama Barang'),
              TextField(controller: _namaController, decoration: _inputDecoration('contoh: Tenda Camping 4 Orang')),
              const SizedBox(height: 14),

              _buildLabel('Kategori'),
              TextField(controller: _kategoriController, decoration: _inputDecoration('contoh: Camping, Elektronik, dll')),
              const SizedBox(height: 14),

              _buildLabel('Deskripsi'),
              TextField(
                controller: _deskripsiController,
                maxLines: 3,
                decoration: _inputDecoration('Ceritakan kondisi & detail barang'),
              ),
              const SizedBox(height: 14),

              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildLabel('Harga / hari (Rp)'),
                        TextField(
                          controller: _hargaController,
                          keyboardType: TextInputType.number,
                          decoration: _inputDecoration('50000'),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildLabel('Stok'),
                        TextField(
                          controller: _stokController,
                          keyboardType: TextInputType.number,
                          decoration: _inputDecoration('1'),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),

              _buildLabel('Lokasi'),
              TextField(controller: _lokasiController, decoration: _inputDecoration('contoh: Bandung')),
              const SizedBox(height: 20),

              _buildLabel('Foto Barang (bisa lebih dari 1)'),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  ..._gambarFiles.asMap().entries.map((entry) {
                    final index = entry.key;
                    final file = entry.value;
                    return Stack(
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(10),
                          child: Image.file(file, width: 90, height: 90, fit: BoxFit.cover),
                        ),
                        Positioned(
                          top: -6,
                          right: -6,
                          child: InkWell(
                            onTap: () => _removeImage(index),
                            child: Container(
                              padding: const EdgeInsets.all(2),
                              decoration: const BoxDecoration(color: Colors.black54, shape: BoxShape.circle),
                              child: const Icon(Icons.close, size: 14, color: Colors.white),
                            ),
                          ),
                        ),
                      ],
                    );
                  }),
                  InkWell(
                    onTap: _pickImages,
                    child: Container(
                      width: 90,
                      height: 90,
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.grey[300]!),
                        borderRadius: BorderRadius.circular(10),
                        color: Colors.grey[50],
                      ),
                      child: const Icon(Icons.add_photo_alternate_outlined, color: Colors.grey),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 28),

              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _handleSubmit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _brandColor,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700),
                  ),
                  child: _isLoading
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Text('Daftarkan Barang'),
                ),
              ),
              const SizedBox(height: 8),
              const Text(
                'Barang akan tampil di Home setelah disetujui admin.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 12, color: Colors.grey),
              ),
            ],
          ),
        ),
      ),
    );
  }
}