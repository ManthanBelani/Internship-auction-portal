import 'dart:async';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'logger_service.dart';

/// Connection status enum
enum ConnectionStatus { online, offline, unstable }

/// Service to monitor network connectivity
class ConnectivityService {
  static final ConnectivityService _instance = ConnectivityService._internal();
  factory ConnectivityService() => _instance;
  ConnectivityService._internal();

  final _statusController = StreamController<ConnectionStatus>.broadcast();
  Timer? _checkTimer;

  ConnectionStatus _currentStatus = ConnectionStatus.online;
  DateTime? _lastOnlineTime;

  /// Stream of connection status changes
  Stream<ConnectionStatus> get statusStream => _statusController.stream;

  /// Current connection status
  ConnectionStatus get currentStatus => _currentStatus;

  /// Check if currently online
  bool get isOnline => _currentStatus == ConnectionStatus.online;

  /// Initialize the connectivity service
  void initialize() {
    // Check connectivity periodically
    _checkTimer = Timer.periodic(const Duration(seconds: 30), (_) {
      checkConnectivity();
    });

    // Initial check
    checkConnectivity();

    LoggerService.info('ConnectivityService initialized', tag: 'Network');
  }

  /// Check connectivity by trying to reach a known host
  Future<void> checkConnectivity() async {
    try {
      // Try to reach Google's DNS as a connectivity check
      final result = await InternetAddress.lookup(
        'google.com',
      ).timeout(const Duration(seconds: 5));

      if (result.isNotEmpty && result.first.rawAddress.isNotEmpty) {
        _updateStatus(ConnectionStatus.online);
      } else {
        _updateStatus(ConnectionStatus.offline);
      }
    } catch (e) {
      _updateStatus(ConnectionStatus.offline);
      LoggerService.warning('Connectivity check failed', tag: 'Network');
    }
  }

  /// Update connection status
  void _updateStatus(ConnectionStatus newStatus) {
    if (newStatus != _currentStatus) {
      final previousStatus = _currentStatus;
      _currentStatus = newStatus;

      if (newStatus == ConnectionStatus.online) {
        _lastOnlineTime = DateTime.now();
      }

      _statusController.add(newStatus);

      LoggerService.info(
        'Connection status changed: ${previousStatus.name} -> ${newStatus.name}',
        tag: 'Network',
      );
    }
  }

  /// Check if server is reachable
  Future<bool> checkServerReachable(String host) async {
    try {
      // Remove protocol and port from host
      final uri = Uri.parse(host);
      final hostname = uri.host;

      final result = await InternetAddress.lookup(
        hostname,
      ).timeout(const Duration(seconds: 5));

      return result.isNotEmpty && result.first.rawAddress.isNotEmpty;
    } catch (e) {
      LoggerService.warning(
        'Server unreachable: $host',
        tag: 'Network',
        data: e,
      );
      return false;
    }
  }

  /// Get connection quality score (0-100)
  Future<int> getConnectionQuality() async {
    if (_currentStatus == ConnectionStatus.offline) {
      return 0;
    }

    try {
      // Simple ping test to measure latency
      final stopwatch = Stopwatch()..start();

      // Try to reach Google's DNS
      final result = await InternetAddress.lookup(
        '8.8.8.8',
      ).timeout(const Duration(seconds: 3));

      stopwatch.stop();

      if (result.isEmpty) {
        return 0;
      }

      // Calculate quality based on latency
      final latencyMs = stopwatch.elapsedMilliseconds;

      if (latencyMs < 100) {
        return 100; // Excellent
      } else if (latencyMs < 300) {
        return 80; // Good
      } else if (latencyMs < 500) {
        return 60; // Fair
      } else if (latencyMs < 1000) {
        return 40; // Poor
      } else {
        return 20; // Very poor
      }
    } catch (e) {
      return 0;
    }
  }

  /// Dispose resources
  void dispose() {
    _checkTimer?.cancel();
    _statusController.close();
  }
}
