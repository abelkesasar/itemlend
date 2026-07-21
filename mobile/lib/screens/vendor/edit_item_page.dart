import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../models/item_model.dart';
import '../../providers/item_provider.dart';

class EditItemPage extends StatefulWidget {
  const EditItemPage({super.key});

  @override
  State<EditItemPage> createState() => _EditItemPageState(); // Ditambah <EditItemPage>
}

class _EditItemPageState extends State<EditItemPage> { // Ditambah <EditItemPage>
  final _formKey = GlobalKey<FormState>(); // <--- PENYEBAB ERROR VALIDATE (Ditambah <FormState>)

  final namaController = TextEditingController();
  final hargaController = TextEditingController();
  final deskripsiController = TextEditingController();

  final ImagePicker _picker = ImagePicker();
  File? imageFile;

  String kategori = "Dokumentasi";

  final List<String> kategoriList = [ // Ditambah <String>
    "Dokumentasi",
    "Audio & Sound",
    "Visual & Presentasi",
    "Komunikasi",
    "Logistik Acara",
    "Lighting & Dekorasi",
    "Konsumsi",
    "Kostum & Properti",
  ];

  bool isLoaded = false;

  Future<void> pickImage() async { // Ditambah <void>
    final XFile? picked = await _picker.pickImage(source: ImageSource.gallery);

    if (picked != null) {
      setState(() {
        imageFile = File(picked.path);
      });
    }
  }

  @override
  void dispose() {
    namaController.dispose();
    hargaController.dispose();
    deskripsiController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final provider = Provider.of<ItemProvider>(context); // Ditambah <ItemProvider>
    final index = ModalRoute.of(context)!.settings.arguments as int;
    final item = provider.getItem(index);

    if (!isLoaded) {
      namaController.text = item.namaBarang;
      hargaController.text = item.harga.toString(); 
      deskripsiController.text = item.deskripsi;
      
      if (kategoriList.contains(item.kategori)) {
        kategori = item.kategori; 
      } else {
        kategori = kategoriList.first;
      }
      
      imageFile = File(item.gambar); 
      isLoaded = true;
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: const Text("Edit Barang", style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF2D3142),
        elevation: 0,
        centerTitle: true,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: GestureDetector(
                  onTap: pickImage,
                  child: Container(
                    width: 170,
                    height: 170,
                    decoration: BoxDecoration(
                      color: const Color(0xFFEEF2FF),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: Colors.grey.shade300, width: 2, style: BorderStyle.solid),
                    ),
                    child: imageFile == null
                        ? const Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.add_a_photo, size: 50, color: Color(0xFF3D5AFE)),
                              SizedBox(height: 8),
                              Text("Ganti Foto", style: TextStyle(color: Color(0xFF3D5AFE), fontWeight: FontWeight.bold))
                            ],
                          )
                        : ClipRRect(
                            borderRadius: BorderRadius.circular(18),
                            child: Image.file(
                              imageFile!,
                              fit: BoxFit.cover,
                            ),
                          ),
                  ),
                ),
              ),
              const SizedBox(height: 30),
              TextFormField(
                controller: namaController,
                decoration: InputDecoration(
                  labelText: "Nama Barang",
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
                  filled: true,
                  fillColor: Colors.white,
                ),
                validator: (value) => value!.isEmpty ? "Wajib diisi" : null,
              ),
              const SizedBox(height: 20),
              DropdownButtonFormField<String>( // <--- PENYEBAB ERROR VALUE (Ditambah <String>)
                value: kategori,
                decoration: InputDecoration(
                  labelText: "Kategori",
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
                  filled: true,
                  fillColor: Colors.white,
                ),
                items: kategoriList.map((e) {
                  return DropdownMenuItem<String>( // Ditambah <String>
                    value: e,
                    child: Text(e),
                  );
                }).toList(),
                onChanged: (value) {
                  setState(() {
                    kategori = value!;
                  });
                },
              ),
              const SizedBox(height: 20),
              TextFormField(
                controller: hargaController,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(
                  labelText: "Harga Sewa / Hari (Rp)",
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
                  filled: true,
                  fillColor: Colors.white,
                ),
                validator: (value) => value!.isEmpty ? "Wajib diisi" : null,
              ),
              const SizedBox(height: 20),
              TextFormField(
                controller: deskripsiController,
                maxLines: 4,
                decoration: InputDecoration(
                  labelText: "Deskripsi",
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
                  filled: true,
                  fillColor: Colors.white,
                ),
                validator: (value) => value!.isEmpty ? "Wajib diisi" : null,
              ),
              const SizedBox(height: 30),
              SizedBox(
                width: double.infinity,
                height: 55,
                child: ElevatedButton(
                  onPressed: () {
                    // Sekarang validate() akan dikenali!
                    if (_formKey.currentState!.validate()) {
                      provider.updateItem(
                        index,
                        ItemModel(
                          id: item.id,
                          namaBarang: namaController.text,
                          kategori: kategori,
                          deskripsi: deskripsiController.text,
                          harga: int.tryParse(hargaController.text) ?? 0,
                          gambar: imageFile!.path,
                          lokasi: item.lokasi, 
                        ),
                      );
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text("Barang berhasil diperbarui! 🎉"),
                          backgroundColor: Colors.green,
                        ),
                      );
                      Navigator.pop(context);
                    }
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF3D5AFE),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                  ),
                  child: const Text(
                    "Simpan Perubahan",
                    style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
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