import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';

class RegisterPage extends StatefulWidget {
  const RegisterPage({super.key});

  @override
  State<RegisterPage> createState() => _RegisterPageState();
}

class _RegisterPageState extends State<RegisterPage> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();

  // Variabel untuk menyimpan pilihan Role
  String _selectedRole = 'user'; // Default-nya sebagai Mahasiswa (user)

  bool _isLoading = false;
  bool _isObscure = true;

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleRegister() async {
    if (_formKey.currentState!.validate()) {
      setState(() {
        _isLoading = true;
      });

      final authService = Provider.of<AuthService>(context, listen: false);

      // Memanggil fungsi register, SEKARANG MENGIRIM _selectedRole
      final success = await authService.register(
        _nameController.text.trim(),
        _emailController.text.trim(),
        _phoneController.text.trim(),
        _passwordController.text,
        _selectedRole, // <--- Ini tambahan parameternya
      );

      setState(() {
        _isLoading = false;
      });

      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text("Pendaftaran Berhasil! Data tersimpan di database 🎉"),
            backgroundColor: Colors.green,
          ),
        );
        
        Navigator.pushNamedAndRemoveUntil(context, '/home', (route) => false);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text("Pendaftaran Gagal. Cek kembali koneksi API atau email mungkin sudah terdaftar."),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: Colors.black,
      ),
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24.0),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text(
                  "Buat Akun Baru",
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF2D3142),
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  "Lengkapi data di bawah untuk bergabung",
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 14, color: Colors.grey.shade600),
                ),
                const SizedBox(height: 40),

                // Input Username
                TextFormField(
                  controller: _nameController,
                  decoration: InputDecoration(
                    labelText: "Username",
                    prefixIcon: const Icon(Icons.person_outline),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
                    filled: true,
                    fillColor: Colors.white,
                  ),
                  validator: (value) => value!.isEmpty ? "Username wajib diisi" : null,
                ),
                const SizedBox(height: 20),

                // Input Email
                TextFormField(
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  decoration: InputDecoration(
                    labelText: "Email",
                    prefixIcon: const Icon(Icons.email_outlined),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
                    filled: true,
                    fillColor: Colors.white,
                  ),
                  validator: (value) => value!.isEmpty ? "Email wajib diisi" : null,
                ),
                const SizedBox(height: 20),

                // Input Nomor WA
                TextFormField(
                  controller: _phoneController,
                  keyboardType: TextInputType.phone,
                  decoration: InputDecoration(
                    labelText: "Nomor WhatsApp",
                    prefixIcon: const Icon(Icons.phone_outlined),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
                    filled: true,
                    fillColor: Colors.white,
                  ),
                  validator: (value) => value!.isEmpty ? "Nomor WA wajib diisi" : null,
                ),
                const SizedBox(height: 20),

                // --- BAGIAN BARU: DROPDOWN ROLE ---
                DropdownButtonFormField<String>(
                  value: _selectedRole,
                  decoration: InputDecoration(
                    labelText: "Daftar Sebagai",
                    prefixIcon: const Icon(Icons.badge_outlined),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
                    filled: true,
                    fillColor: Colors.white,
                  ),
                  items: const [
                    DropdownMenuItem(
                      value: 'user', // Value yang dikirim ke database
                      child: Text("Mahasiswa"), // Teks yang dilihat user
                    ),
                    DropdownMenuItem(
                      value: 'vendor', // Value yang dikirim ke database
                      child: Text("Vendor"), // Teks yang dilihat user
                    ),
                  ],
                  onChanged: (String? newValue) {
                    setState(() {
                      _selectedRole = newValue!;
                    });
                  },
                ),
                const SizedBox(height: 20),
                // --- END DROPDOWN ROLE ---

                // Input Password
                TextFormField(
                  controller: _passwordController,
                  obscureText: _isObscure,
                  decoration: InputDecoration(
                    labelText: "Password",
                    prefixIcon: const Icon(Icons.lock_outline),
                    suffixIcon: IconButton(
                      icon: Icon(_isObscure ? Icons.visibility_off : Icons.visibility),
                      onPressed: () {
                        setState(() {
                          _isObscure = !_isObscure;
                        });
                      },
                    ),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
                    filled: true,
                    fillColor: Colors.white,
                  ),
                  validator: (value) => value!.isEmpty ? "Password wajib diisi" : null,
                ),
                const SizedBox(height: 40),

                // Tombol Register
                SizedBox(
                  height: 55,
                  child: ElevatedButton(
                    onPressed: _isLoading ? null : _handleRegister,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF3D5AFE),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    child: _isLoading
                        ? const SizedBox(
                            height: 24,
                            width: 24,
                            child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5),
                          )
                        : const Text(
                            "Daftar",
                            style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                          ),
                  ),
                ),
                const SizedBox(height: 20),
                
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text("Sudah punya akun? ", style: TextStyle(color: Colors.grey.shade700)),
                    TextButton(
                      onPressed: () => Navigator.pop(context),
                      child: const Text(
                        "Masuk di sini",
                        style: TextStyle(color: Color(0xFF3D5AFE), fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}