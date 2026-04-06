import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'screens/navigation_hub.dart';
import 'screens/login_screen.dart';
import 'screens/register_screen.dart';
import 'providers/auth_provider.dart';
import 'providers/seller_provider.dart';

void main() {
  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => SellerProvider()),
      ],
      child: const SellerApp(),
    ),
  );
}

class SellerApp extends StatelessWidget {
  const SellerApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Seller Dashboard Pulse',
      debugShowCheckedModeBanner: false,
      themeMode: ThemeMode.dark,
      darkTheme: ThemeData(
        brightness: Brightness.dark,
        scaffoldBackgroundColor: const Color(0xFF0A0E17),
        primaryColor: const Color(0xFF2977F5),
        colorScheme: const ColorScheme.dark(
          primary: Color(0xFF2977F5),
          secondary: Color(0xFF0BDA5E),
          surface: Color(0xFF111827),
          background: Color(0xFF0A0E17),
        ),
        textTheme: GoogleFonts.interTextTheme(ThemeData.dark().textTheme),
        useMaterial3: true,
      ),
      home: Consumer<AuthProvider>(
        builder: (context, auth, _) {
          if (!auth.isAuthenticated) {
            return const LoginScreen();
          }
          return const NavigationHub();
        },
      ),
      routes: {
        '/login': (context) => const LoginScreen(),
        '/register': (context) => const RegisterScreen(),
        '/dashboard': (context) => const NavigationHub(),
      },
    );
  }
}
