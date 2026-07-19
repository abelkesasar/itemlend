import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'providers/item_provider.dart';
import 'screens/auth/login_page.dart';
import 'screens/auth/register_page.dart'; // Hanya pakai satu register page
import 'screens/home/home_page.dart';
import 'screens/item/item_detail_page.dart';
import 'screens/item/item_list_page.dart';
import 'screens/rental/checkout_page.dart';
import 'screens/rental/rental_form_page.dart';
import 'screens/rental/success_page.dart';
import 'screens/payment/payment_page.dart';
import 'screens/chat/chat_list_page.dart';
import 'screens/chat/chat_detail_page.dart';
import 'screens/rental/rental_history_page.dart';
import 'screens/notification/notification_page.dart';
import 'screens/wishlist/wishlist_page.dart';
import 'screens/profile/profile_page.dart';
import 'screens/vendor/dashboard_vendor_page.dart';
import 'screens/vendor/my_items_page.dart';
import 'screens/vendor/add_item_page.dart';
import 'screens/vendor/edit_item_page.dart';
import 'screens/vendor/rental_request_page.dart';
import 'screens/vendor/vendor_profile_page.dart';
import 'services/auth_service.dart';
import 'screens/splash/splash_page.dart'; 

void main() {
  runApp(const ItemLendApp());
}

class ItemLendApp extends StatelessWidget {
  const ItemLendApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => ItemProvider()),
        ChangeNotifierProvider(create: (_) => AuthService()),
      ],
      child: MaterialApp(
        debugShowCheckedModeBanner: false,
        title: 'Item Lend',
        theme: ThemeData(
          primarySwatch: Colors.blue,
        ),
        
        initialRoute: '/splash',

        routes: {
          '/splash': (context) => const SplashPage(),
          '/register': (context) => const RegisterPage(),
          '/login': (context) => const LoginPage(),
          '/home': (context) => const HomePage(),
          '/items': (context) => const ItemListPage(),
          '/detail': (context) => const ItemDetailPage(),
          '/rental': (context) => const RentalFormPage(),
          '/checkout': (context) => const CheckoutPage(),
          '/payment': (context) => const PaymentPage(),
          '/success': (context) => const SuccessPage(),
          '/chat': (context) => const ChatListPage(),
          '/chat-detail': (context) => const ChatDetailPage(),
          '/rental-history': (context) => const RentalHistoryPage(),
          '/notification': (context) => const NotificationPage(),
          '/wishlist': (context) => const WishlistPage(),
          '/profile': (context) => const ProfilePage(),
          '/vendor-dashboard': (context) => const DashboardVendorPage(),
          '/my-items': (context) => const MyItemsPage(),
          '/add-item': (context) => const AddItemPage(),
          '/edit-item': (context) => const EditItemPage(),
          '/rental-request': (context) => const RentalRequestPage(),
          '/vendor-profile': (context) => const VendorProfilePage(),
        },
      ),
    );
  }
}