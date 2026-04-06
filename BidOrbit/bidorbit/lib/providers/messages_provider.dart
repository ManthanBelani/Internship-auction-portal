import 'package:flutter/foundation.dart';
import '../models/message.dart';
import '../services/api_service.dart';

class MessagesProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<Conversation> _conversations = [];
  List<Message> _messages = [];
  bool _isLoading = false;
  String? _error;

  List<Conversation> get conversations => _conversations;
  List<Message> get messages => _messages;
  bool get isLoading => _isLoading;
  String? get error => _error;

  int get unreadCount => _conversations.fold(0, (sum, c) => sum + c.unreadCount);

  Future<void> fetchConversations() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get('/seller/messages');
      if (response['conversations'] != null) {
        _conversations = (response['conversations'] as List)
            .map((json) => Conversation.fromJson(json))
            .toList();
      }
    } catch (e) {
      _error = e.toString();
      debugPrint('Error fetching conversations: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchMessages(int conversationId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get('/seller/messages/$conversationId');
      if (response['messages'] != null) {
        _messages = (response['messages'] as List)
            .map((json) => Message.fromJson(json))
            .toList();
      }
    } catch (e) {
      _error = e.toString();
      debugPrint('Error fetching messages: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> sendMessage(int conversationId, String message) async {
    try {
      await _apiService.post('/seller/messages/send', {
        'conversationId': conversationId,
        'message': message,
      });
      await fetchMessages(conversationId);
      await fetchConversations();
      return true;
    } catch (e) {
      _error = e.toString();
      debugPrint('Error sending message: $e');
      notifyListeners();
      return false;
    }
  }

  Future<void> markAsRead(int messageId) async {
    try {
      await _apiService.put('/seller/messages/$messageId/read', {});
      notifyListeners();
    } catch (e) {
      debugPrint('Error marking message as read: $e');
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
