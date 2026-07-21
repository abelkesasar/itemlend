import 'package:flutter/material.dart';

class AddItemPage extends StatefulWidget {
  const AddItemPage({super.key});

  @override
  State<AddItemPage> createState() => _AddItemPageState();
}

class _AddItemPageState extends State<AddItemPage> {
  final nameController = TextEditingController();
  final priceController = TextEditingController();
  final descController = TextEditingController();
  
  String selectedCategory = 'Elektronik'; 

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text("Tambah Barang", style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF2D3142))),
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        iconTheme: const IconThemeData(color: Color(0xFF3D5AFE)),
        surfaceTintColor: Colors.transparent,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Kotak Upload Foto (Modern, mirip area Drag & Drop)
            GestureDetector(
              onTap: () {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Membuka galeri...")));
              },
              child: Container(
                height: 160,
                width: double.infinity,
                decoration: BoxDecoration(
                  color: const Color(0xFFF8F9FA),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: Colors.grey.shade300, width: 2), // Outline tegas
                ),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.blue.shade50,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.add_a_photo_rounded, size: 32, color: Color(0xFF3D5AFE)),
                    ),
                    const SizedBox(height: 16),
                    const Text("Ketuk untuk upload foto", style: TextStyle(color: Color(0xFF2D3142), fontWeight: FontWeight.bold)),
                    const SizedBox(height: 4),
                    Text("Format: JPG, PNG (Maks. 2MB)", style: TextStyle(color: Colors.grey, fontSize: 12)),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 32),

            _buildLabel("Nama Barang"),
            _buildTextField(nameController, "Contoh: Kamera Canon EOS 700D"),

            _buildLabel("Kategori"),
            _buildDropdown(),

            _buildLabel("Harga Sewa per Hari (Rp)"),
            _buildTextField(priceController, "Contoh: 50000", isNumber: true, prefix: "Rp "),

            _buildLabel("Deskripsi Barang"),
            _buildTextField(descController, "Jelaskan kondisi, kelengkapan, dan syarat sewa...", maxLines: 4),

            const SizedBox(height: 40),

            // Tombol Simpan
            SizedBox(
              width: double.infinity,
              height: 55, // Sedikit lebih tinggi agar premium
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF3D5AFE),
                  elevation: 0,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                ),
                onPressed: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text("Barang berhasil ditambahkan!"), backgroundColor: Colors.green),
                  );
                  Navigator.pop(context); 
                },
                child: const Text("Simpan Barang", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // Widget Pembantu untuk Label
  Widget _buildLabel(String text) => Padding(
    padding: const EdgeInsets.only(bottom: 8, top: 20),
    child: Text(text, style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF2D3142))),
  );

  // Widget Pembantu untuk TextField
  Widget _buildTextField(TextEditingController controller, String hint, {int maxLines = 1, bool isNumber = false, String? prefix}) => TextField(
    controller: controller,
    maxLines: maxLines,
    keyboardType: isNumber ? TextInputType.number : TextInputType.text,
    decoration: InputDecoration(
      hintText: hint,
      hintStyle: TextStyle(color: Colors.grey.shade400),
      prefixText: prefix, // Menambahkan teks Rp di depan form harga
      prefixStyle: const TextStyle(color: Color(0xFF2D3142), fontWeight: FontWeight.bold),
      filled: true,
      fillColor: const Color(0xFFF8F9FA), // Latar abu-abu sangat terang
      contentPadding: const EdgeInsets.all(16),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide(color: Colors.grey.shade200)),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide(color: Colors.grey.shade200)),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: Color(0xFF3D5AFE), width: 1.5)),
    ),
  );

  // Widget Pembantu untuk Dropdown Kategori
  Widget _buildDropdown() => Container(
    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
    decoration: BoxDecoration(
      color: const Color(0xFFF8F9FA),
      borderRadius: BorderRadius.circular(16),
      border: Border.all(color: Colors.grey.shade200),
    ),
    child: DropdownButtonHideUnderline(
      child: DropdownButton<String>(
        value: selectedCategory,
        isExpanded: true,
        dropdownColor: Colors.white,
        icon: const Icon(Icons.keyboard_arrow_down_rounded, color: Colors.grey),
        style: const TextStyle(color: Color(0xFF2D3142), fontSize: 16),
        items: ['Elektronik', 'Pakaian', 'Otomotif', 'Peralatan', 'Lainnya'].map((String value) {
          return DropdownMenuItem<String>(
            value: value,
            child: Text(value),
          );
        }).toList(),
        onChanged: (newValue) {
          setState(() {
            selectedCategory = newValue!;
          });
        },
      ),
    ),
  );
}