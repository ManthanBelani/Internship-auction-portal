import 'package:flutter/material.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:provider/provider.dart';
import 'config/env_config.dart';
import 'providers/auth_provider.dart';
import 'providers/items_provider.dart';
import 'providers/watchlist_provider.dart';
import 'providers/theme_provider.dart';
import 'providers/seller_provider.dart';
import 'providers/bid_provider.dart';
import 'providers/notification_provider.dart';
import 'providers/payment_provider.dart';
import 'providers/shipping_provider.dart';
import 'providers/order_provider.dart';
import 'providers/sales_provider.dart';
import 'providers/analytics_provider.dart';
import 'providers/payout_provider.dart';
import 'providers/messages_provider.dart';
import 'theme/app_theme.dart';
import 'user_screens/login_screen.dart';
import 'user_screens/main_navigation.dart';
import 'seller_screens/dashboard_screen.dart';
import 'services/logger_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await dotenv.load(fileName: '.env');

  EnvConfig.printConfig();

  FlutterError.onError = (FlutterErrorDetails details) {
    LoggerService.error(
      'Flutter Error',
      error: details.exception,
      stackTrace: details.stack,
    );
  };

  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => ItemsProvider()),
        ChangeNotifierProvider(create: (_) => WatchlistProvider()),
        ChangeNotifierProvider(create: (_) => ThemeProvider()),
        ChangeNotifierProvider(create: (_) => SellerProvider()),
        ChangeNotifierProvider(create: (_) => BidProvider()),
        ChangeNotifierProvider(create: (_) => NotificationProvider()),
        ChangeNotifierProvider(create: (_) => PaymentProvider()),
        ChangeNotifierProvider(create: (_) => ShippingProvider()),
        ChangeNotifierProvider(create: (_) => OrderProvider()),
        ChangeNotifierProvider(create: (_) => SalesProvider()),
        ChangeNotifierProvider(create: (_) => AnalyticsProvider()),
        ChangeNotifierProvider(create: (_) => PayoutProvider()),
        ChangeNotifierProvider(create: (_) => MessagesProvider()),
      ],
      child: const MyApp(),
    ),
  );
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'BidOrbit',
      theme: AppTheme.light,
      darkTheme: AppTheme.dark,
      themeMode: context.watch<ThemeProvider>().themeMode,
      home: const AuthCheckScreen(),
      routes: {
        '/login': (context) => const LoginScreen(),
        '/home': (context) => const MainNavigation(),
        '/seller-dashboard': (context) => const SellerDashboardScreen(),
      },
    );
  }
}

class AuthCheckScreen extends StatefulWidget {
  const AuthCheckScreen({super.key});

  @override
  State<AuthCheckScreen> createState() => _AuthCheckScreenState();
}

class _AuthCheckScreenState extends State<AuthCheckScreen> {
  @override
  void initState() {
    super.initState();
    _checkAuth();
  }

  Future<void> _checkAuth() async {
    final authProvider = context.read<AuthProvider>();

    try {
      final isLoggedIn = await authProvider.tryAutoLogin();

      if (!mounted) return;

      if (isLoggedIn && authProvider.user != null) {
        if (authProvider.user!.role == 'seller') {
          Navigator.of(context).pushReplacementNamed('/seller-dashboard');
        } else {
          Navigator.of(context).pushReplacementNamed('/home');
        }
      } else {
        Navigator.of(context).pushReplacementNamed('/login');
      }
    } catch (e) {
      LoggerService.error('Auth check failed', error: e);
      if (!mounted) return;
      Navigator.of(context).pushReplacementNamed('/login');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.surface,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 120,
              height: 120,
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [AppColors.primary, AppColors.primaryDark],
                ),
                borderRadius: BorderRadius.circular(AppRadius.xl),
                boxShadow: AppShadows.primary,
              ),
              child: const Icon(Icons.gavel, size: 60, color: Colors.white),
            ),
            const SizedBox(height: 24),
            const Text(
              'Bid Orbit',
              style: TextStyle(
                fontSize: 32,
                fontWeight: FontWeight.bold,
                letterSpacing: 1.2,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Your Auction Marketplace',
              style: TextStyle(fontSize: 16, color: AppColors.textSecondary),
            ),
            const SizedBox(height: 48),
            const CircularProgressIndicator(color: AppColors.primary),
          ],
        ),
      ),
    );
  }
}
