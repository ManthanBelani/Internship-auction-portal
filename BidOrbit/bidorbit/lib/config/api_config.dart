import 'dart:io';

class ApiConfig {
  // Base URL configuration for different platforms
  static String get baseUrl {
    if (Platform.isAndroid) {
      return 'http://10.241.248.238:8000/api';
    } else if (Platform.isIOS) {
      return 'http://10.241.248.238:8000/api';
    }
    return 'http://10.241.248.238:8000/api';
  }

  static String get wsUrl {
    if (Platform.isAndroid) {
      return 'ws://10.241.248.238:8080';
    } else if (Platform.isIOS) {
      return 'ws://10.241.248.238:8080';
    }
    return 'ws://10.241.248.238:8080';
  }

  // API Endpoints
  static const String register = '/users/register';
  static const String login = '/users/login';
  static const String profile = '/users/profile';
  static const String items = '/items';
  static const String bids = '/bids';
  static const String watchlist = '/watchlist';

  // Timeouts
  static const Duration connectionTimeout = Duration(seconds: 30);
  static const Duration receiveTimeout = Duration(seconds: 30);

  // Storage Keys
  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';
}
