import 'dart:async';
import 'dart:convert';
import 'package:web_socket_channel/web_socket_channel.dart';
import '../config/api_config.dart';

class WebSocketService {
  static final WebSocketService _instance = WebSocketService._internal();
  factory WebSocketService() => _instance;
  WebSocketService._internal();

  WebSocketChannel? _channel;
  final _controller = StreamController<Map<String, dynamic>>.broadcast();
  Timer? _reconnectTimer;
  bool _isConnecting = false;
  int _reconnectAttempts = 0;
  static const int _maxReconnectAttempts = 5;
  static const Duration _reconnectDelay = Duration(seconds: 3);

  Stream<Map<String, dynamic>> get stream => _controller.stream;
  bool get isConnected => _channel != null;

  Future<void> connect() async {
    if (_isConnecting || isConnected) return;

    _isConnecting = true;
    try {
      final uri = Uri.parse(ApiConfig.wsUrl);
      _channel = WebSocketChannel.connect(uri);

      _channel!.stream.listen(
        _onMessage,
        onError: _onError,
        onDone: _onDone,
        cancelOnError: false,
      );

      _reconnectAttempts = 0;
      _isConnecting = false;
      print('WebSocket connected');
    } catch (e) {
      _isConnecting = false;
      print('WebSocket connection error: $e');
      _scheduleReconnect();
    }
  }

  void _onMessage(dynamic message) {
    try {
      final data = jsonDecode(message as String);
      _controller.add(data);
    } catch (e) {
      print('Error parsing WebSocket message: $e');
    }
  }

  void _onError(error) {
    print('WebSocket error: $error');
    _scheduleReconnect();
  }

  void _onDone() {
    print('WebSocket connection closed');
    _channel = null;
    _scheduleReconnect();
  }

  void _scheduleReconnect() {
    if (_reconnectAttempts >= _maxReconnectAttempts) {
      print('Max reconnect attempts reached');
      return;
    }

    _reconnectTimer?.cancel();
    _reconnectTimer = Timer(_reconnectDelay, () {
      _reconnectAttempts++;
      print('Reconnecting... Attempt $_reconnectAttempts');
      connect();
    });
  }

  void subscribe(String event, String itemId) {
    if (!isConnected) return;

    final message = jsonEncode({
      'action': 'subscribe',
      'event': event,
      'itemId': itemId,
    });

    _channel?.sink.add(message);
  }

  void unsubscribe(String event, String itemId) {
    if (!isConnected) return;

    final message = jsonEncode({
      'action': 'unsubscribe',
      'event': event,
      'itemId': itemId,
    });

    _channel?.sink.add(message);
  }

  void send(Map<String, dynamic> data) {
    if (!isConnected) return;
    _channel?.sink.add(jsonEncode(data));
  }

  void disconnect() {
    _reconnectTimer?.cancel();
    _channel?.sink.close();
    _channel = null;
    _reconnectAttempts = 0;
    print('WebSocket disconnected');
  }

  void dispose() {
    disconnect();
    _controller.close();
  }
}
