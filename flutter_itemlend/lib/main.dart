import 'package:flutter/material.dart';
import 'services/api_service.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';

void main() {
  runApp(const ItemLendApp());
}

class ItemLendApp extends StatelessWidget {
  const ItemLendApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'ItemLend',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorSchemeSeed: Colors.indigo,
        useMaterial3: true,
      ),
      home: const _AuthGate(),
    );
  }
}

/// Cek apakah user sudah punya token tersimpan (auto-login),
/// kalau belum arahkan ke LoginScreen
class _AuthGate extends StatelessWidget {
  const _AuthGate();

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<String?>(
      future: ApiService.getToken(),
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Scaffold(
            body: Center(child: CircularProgressIndicator()),
          );
        }
        final hasToken = snapshot.data != null && snapshot.data!.isNotEmpty;
        return hasToken ? const HomeScreen() : const LoginScreen();
      },
    );
  }
}