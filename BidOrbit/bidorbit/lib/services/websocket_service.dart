import 'dart:async';
import 'dart:convert';
import 'package:web_socket_channel/web_socket_channel.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config/api_config.dart';

enum WebSocketStatus { connecting, connected, disconnected, error }

class WebSocketService {
  static final WebSocketService _instance = WebSocketService._internal();
  factory WebSocketService() => _instance;
  WebSocketService._internal();

  WebSocketChannel? _channel;
  WebSocketStatus _status = WebSocketStatus.disconnected;
  Timer? _reconnectTimer;
  Timer? _heartbeatTimer;
  int _reconnectAttempts = 0;
  static const int _maxReconnectAttempts = 5;
  static const Duration _reconnectDelay = Duration(seconds: 3);
  static const Duration _heartbeatInterval = Duration(seconds: 30);

  // Stream controllers for different event types
  final _bidUpdateController =
      StreamController<Map<String, dynamic>>.broadcast();
  final _auctionStatusController =
      StreamController<Map<String, dynamic>>.broadcast();
  final _notificationController =
      StreamController<Map<String, dynamic>>.broadcast();
  final _statusController = StreamController<WebSocketStatus>.broadcast();

  // Public streams
  Stream<Map<String, dynamic>> get bidUpdates => _bidUpdateController.stream;
  Stream<Map<String, dynamic>> get auctionStatusUpdates =>
      _auctionStatusController.stream;
  Stream<Map<String, dynamic>> get notifications =>
      _notificationController.stream;
  Stream<WebSocketStatus> get statusStream => _statusController.stream;

  WebSocketStatus get status => _status;
  bool get isConnected => _status == WebSocketStatus.connected;

  /// Connect to WebSocket server
  Future<void> connect() async {
    if (_status == WebSocketStatus.connected ||
        _status == WebSocketStatus.connecting) {
      return;
    }

    try {
      _updateStatus(WebSocketStatus.connecting);

      // Get auth token
      const storage = FlutterSecureStorage();
      final token = await storage.read(key: ApiConfig.tokenKey);

      if (token == null) {
        throw Exception('No authentication token found');
      }

      // Build WebSocket URL with token
      final wsUrl = '${ApiConfig.wsUrl}?token=$token';

      // Create WebSocket connection
      _channel = WebSocketChannel.connect(Uri.parse(wsUrl));

      // Listen to messages
      _channel!.stream.listen(
        _handleMessage,
        onError: _handleError,
        onDone: _handleDisconnect,
        cancelOnError: false,
      );

      _updateStatus(WebSocketStatus.connected);
      _reconnectAttempts = 0;
      _startHeartbeat();

      print('WebSocket connected successfully');
    } catch (e) {
      print('WebSocket connection error: $e');
      _updateStatus(WebSocketStatus.error);
      _scheduleReconnect();
    }
  }

  /// Disconnect from WebSocket server
  void disconnect() {
    _reconnectTimer?.cancel();
    _heartbeatTimer?.cancel();
    _channel?.sink.close();
    _channel = null;
    _updateStatus(WebSocketStatus.disconnected);
    print('WebSocket disconnected');
  }

  /// Send message to server
  void send(Map<String, dynamic> message) {
    if (_status != WebSocketStatus.connected) {
      print('Cannot send message: WebSocket not connected');
      return;
    }

    try {
      final jsonMessage = jsonEncode(message);
      _channel?.sink.add(jsonMessage);
    } catch (e) {
      print('Error sending message: $e');
    }
  }

  /// Subscribe to item updates
  void subscribeToItem(int itemId) {
    send({'action': 'subscribe', 'itemId': itemId});
  }

  /// Unsubscribe from item updates
  void unsubscribeFromItem(int itemId) {
    send({'action': 'unsubscribe', 'itemId': itemId});
  }

  /// Subscribe to user notifications
  void subscribeToNotifications() {
    send({'action': 'subscribe', 'channel': 'notifications'});
  }

  /// Handle incoming messages
  void _handleMessage(dynamic message) {
    try {
      final data = jsonDecode(message as String) as Map<String, dynamic>;
      final type = data['type'] as String?;

      switch (type) {
        case 'bid_update':
          _bidUpdateController.add(data);
          break;
        case 'auction_status':
          _auctionStatusController.add(data);
          break;
        case 'notification':
          _notificationController.add(data);
          break;
        case 'pong':
          // Heartbeat response received
          break;
        case 'error':
          print('WebSocket error: ${data['message']}');
          break;
        default:
          print('Unknown message type: $type');
      }
    } catch (e) {
      print('Error handling message: $e');
    }
  }

  /// Handle WebSocket errors
  void _handleError(error) {
    print('WebSocket error: $error');
    _updateStatus(WebSocketStatus.error);
    _scheduleReconnect();
  }

  /// Handle WebSocket disconnect
  void _handleDisconnect() {
    print('WebSocket disconnected');
    _updateStatus(WebSocketStatus.disconnected);
    _heartbeatTimer?.cancel();
    _scheduleReconnect();
  }

  /// Schedule reconnection attempt
  void _scheduleReconnect() {
    if (_reconnectAttempts >= _maxReconnectAttempts) {
      print('Max reconnection attempts reached');
      return;
    }

    _reconnectTimer?.cancel();
    _reconnectTimer = Timer(_reconnectDelay, () {
      _reconnectAttempts++;
      print('Reconnection attempt $_reconnectAttempts/$_maxReconnectAttempts');
      connect();
    });
  }

  /// Start heartbeat to keep connection alive
  void _startHeartbeat() {
    _heartbeatTimer?.cancel();
    _heartbeatTimer = Timer.periodic(_heartbeatInterval, (timer) {
      if (_status == WebSocketStatus.connected) {
        send({'type': 'ping'});
      } else {
        timer.cancel();
      }
    });
  }

  /// Update connection status
  void _updateStatus(WebSocketStatus newStatus) {
    _status = newStatus;
    _statusController.add(newStatus);
  }

  /// Dispose resources
  void dispose() {
    disconnect();
    _bidUpdateController.close();
    _auctionStatusController.close();
    _notificationController.close();
    _statusController.close();
  }
}
