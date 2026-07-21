import 'package:flutter/material.dart';

class PaymentPage extends StatefulWidget {
  const PaymentPage({super.key});

  @override
  State<PaymentPage> createState() => _PaymentPageState();
}

class _PaymentPageState extends State<PaymentPage> {
  // Index untuk metode pembayaran yang dipilih
  int _selectedMethod = 0;
  bool _isLoading = false;

  // Dummy data metode pembayaran
  final List<Map<String, dynamic>> _paymentMethods = [
    {
      "name": "Transfer Bank (BCA)",
      "desc": "Bebas biaya admin",
      "icon": Icons.account_balance_rounded,
    },
    {
      "name": "GoPay",
      "desc": "Biaya penanganan Rp 1.000",
      "icon": Icons.account_balance_wallet_rounded,
    },
    {
      "name": "Bayar di Toko",
      "desc": "Bayar tunai saat mengambil barang",
      "icon": Icons.storefront_rounded,
    },
  ];

  // Fungsi simulasi proses pembayaran
  void _processPayment() async {
    setState(() {
      _isLoading = true; // Munculkan efek loading
    });

    // Simulasi jeda waktu sistem memproses pembayaran (2 detik)
    await Future.delayed(const Duration(seconds: 2));

    if (!mounted) return;

    setState(() {
      _isLoading = false;
    });

    // Tampilkan notifikasi sukses
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text("Pembayaran Berhasil Dikonfirmasi! 🎉"),
        backgroundColor: Colors.green,
        duration: Duration(seconds: 2),
      ),
    );

    // Setelah sukses, kembali ke beranda atau arahkan ke halaman riwayat
    // Jika kamu punya halaman success_page, ganti Navigator.pop dengan:
    // Navigator.pushNamed(context, '/success');
    Navigator.pop(context); 
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          "Pembayaran",
          style: TextStyle(color: Color(0xFF2D3142), fontWeight: FontWeight.bold),
        ),
        iconTheme: const IconThemeData(color: Color(0xFF2D3142)),
        centerTitle: true,
        surfaceTintColor: Colors.transparent,
      ),
      
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Section 1: Ringkasan Total Pembayaran
            const Text(
              "Ringkasan Pembayaran",
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF2D3142)),
            ),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: const Color(0xFF3D5AFE),
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF3D5AFE).withAlpha(70), // withAlpha aman dari warning IDE
                    blurRadius: 15,
                    offset: const Offset(0, 6),
                  )
                ],
              ),
              child: const Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text("Total Tagihan", style: TextStyle(color: Colors.white70, fontSize: 14)),
                      SizedBox(height: 4),
                      Text("Rp 302.000", style: TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold)),
                    ],
                  ),
                  Icon(Icons.receipt_long_rounded, color: Colors.white, size: 40),
                ],
              ),
            ),
            
            const SizedBox(height: 30),
            
            // Section 2: Pilih Metode Pembayaran
            const Text(
              "Pilih Metode Pembayaran",
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF2D3142)),
            ),
            const SizedBox(height: 12),
            
            // List Metode Pembayaran
            ...List.generate(_paymentMethods.length, (index) {
              final method = _paymentMethods[index];
              final isSelected = _selectedMethod == index;
              
              return GestureDetector(
                onTap: () {
                  setState(() {
                    _selectedMethod = index;
                  });
                },
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 200),
                  margin: const EdgeInsets.only(bottom: 12),
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: isSelected ? const Color(0xFFEEF2FF) : Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: isSelected ? const Color(0xFF3D5AFE) : Colors.grey.shade200,
                      width: isSelected ? 2 : 1,
                    ),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: isSelected ? const Color(0xFF3D5AFE) : Colors.grey.shade100,
                          shape: BoxShape.circle,
                        ),
                        child: Icon(
                          method["icon"],
                          color: isSelected ? Colors.white : Colors.grey.shade600,
                          size: 24,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              method["name"],
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 15,
                                color: isSelected ? const Color(0xFF3D5AFE) : const Color(0xFF2D3142),
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              method["desc"],
                              style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                            ),
                          ],
                        ),
                      ),
                      // Icon checklist jika dipilih
                      if (isSelected)
                        const Icon(Icons.check_circle_rounded, color: Color(0xFF3D5AFE)),
                    ],
                  ),
                ),
              );
            }),
          ],
        ),
      ),
      
      // Section 3: Tombol Bayar di bagian bawah
      bottomNavigationBar: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: Colors.grey.shade200)),
        ),
        child: SafeArea(
          child: SizedBox(
            width: double.infinity,
            height: 55,
            child: ElevatedButton(
              onPressed: _isLoading ? null : _processPayment,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF3D5AFE),
                disabledBackgroundColor: Colors.grey.shade300,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                elevation: 0,
              ),
              child: _isLoading
                  ? const SizedBox(
                      width: 24,
                      height: 24,
                      child: CircularProgressIndicator(color: Colors.white, strokeWidth: 3),
                    )
                  : const Text(
                      "Bayar Sekarang",
                      style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
                    ),
            ),
          ),
        ),
      ),
    );
  }
}