import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

class AppConfig {
  // API Configuration
  // Using PC's local IP address for network access
  static String get baseUrl {
    if (kReleaseMode) {
      return 'http://your-production-url.com';
    }
    // Use your PC's local IP address so the app can connect from emulator/device
    return 'http://10.241.248.238:8000'; 
  }
  
  static String get apiUrl => '$baseUrl/api';
  static String get wsUrl => 'ws://10.241.248.238:8000';
  
  // Colors
  static const Color primaryColor = Color(0xFF2094F3);
  static const Color backgroundLight = Color(0xFFF5F7F8);
  static const Color backgroundDark = Color(0xFF101A22);
  
  // App Settings
  static const int requestTimeout = 30; // seconds
  static const int maxImageSize = 10 * 1024 * 1024; // 10MB
  static const int maxImages = 10;
  
  // Pagination
  static const int itemsPerPage = 20;
  
  // Bid Settings
  static const double minBidIncrement = 10.0;
  
  // Storage Keys
  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';
  static const String themeKey = 'theme_mode';
}
