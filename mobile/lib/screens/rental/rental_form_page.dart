import 'package:flutter/material.dart';

// Ubah nama class menjadi RentalFormPage
class RentalFormPage extends StatefulWidget {
  const RentalFormPage({super.key});

  @override
  State<RentalFormPage> createState() => _RentalFormPageState();
}

class _RentalFormPageState extends State<RentalFormPage> {
  // State untuk melacak metode pengambilan (0 = Ambil Sendiri, 1 = Diantar)
  int _selectedDeliveryMethod = 0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
// ... lanjutannya persis seperti kode yang sebelumnya ...
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: const Text("Formulir Sewa", style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF2D3142))),
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
            _buildSectionTitle("Ringkasan Barang"),
            // Card Ringkasan Barang
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 10, offset: const Offset(0, 4))
                ],
              ),
              child: Row(
                children: [
                  Container(
                    width: 60,
                    height: 60,
                    decoration: BoxDecoration(
                      color: const Color(0xFFEEF2FF),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.camera_alt_rounded, color: Color(0xFF3D5AFE)),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text("Sony Camera", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF2D3142))),
                        const SizedBox(height: 4),
                        Text("Vendor: John Doe", style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                        const SizedBox(height: 4),
                        const Text("Rp 150.000 / hari", style: TextStyle(color: Color(0xFF3D5AFE), fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 28),
            _buildSectionTitle("Durasi Sewa"),
            // Pemilihan Tanggal
            Row(
              children: [
                Expanded(child: _buildDateSelector("Tanggal Mulai", "12 Okt 2026")),
                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16),
                  child: Icon(Icons.arrow_forward_rounded, color: Colors.grey, size: 20),
                ),
                Expanded(child: _buildDateSelector("Tanggal Selesai", "14 Okt 2026")),
              ],
            ),
            const SizedBox(height: 12),
            Text("Total durasi: 2 Hari", style: TextStyle(color: Colors.blue.shade700, fontWeight: FontWeight.w600, fontSize: 13)),

            const SizedBox(height: 28),
            _buildSectionTitle("Metode Pengambilan"),
            // Pilihan Metode Pengambilan
            Row(
              children: [
                Expanded(
                  child: _buildDeliveryOption(
                    index: 0,
                    icon: Icons.storefront_rounded,
                    title: "Ambil Sendiri",
                    subtitle: "Gratis",
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _buildDeliveryOption(
                    index: 1,
                    icon: Icons.local_shipping_rounded,
                    title: "Diantar",
                    subtitle: "+ Rp 15.000",
                  ),
                ),
              ],
            ),

            const SizedBox(height: 28),
            _buildSectionTitle("Detail Pembayaran"),
            // Rincian Harga
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: Colors.grey.shade200),
              ),
              child: Column(
                children: [
                  _buildPaymentRow("Harga Sewa (2 hari)", "Rp 300.000"),
                  const SizedBox(height: 12),
                  _buildPaymentRow("Biaya Pengambilan", _selectedDeliveryMethod == 0 ? "Gratis" : "Rp 15.000"),
                  const SizedBox(height: 12),
                  _buildPaymentRow("Biaya Layanan Aplikasi", "Rp 2.000"),
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 16),
                    child: Divider(),
                  ),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text("Total Pembayaran", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF2D3142))),
                      Text(
                        _selectedDeliveryMethod == 0 ? "Rp 302.000" : "Rp 317.000",
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF3D5AFE)),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
      
      // Bottom Bar Konfirmasi
      bottomNavigationBar: Container(
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 20, offset: const Offset(0, -5))],
        ),
        child: SafeArea(
          child: Row(
            children: [
              Expanded(
                flex: 2,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text("Total Harga", style: TextStyle(color: Colors.grey, fontSize: 12)),
                    Text(
                      _selectedDeliveryMethod == 0 ? "Rp 302.000" : "Rp 317.000",
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF2D3142)),
                    ),
                  ],
                ),
              ),
              Expanded(
                flex: 3,
                child: ElevatedButton(
                  onPressed: () {
                    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Pesanan berhasil dibuat!")));
                    // Nanti bisa diarahkan ke halaman sukses / riwayat pesanan
                    Navigator.pushReplacementNamed(context, '/success');
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF3D5AFE),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    elevation: 0,
                  ),
                  child: const Text("Konfirmasi", style: TextStyle(fontSize: 16, color: Colors.white, fontWeight: FontWeight.bold)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Text(title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF2D3142))),
    );
  }

  Widget _buildDateSelector(String label, String date) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey, fontSize: 12)),
          const SizedBox(height: 8),
          Row(
            children: [
              const Icon(Icons.calendar_today_rounded, size: 16, color: Color(0xFF3D5AFE)),
              const SizedBox(width: 8),
              Text(date, style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF2D3142))),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildDeliveryOption({required int index, required IconData icon, required String title, required String subtitle}) {
    bool isSelected = _selectedDeliveryMethod == index;
    return GestureDetector(
      onTap: () {
        setState(() {
          _selectedDeliveryMethod = index;
        });
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFFEEF2FF) : Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: isSelected ? const Color(0xFF3D5AFE) : Colors.grey.shade200, width: isSelected ? 2 : 1),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: isSelected ? const Color(0xFF3D5AFE) : Colors.grey),
            const SizedBox(height: 12),
            Text(title, style: TextStyle(fontWeight: FontWeight.bold, color: isSelected ? const Color(0xFF3D5AFE) : const Color(0xFF2D3142))),
            const SizedBox(height: 4),
            Text(subtitle, style: TextStyle(color: isSelected ? const Color(0xFF3D5AFE) : Colors.grey, fontSize: 12)),
          ],
        ),
      ),
    );
  }

  Widget _buildPaymentRow(String title, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(title, style: const TextStyle(color: Colors.grey)),
        Text(value, style: const TextStyle(fontWeight: FontWeight.w600, color: Color(0xFF2D3142))),
      ],
    );
  }
}