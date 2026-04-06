import 'package:flutter/material.dart';
import 'env_config.dart';

class AppConfig {
  static String get baseUrl => EnvConfig.apiBaseUrl.replaceAll('/api', '');
  static String get apiUrl => EnvConfig.apiBaseUrl;
  static String get wsUrl => EnvConfig.wsBaseUrl;

  static const Color primaryColor = Color(0xFF2094F3);
  static const Color backgroundLight = Color(0xFFF5F7F8);
  static const Color backgroundDark = Color(0xFF101A22);

  static const int requestTimeout = 30;
  static const int maxImageSize = 10 * 1024 * 1024;
  static const int maxImages = 10;

  static const int itemsPerPage = 20;
  static const double minBidIncrement = 10.0;

  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';
  static const String themeKey = 'theme_mode';
}
