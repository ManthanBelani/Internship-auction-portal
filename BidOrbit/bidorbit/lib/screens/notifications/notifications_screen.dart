import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../models/notification.dart';
import '../../services/api_service.dart';
import '../property/property_details_screen.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({Key? key}) : super(key: key);

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  final ApiService _apiService = ApiService();
  List<AppNotification> _notifications = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _fetchNotifications();
  }

  Future<void> _fetchNotifications() async {
    setState(() => _isLoading = true);

    try {
      final response = await _apiService.get('/notifications');
      final List<dynamic> notificationsJson =
          response['notifications'] ?? response['data'] ?? [];
      setState(() {
        _notifications = notificationsJson
            .map((json) => AppNotification.fromJson(json))
            .toList();
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Failed to load notifications: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _markAsRead(String notificationId) async {
    try {
      await _apiService.put('/notifications/$notificationId/read', {});
      setState(() {
        final index =
            _notifications.indexWhere((n) => n.id == notificationId);
        if (index != -1) {
          _notifications[index] =
              _notifications[index].copyWith(isRead: true);
        }
      });
    } catch (e) {
      print('Failed to mark notification as read: $e');
    }
  }

  IconData _getNotificationIcon(String type) {
    switch (type) {
      case 'bid_placed':
      case 'outbid':
        return Icons.gavel;
      case 'won':
        return Icons.emoji_events;
      case 'ending_soon':
        return Icons.access_time;
      default:
        return Icons.notifications;
    }
  }

  Color _getNotificationColor(String type) {
    switch (type) {
      case 'won':
        return Colors.green;
      case 'outbid':
        return Colors.red;
      case 'ending_soon':
        return Colors.orange;
      default:
        return const Color(0xFF2094F3);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _isLoading && _notifications.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : _notifications.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.notifications_none,
                        size: 64,
                        color: Colors.grey[400],
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'No notifications',
                        style: TextStyle(
                          fontSize: 18,
                          color: Colors.grey[600],
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'You\'ll see notifications here',
                        style: TextStyle(
                          color: Colors.grey[500],
                        ),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _fetchNotifications,
                  child: ListView.separated(
                    itemCount: _notifications.length,
                    separatorBuilder: (context, index) => const Divider(height: 1),
                    itemBuilder: (context, index) {
                      final notification = _notifications[index];
                      return ListTile(
                        leading: CircleAvatar(
                          backgroundColor: _getNotificationColor(notification.type)
                              .withOpacity(0.1),
                          child: Icon(
                            _getNotificationIcon(notification.type),
                            color: _getNotificationColor(notification.type),
                          ),
                        ),
                        title: Text(
                          notification.title,
                          style: TextStyle(
                            fontWeight: notification.isRead
                                ? FontWeight.normal
                                : FontWeight.bold,
                          ),
                        ),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const SizedBox(height: 4),
                            Text(notification.message),
                            const SizedBox(height: 4),
                            Text(
                              DateFormat('MMM dd, yyyy - hh:mm a')
                                  .format(notification.timestamp),
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.grey[600],
                              ),
                            ),
                          ],
                        ),
                        isThreeLine: true,
                        tileColor: notification.isRead
                            ? null
                            : Colors.blue.withOpacity(0.05),
                        onTap: () {
                          if (!notification.isRead) {
                            _markAsRead(notification.id);
                          }
                          if (notification.itemId != null) {
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => PropertyDetailsScreen(
                                  itemId: notification.itemId!,
                                ),
                              ),
                            );
                          }
                        },
                      );
                    },
                  ),
                ),
    );
  }
}
