import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';

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

  /// Buat rental/booking barang (wajib login)
  static Future<Map<String, dynamic>> sewaBarang({
    required int itemId,
    required String start,
    required String end,
  }) async {
    try {
      final token = await getToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Kamu harus login dulu.'};
      }

      final response = await http.post(
        Uri.parse('$baseUrl/api_sewa.php'),
        headers: {'Authorization': 'Bearer $token'},
        body: {
          'item_id': itemId.toString(),
          'start': start,
          'end': end,
        },
      );

      return jsonDecode(response.body);
    } catch (e) {
      return {
        'success': false,
        'message': 'Tidak bisa terhubung ke server. Cek koneksi atau URL API.',
      };
    }
  }

  /// Upload bukti pembayaran untuk rental tertentu (wajib login)
  static Future<Map<String, dynamic>> prosesPembayaran({
    required int rentalId,
    required String metode,
    required File buktiFile,
  }) async {
    try {
      final token = await getToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Kamu harus login dulu.'};
      }

      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/api_proses_pembayaran.php'),
      );

      request.headers['Authorization'] = 'Bearer $token';

      request.fields['rental_id'] = rentalId.toString();
      request.fields['metode'] = metode;

      request.files.add(await http.MultipartFile.fromPath('bukti', buktiFile.path));

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

  // ────────────────────────────────────────────
  //  Barang Saya
  // ────────────────────────────────────────────

  /// Ambil daftar barang milik user yang login.
  static Future<Map<String, dynamic>> getBarangSaya() async {
    try {
      final token = await getToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Kamu harus login dulu.'};
      }

      final response = await http.get(
        Uri.parse('$baseUrl/api_barang_saya.php'),
        headers: {'Authorization': 'Bearer $token'},
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  /// Hapus barang milik sendiri (hanya bisa hapus yang status pending).
  static Future<Map<String, dynamic>> hapusBarang({required int itemId}) async {
    try {
      final token = await getToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Kamu harus login dulu.'};
      }

      final response = await http.post(
        Uri.parse('$baseUrl/api_hapus_barang.php'),
        headers: {'Authorization': 'Bearer $token'},
        body: {'item_id': itemId.toString()},
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  // ────────────────────────────────────────────
  //  Pesanan Masuk (Owner)
  // ────────────────────────────────────────────

  /// Ambil daftar pesanan masuk untuk barang milik pemilik ini.
  static Future<Map<String, dynamic>> getPesananMasuk() async {
    try {
      final token = await getToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Kamu harus login dulu.'};
      }

      final response = await http.get(
        Uri.parse('$baseUrl/api_pesanan_masuk.php'),
        headers: {'Authorization': 'Bearer $token'},
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  /// Update status pinjam (mulai dipinjam / tandai selesai).
  static Future<Map<String, dynamic>> updateStatusPinjam({
    required int rentalId,
    required String statusPinjam,
  }) async {
    try {
      final token = await getToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Kamu harus login dulu.'};
      }

      final response = await http.post(
        Uri.parse('$baseUrl/api_update_status_pinjam.php'),
        headers: {'Authorization': 'Bearer $token'},
        body: {
          'rental_id': rentalId.toString(),
          'status_pinjam': statusPinjam,
        },
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  /// Laporkan penyewa/pemilik (wajib login). Bukti bersifat opsional.
  static Future<Map<String, dynamic>> laporkan({
    required int targetId,
    required String reason,
    String? detail,
    File? buktiFile,
  }) async {
    try {
      final token = await getToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Kamu harus login dulu.'};
      }

      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/api_report.php'),
      );

      request.headers['Authorization'] = 'Bearer $token';
      request.fields['target_id'] = targetId.toString();
      request.fields['reason'] = reason;
      if (detail != null && detail.isNotEmpty) {
        request.fields['detail'] = detail;
      }

      if (buktiFile != null) {
        request.files.add(await http.MultipartFile.fromPath('bukti', buktiFile.path));
      }

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  // ────────────────────────────────────────────
  //  Pesanan Saya (Penyewa)
  // ────────────────────────────────────────────

  /// Ambil daftar pesanan milik user yang login (sebagai penyewa).
  static Future<Map<String, dynamic>> getPesananSaya() async {
    try {
      final token = await getToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Kamu harus login dulu.'};
      }

      final response = await http.get(
        Uri.parse('$baseUrl/api_pesanan_saya.php'),
        headers: {'Authorization': 'Bearer $token'},
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
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

  /// Buka URL external (WhatsApp, dll)
  static Future<void> openUrl(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  // ══════════════════════════════════════════════════
  //  ADMIN METHODS
  // ══════════════════════════════════════════════════

  /// Ambil data dashboard admin
  static Future<Map<String, dynamic>> getAdminDashboard() async {
    try {
      final token = await getAdminToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Admin harus login dulu.'};
      }
      final response = await http.get(
        Uri.parse('$baseUrl/api_admin_dashboard.php'),
        headers: {'Authorization': 'Bearer $token'},
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  /// List semua users untuk admin
  static Future<Map<String, dynamic>> getAdminUsers() async {
    try {
      final token = await getAdminToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Admin harus login dulu.'};
      }
      final response = await http.get(
        Uri.parse('$baseUrl/api_admin_users.php'),
        headers: {'Authorization': 'Bearer $token'},
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  /// Detail user untuk admin
  static Future<Map<String, dynamic>> getAdminUserDetail(int userId) async {
    try {
      final token = await getAdminToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Admin harus login dulu.'};
      }
      final response = await http.get(
        Uri.parse('$baseUrl/api_admin_user_detail.php?id=$userId'),
        headers: {'Authorization': 'Bearer $token'},
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  /// Approve/reject user
  static Future<Map<String, dynamic>> adminUserAction({
    required int userId,
    required String action,
  }) async {
    try {
      final token = await getAdminToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Admin harus login dulu.'};
      }
      final response = await http.post(
        Uri.parse('$baseUrl/api_admin_user_action.php'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({'id': userId, 'action': action}),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  /// List items untuk admin
  static Future<Map<String, dynamic>> getAdminItems({
    String status = 'approved',
    String search = '',
    String sort = 'terbaru',
  }) async {
    try {
      final token = await getAdminToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Admin harus login dulu.'};
      }
      final uri = Uri.parse('$baseUrl/api_admin_items.php').replace(
        queryParameters: {
          if (status.isNotEmpty) 'status': status,
          if (search.isNotEmpty) 'search': search,
          if (sort.isNotEmpty) 'sort': sort,
        },
      );
      final response = await http.get(
        uri,
        headers: {'Authorization': 'Bearer $token'},
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  /// Approve/reject item
  static Future<Map<String, dynamic>> adminItemAction({
    required int itemId,
    required String action,
  }) async {
    try {
      final token = await getAdminToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Admin harus login dulu.'};
      }
      final response = await http.post(
        Uri.parse('$baseUrl/api_admin_item_action.php'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({'id': itemId, 'action': action}),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  /// List rentals untuk admin
  static Future<Map<String, dynamic>> getAdminRentals({
    String tab = 'semua',
    String search = '',
    String sort = 'terbaru',
  }) async {
    try {
      final token = await getAdminToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Admin harus login dulu.'};
      }
      final uri = Uri.parse('$baseUrl/api_admin_rentals.php').replace(
        queryParameters: {
          if (tab.isNotEmpty) 'tab': tab,
          if (search.isNotEmpty) 'search': search,
          if (sort.isNotEmpty) 'sort': sort,
        },
      );
      final response = await http.get(
        uri,
        headers: {'Authorization': 'Bearer $token'},
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  /// Konfirmasi/tolak pembayaran
  static Future<Map<String, dynamic>> adminRentalAction({
    required int rentalId,
    required String aksi,
    String? catatan,
  }) async {
    try {
      final token = await getAdminToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Admin harus login dulu.'};
      }
      final response = await http.post(
        Uri.parse('$baseUrl/api_admin_rental_action.php'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'rental_id': rentalId,
          'aksi': aksi,
          if (catatan != null) 'catatan': catatan,
        }),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  /// List laporan untuk admin
  static Future<Map<String, dynamic>> getAdminReports({
    String status = 'all',
  }) async {
    try {
      final token = await getAdminToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Admin harus login dulu.'};
      }
      final uri = Uri.parse('$baseUrl/api_admin_reports.php').replace(
        queryParameters: {
          if (status.isNotEmpty && status != 'all') 'status': status,
        },
      );
      final response = await http.get(
        uri,
        headers: {'Authorization': 'Bearer $token'},
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  /// Proses laporan (sanksi, refund, ganti rugi)
  static Future<Map<String, dynamic>> adminReportAction({
    required int reportId,
    required String sanksiOption,
    String refundOption = 'tidak_ada',
    String catatanRefund = '',
    String tagihanGantiRugi = '',
    int amountGantiRugi = 0,
  }) async {
    try {
      final token = await getAdminToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Admin harus login dulu.'};
      }
      final response = await http.post(
        Uri.parse('$baseUrl/api_admin_report_action.php'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'id': reportId,
          'sanksi_option': sanksiOption,
          'refund_option': refundOption,
          'catatan_refund': catatanRefund,
          'tagihan_ganti_rugi': tagihanGantiRugi,
          'amount_ganti_rugi': amountGantiRugi,
        }),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  /// List pencairan untuk admin
  static Future<Map<String, dynamic>> getAdminPencairan({
    String tab = 'belum',
    String search = '',
    String sort = 'terbaru',
  }) async {
    try {
      final token = await getAdminToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Admin harus login dulu.'};
      }
      final uri = Uri.parse('$baseUrl/api_admin_pencairan.php').replace(
        queryParameters: {
          if (tab.isNotEmpty) 'tab': tab,
          if (search.isNotEmpty) 'search': search,
          if (sort.isNotEmpty) 'sort': sort,
        },
      );
      final response = await http.get(
        uri,
        headers: {'Authorization': 'Bearer $token'},
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  /// Cairkan dana (dengan upload bukti)
  static Future<Map<String, dynamic>> adminPencairanAction({
    required int rentalId,
    File? buktiFile,
  }) async {
    try {
      final token = await getAdminToken();
      if (token == null || token.isEmpty) {
        return {'success': false, 'message': 'Admin harus login dulu.'};
      }

      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/api_admin_pencairan_action.php'),
      );
      request.headers['Authorization'] = 'Bearer $token';
      request.fields['rental_id'] = rentalId.toString();

      if (buktiFile != null) {
        request.files.add(
          await http.MultipartFile.fromPath('bukti_pencairan', buktiFile.path),
        );
      }

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    }
  }

  // ── Admin Token Management ──

  static Future<String?> getAdminToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('admin_token');
  }

  static Future<void> saveAdminToken(String token, String username) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('admin_token', token);
    await prefs.setString('admin_username', username);
    await prefs.setBool('is_admin', true);
  }

  static Future<bool> isAdminLoggedIn() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool('is_admin') == true && prefs.getString('admin_token') != null;
  }

  static Future<void> logoutAdmin() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('admin_token');
    await prefs.remove('admin_username');
    await prefs.remove('is_admin');
  }

  static Future<String?> getAdminUsername() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('admin_username');
  }
}