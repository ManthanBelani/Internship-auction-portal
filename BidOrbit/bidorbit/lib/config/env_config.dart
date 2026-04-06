import 'package:flutter/foundation.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';

class EnvConfig {
  static const String _envDevelopment = 'development';
  static const String _envStaging = 'staging';
  static const String _envProduction = 'production';

  static const String environment = String.fromEnvironment(
    'ENVIRONMENT',
    defaultValue: 'development',
  );

  static String get apiBaseUrl {
    switch (environment) {
      case _envProduction:
        return const String.fromEnvironment(
          'API_BASE_URL',
          defaultValue: 'https://api.bidorbit.com/api',
        );
      case _envStaging:
        return const String.fromEnvironment(
          'API_BASE_URL',
          defaultValue: 'https://staging-api.bidorbit.com/api',
        );
      case _envDevelopment:
      default:
        // DEV_SERVER_IP dart-define takes highest priority (passed via flutter run --dart-define)
        const devServerIp = String.fromEnvironment('DEV_SERVER_IP', defaultValue: '');
        if (devServerIp.isNotEmpty) {
          return 'http://$devServerIp:8000/api';
        }
        return dotenv.env['API_BASE_URL'] ?? 'http://10.0.2.2:8000/api';
    }
  }

  static String get wsBaseUrl {
    switch (environment) {
      case _envProduction:
        return const String.fromEnvironment(
          'WS_BASE_URL',
          defaultValue: 'wss://ws.bidorbit.com',
        );
      case _envStaging:
        return const String.fromEnvironment(
          'WS_BASE_URL',
          defaultValue: 'wss://staging-ws.bidorbit.com',
        );
      case _envDevelopment:
      default:
        const devServerIp = String.fromEnvironment('DEV_SERVER_IP', defaultValue: '');
        if (devServerIp.isNotEmpty) {
          return 'ws://$devServerIp:8081';
        }
        return dotenv.env['WS_BASE_URL'] ?? 'ws://10.0.2.2:8081';
    }
  }

  static bool get isDebug => kDebugMode;
  static bool get isRelease => kReleaseMode;
  static bool get isDevelopment => environment == _envDevelopment;
  static bool get isStaging => environment == _envStaging;
  static bool get isProduction => environment == _envProduction;

  static Duration get connectionTimeout {
    if (isProduction) {
      return const Duration(seconds: 15);
    }
    return const Duration(seconds: 30);
  }

  static Duration get receiveTimeout {
    if (isProduction) {
      return const Duration(seconds: 15);
    }
    return const Duration(seconds: 30);
  }

  static bool get enableVerboseLogging => isDevelopment || isStaging;

  static void printConfig() {
    if (!enableVerboseLogging) return;

    debugPrint('=== BidOrbit Configuration ===');
    debugPrint('Environment: $environment');
    debugPrint('API Base URL: $apiBaseUrl');
    debugPrint('WebSocket URL: $wsBaseUrl');
    debugPrint('Debug Mode: $isDebug');
    debugPrint('==============================');
  }
}
