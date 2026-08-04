import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'login_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  Map<String, String?> _userData = {};

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  Future<void> _loadUser() async {
    final data = await ApiService.getUserData();
    setState(() => _userData = data);
  }

  Future<void> _handleLogout() async {
    await ApiService.logout();
    if (!mounted) return;
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(builder: (_) => const LoginScreen()),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('ItemLend'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: _handleLogout,
          ),
        ],
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.check_circle_outline, size: 64, color: Colors.green),
            const SizedBox(height: 16),
            Text(
              'Selamat datang, ${_userData['username'] ?? '-'}!',
              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 4),
            Text('Role: ${_userData['role'] ?? '-'}'),
          ],
        ),
      ),
    );
  }
}