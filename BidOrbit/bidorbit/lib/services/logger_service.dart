import 'dart:developer' as developer;
import 'package:flutter/foundation.dart';
import '../config/env_config.dart';

enum LogLevel { debug, info, warning, error }

/// Centralized logging service for the BidOrbit app
/// In production, this could be extended to send logs to a remote service
class LoggerService {
  static final LoggerService _instance = LoggerService._internal();
  factory LoggerService() => _instance;
  LoggerService._internal();

  /// Log a debug message (only in development/staging)
  static void debug(String message, {String? tag, dynamic data}) {
    if (!EnvConfig.enableVerboseLogging) return;
    _log(LogLevel.debug, message, tag: tag, data: data);
  }

  /// Log an info message
  static void info(String message, {String? tag, dynamic data}) {
    _log(LogLevel.info, message, tag: tag, data: data);
  }

  /// Log a warning message
  static void warning(String message, {String? tag, dynamic data}) {
    _log(LogLevel.warning, message, tag: tag, data: data);
  }

  /// Log an error message
  static void error(
    String message, {
    String? tag,
    dynamic data,
    StackTrace? stackTrace,
    dynamic error,
  }) {
    _log(
      LogLevel.error,
      message,
      tag: tag,
      data: data,
      stackTrace: stackTrace,
      error: error,
    );
  }

  /// Internal logging method
  static void _log(
    LogLevel level,
    String message, {
    String? tag,
    dynamic data,
    StackTrace? stackTrace,
    dynamic error,
  }) {
    final timestamp = DateTime.now().toIso8601String();
    final levelStr = level.name.toUpperCase().padRight(7);
    final tagStr = tag != null ? '[$tag] ' : '';
    final logMessage = '[$timestamp] $levelStr $tagStr$message';

    // Print to console in debug mode
    if (kDebugMode) {
      developer.log(
        message,
        time: DateTime.now(),
        level: _getLevelValue(level),
        name: tag ?? 'BidOrbit',
        error: error,
        stackTrace: stackTrace,
      );

      // Also print formatted message
      final colorCode = _getColorCode(level);
      debugPrint('$colorCode$logMessage\x1B[0m');

      if (data != null) {
        debugPrint('  Data: $data');
      }
    }

    // In production, you could send logs to a service like Sentry, Firebase Crashlytics, etc.
    if (EnvConfig.isProduction && level == LogLevel.error) {
      _sendToRemoteLogging(
        level: level,
        message: message,
        tag: tag,
        data: data,
        stackTrace: stackTrace,
        error: error,
      );
    }
  }

  static int _getLevelValue(LogLevel level) {
    switch (level) {
      case LogLevel.debug:
        return 500;
      case LogLevel.info:
        return 800;
      case LogLevel.warning:
        return 900;
      case LogLevel.error:
        return 1000;
    }
  }

  static String _getColorCode(LogLevel level) {
    switch (level) {
      case LogLevel.debug:
        return '\x1B[37m'; // White
      case LogLevel.info:
        return '\x1B[34m'; // Blue
      case LogLevel.warning:
        return '\x1B[33m'; // Yellow
      case LogLevel.error:
        return '\x1B[31m'; // Red
    }
  }

  /// Send logs to remote logging service (placeholder for production)
  static void _sendToRemoteLogging({
    required LogLevel level,
    required String message,
    String? tag,
    dynamic data,
    StackTrace? stackTrace,
    dynamic error,
  }) {
    // TODO: Implement remote logging service integration
    // Examples: Sentry, Firebase Crashlytics, LogRocket, etc.

    // For now, just store in memory or local storage for debugging
    // In production, this would send to a remote service
  }

  /// Log API request
  static void logRequest({
    required String method,
    required String endpoint,
    Map<String, dynamic>? body,
    Map<String, String>? headers,
  }) {
    debug(
      '$method $endpoint',
      tag: 'API',
      data: {
        if (body != null) 'body': body,
        if (headers != null) 'headers': _sanitizeHeaders(headers),
      },
    );
  }

  /// Log API response
  static void logResponse({
    required String method,
    required String endpoint,
    required int statusCode,
    dynamic response,
    Duration? duration,
  }) {
    final level = statusCode >= 200 && statusCode < 300
        ? LogLevel.debug
        : LogLevel.warning;
    _log(
      level,
      '$method $endpoint -> $statusCode${duration != null ? ' (${duration.inMilliseconds}ms)' : ''}',
      tag: 'API',
      data: response,
    );
  }

  /// Log API error
  static void logApiError({
    required String method,
    required String endpoint,
    required dynamic error,
    StackTrace? stackTrace,
  }) {
    error(
      '$method $endpoint failed: $error',
      tag: 'API',
      error: error,
      stackTrace: stackTrace,
    );
  }

  /// Sanitize headers to remove sensitive information
  static Map<String, String> _sanitizeHeaders(Map<String, String> headers) {
    final sanitized = Map<String, String>.from(headers);
    if (sanitized.containsKey('Authorization')) {
      sanitized['Authorization'] = 'Bearer ***';
    }
    return sanitized;
  }
}
