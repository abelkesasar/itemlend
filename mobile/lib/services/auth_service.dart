import 'package:flutter/material.dart';

class AuthService extends ChangeNotifier {
  bool _isLoggedIn = false;
  
  // Variabel untuk menyimpan data user dummy sementara
  String? _registeredEmail;
  String? _registeredPassword;
  String? _registeredRole; // Variabel baru untuk menyimpan role

  bool get isLoggedIn => _isLoggedIn;
  String? get userRole => _registeredRole; // Getter untuk mengambil role user saat sudah login

  // Fungsi Register: Menyimpan data email, password, dan role
  Future<bool> register(String email, String password, String role) async {
    await Future.delayed(const Duration(seconds: 1));
    
    // Simpan data ke variabel sementara
    _registeredEmail = email;
    _registeredPassword = password;
    _registeredRole = role; // Simpan role
    
    return true; // Berhasil daftar
  }

  // Fungsi Login: Mengecek apakah email & password cocok dengan yang di-register
  Future<bool> login(String email, String password) async {
    await Future.delayed(const Duration(seconds: 1)); 
    
    // Cek apakah email & password cocok dengan hasil register
    if (email == _registeredEmail && password == _registeredPassword) {
      _isLoggedIn = true;
      notifyListeners(); // Memberi tahu UI bahwa status login berubah
      return true;
    }
    return false; // Login gagal
  }

  void logout() {
    _isLoggedIn = false;
    _registeredRole = null; // Reset role saat logout
    notifyListeners();
  }
}