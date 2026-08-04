import 'package:flutter/material.dart';
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
        colorSchemeSeed: const Color(0xFF6366F1),
        useMaterial3: true,
      ),
      home: const HomeScreen(),
    );
  }
}