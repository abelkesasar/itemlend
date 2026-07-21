import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

class AuthService extends ChangeNotifier {
  // Sesuaikan nama folder 'itemlend_api' dengan nama folder PHP kamu di htdocs
  final String _baseUrl = 'http://10.0.2.2/ITEMLEND WEB';

  String? _token;
  Map<String, dynamic>? _user;

  String? get token => _token;
  Map<String, dynamic>? get user => _user;
  
  bool get isLoggedIn => _token != null;
  
  // Mengecek apakah user yang login adalah vendor
  bool get isVendor => _user?['role'] == 'vendor';

  // --- FUNGSI LOGIN ---
  Future<bool> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/login.php'), 
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'email': email,
          'password': password,
        }),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true) {
          _token = "dummy_token_${data['data']['id']}"; 
          _user = data['data']; 
          
          notifyListeners(); 
          return true;
        }
        return false;
      } else {
        return false;
      }
    } catch (e) {
      debugPrint("Error Login: $e");
      return false;
    }
  }

  // --- FUNGSI REGISTER ---
  // Ditambahkan parameter String role di bagian akhir
  Future<bool> register(String username, String email, String nomorWa, String password, String role) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/register.php'),
        // jsonEncode dan headers dihilangkan agar PHP $_POST bisa langsung membaca datanya
        body: {
          'username': username,
          'email': email,
          'nomor_wa': nomorWa,
          'password': password,
          'role': role, // Data role dari dropdown dikirim ke PHP
        },
      );

      if (response.statusCode == 201 || response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true) {
          // Kita tidak perlu set _token dan _user di sini karena register.php tidak 
          // mengembalikan data detail user. Lebih aman jika user diarahkan 
          // untuk login manual setelah registrasi berhasil.
          return true;
        }
        return false;
      } else {
        return false;
      }
    } catch (e) {
      debugPrint("Error Register: $e");
      return false;
    }
  }

  // --- FUNGSI LOGOUT ---
  Future<void> logout() async {
    _token = null;
    _user = null;
    notifyListeners();
  }
}