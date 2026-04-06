import 'package:flutter/foundation.dart';
import '../services/api_service.dart';
import '../services/websocket_service.dart';
import '../config/api_config.dart';

class NotificationProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();
  final WebSocketService _wsService = WebSocketService();

  List<Map<String, dynamic>> _notifications = [];
  bool _isLoading = false;
  String? _error;
  int _unreadCount = 0;

  List<Map<String, dynamic>> get notifications => _notifications;
  bool get isLoading => _isLoading;
  String? get error => _error;
  int get unreadCount => _unreadCount;

  // Filter notifications
  List<Map<String, dynamic>> get unreadNotifications =>
      _notifications.where((n) => n['isRead'] == false || n['is_read'] == false).toList();

  List<Map<String, dynamic>> get auctionNotifications =>
      _notifications.where((n) => n['type'] == 'auction' || n['type'] == 'ending_soon').toList();

  List<Map<String, dynamic>> get offerNotifications =>
      _notifications.where((n) => n['type'] == 'offer' || n['type'] == 'outbid').toList();

  NotificationProvider() {
    _initWebSocket();
  }

  void _initWebSocket() {
    // Connect to WebSocket
    _wsService.connect();
    
    // Subscribe to notifications
    _wsService.subscribeToNotifications();
    
    // Listen to real-time notifications
    _wsService.notifications.listen((data) {
      _handleNewNotification(data);
    });
  }

  void _handleNewNotification(Map<String, dynamic> data) {
    // Add new notification to the beginning of the list
    _notifications.insert(0, data);
    
    // Update unread count
    if (data['isRead'] == false || data['is_read'] == false) {
      _unreadCount++;
    }
    
    notifyListeners();
  }

  Future<void> fetchNotifications() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get(ApiConfig.myNotifications);
      
      // Handle different response structures
      if (response is Map<String, dynamic>) {
        if (response.containsKey('notifications')) {
          _notifications = List<Map<String, dynamic>>.from(response['notifications']);
        } else if (response.containsKey('data')) {
          _notifications = List<Map<String, dynamic>>.from(response['data']);
        } else {
          _notifications = [];
        }
      } else if (response is List) {
        _notifications = List<Map<String, dynamic>>.from(response);
      } else {
        _notifications = [];
      }

      // Calculate unread count
      _unreadCount = _notifications.where((n) => 
        n['isRead'] == false || n['is_read'] == false
      ).length;

      _error = null;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      _notifications = [];
      _unreadCount = 0;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> markAsRead(int notificationId) async {
    try {
      await _apiService.put(
        '${ApiConfig.myNotifications}/$notificationId/read',
        {},
      );

      // Update local state
      final index = _notifications.indexWhere((n) => 
        (n['id'] ?? n['notificationId']) == notificationId
      );
      
      if (index != -1) {
        _notifications[index]['isRead'] = true;
        _notifications[index]['is_read'] = true;
        _unreadCount = _notifications.where((n) => 
          n['isRead'] == false || n['is_read'] == false
        ).length;
        notifyListeners();
      }

      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  Future<bool> markAllAsRead() async {
    try {
      // Mark all unread notifications as read
      for (var notification in unreadNotifications) {
        final id = notification['id'] ?? notification['notificationId'];
        if (id != null) {
          await markAsRead(id);
        }
      }
      
      await fetchNotifications();
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
