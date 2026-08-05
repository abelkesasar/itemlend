import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

/// Ganti sesuai lokasi server API kamu:
/// - Android Emulator  -> 'http://10.0.2.2/itemlend/api'
/// - iOS Simulator     -> 'http://localhost/itemlend/api'
/// - HP fisik (1 wifi) -> 'http://IP-LAPTOP-KAMU/itemlend/api' (misal http://192.168.1.5/itemlend/api)
/// - Production        -> 'https://domainmu.com/api'
const String baseUrl = 'http://10.0.2.2/itemlend/api';

class ApiService {
  /// Login ke API dan simpan token + data user ke local storage kalau berhasil
  static Future<Map<String, dynamic>> login(String username, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api_login.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'username': username, 'password': password}),
      );

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        final token = data['data']['token'];
        final user = data['data']['user'];

        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('token', token);
        await prefs.setString('username', user['username']);
        await prefs.setString('role', user['role']);
        await prefs.setInt('user_id', user['id']);
      }

      return data; // { success: bool, message: String, data?: {...} }
    } catch (e) {
      return {
        'success': false,
        'message': 'Tidak bisa terhubung ke server. Cek koneksi atau URL API.',
      };
    }
  }

  /// Register user baru, wajib kirim file KTP & KTM (multipart/form-data)
  static Future<Map<String, dynamic>> register({
    required String username,
    required String email,
    required String password,
    required String alamat,
    required String nomorWa,
    required File ktpFile,
    required File ktmFile,
  }) async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/api_register.php'),
      );

      request.fields['username'] = username;
      request.fields['email'] = email;
      request.fields['password'] = password;
      request.fields['alamat'] = alamat;
      request.fields['nomor_wa'] = nomorWa;

      request.files.add(await http.MultipartFile.fromPath('ktp_user', ktpFile.path));
      request.files.add(await http.MultipartFile.fromPath('ktm', ktmFile.path));

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);

      return jsonDecode(response.body);
    } catch (e) {
      return {
        'success': false,
        'message': 'Tidak bisa terhubung ke server. Cek koneksi atau URL API.',
      };
    }
  }

  /// Ambil detail 1 barang (tidak wajib login)
  static Future<Map<String, dynamic>> getItemDetail(int id) async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/api_detail_barang.php?id=$id'));
      return jsonDecode(response.body);
    } catch (e) {
      return {
        'success': false,
        'message': 'Tidak bisa terhubung ke server. Cek koneksi atau URL API.',
      };
    }
  }

  /// Ambil daftar barang yang sudah di-approve admin
  static Future<Map<String, dynamic>> getItems() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/api_items.php'));
      return jsonDecode(response.body);
    } catch (e) {
      return {
        'success': false,
        'message': 'Tidak bisa terhubung ke server. Cek koneksi atau URL API.',
      };
    }
  }

  /// Tambah barang baru (wajib login). Bisa kirim lebih dari 1 foto sekaligus.
  static Future<Map<String, dynamic>> tambahBarang({
    required String namaBarang,
    required String deskripsi,
    required int harga,
    required int stok,
    required String kategori,
    required String lokasi,
    required List<File> gambarFiles,
  }) async {
    try {
      final token = await getToken();

      if (token == null || token.isEmpty) {
        return {
          'success': false,
          'message': 'Kamu harus login dulu untuk menambahkan barang.',
        };
      }

      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/api_tambah_barang.php'),
      );

      request.headers['Authorization'] = 'Bearer $token';

      request.fields['nama_barang'] = namaBarang;
      request.fields['deskripsi'] = deskripsi;
      request.fields['harga'] = harga.toString();
      request.fields['stok'] = stok.toString();
      request.fields['kategori'] = kategori;
      request.fields['lokasi'] = lokasi;

      for (final file in gambarFiles) {
        request.files.add(await http.MultipartFile.fromPath('gambar[]', file.path));
      }

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);

      return jsonDecode(response.body);
    } catch (e) {
      return {
        'success': false,
        'message': 'Tidak bisa terhubung ke server. Cek koneksi atau URL API.',
      };
    }
  }

  /// Ambil data profil user yang sedang login
  static Future<Map<String, dynamic>> getProfile() async {
    try {
      final token = await getToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Kamu harus login dulu.'};
      }

      final response = await http.get(
        Uri.parse('$baseUrl/api_get_profile.php'),
        headers: {'Authorization': 'Bearer $token'},
      );

      print('GET PROFILE STATUS: ${response.statusCode}');
      print('GET PROFILE BODY: ${response.body}');

      return jsonDecode(response.body);
    } catch (e) {
      print('GET PROFILE ERROR: $e');
      return {
        'success': false,
        'message': 'Tidak bisa terhubung ke server. Cek koneksi atau URL API.',
      };
    }
  }

  /// Update profil user (termasuk metode pembayaran). File bersifat opsional.
  static Future<Map<String, dynamic>> updateProfile({
    required String username,
    required String email,
    required String nomorWa,
    required String alamat,
    String? deskripsiVendor,
    required String namaPenyedia,
    required String nomorRekening,
    required String namaPemilikRekening,
    String? password,
    File? fotoProfil,
    File? fotoQris,
  }) async {
    try {
      final token = await getToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Kamu harus login dulu.'};
      }

      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/api_update_profile.php'),
      );

      request.headers['Authorization'] = 'Bearer $token';

      request.fields['username'] = username;
      request.fields['email'] = email;
      request.fields['nomor_wa'] = nomorWa;
      request.fields['alamat'] = alamat;
      request.fields['deskripsi_vendor'] = deskripsiVendor ?? '';
      request.fields['nama_penyedia'] = namaPenyedia;
      request.fields['nomor_rekening'] = nomorRekening;
      request.fields['nama_pemilik_rekening'] = namaPemilikRekening;
      if (password != null && password.isNotEmpty) {
        request.fields['password'] = password;
      }

      if (fotoProfil != null) {
        request.files.add(await http.MultipartFile.fromPath('foto_profil', fotoProfil.path));
      }
      if (fotoQris != null) {
        request.files.add(await http.MultipartFile.fromPath('foto_qris', fotoQris.path));
      }

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);

      return jsonDecode(response.body);
    } catch (e) {
      return {
        'success': false,
        'message': 'Tidak bisa terhubung ke server. Cek koneksi atau URL API.',
      };
    }
  }

  /// Cek apakah user masih punya token tersimpan (dipakai buat auto-login)
  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('token');
  }

  static Future<int?> getUserId() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getInt('user_id');
  }

  static Future<Map<String, String?>> getUserData() async {
    final prefs = await SharedPreferences.getInstance();
    return {
      'username': prefs.getString('username'),
      'role': prefs.getString('role'),
    };
  }

  static Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
  }
}