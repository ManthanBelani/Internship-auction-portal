import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/notification_provider.dart';
import '../theme/app_theme.dart';
import 'item_deatils_screen.dart';

class NotificationScreen extends StatefulWidget {
  const NotificationScreen({super.key});

  @override
  State<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends State<NotificationScreen> {
  int _selectedFilter = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<NotificationProvider>().fetchNotifications();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: AppColors.textPrimary),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          Consumer<NotificationProvider>(
            builder: (context, notificationProvider, child) {
              return TextButton(
                onPressed: notificationProvider.unreadCount > 0
                    ? () async {
                        await notificationProvider.markAllAsRead();
                        if (mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              content: Text('All notifications marked as read'),
                            ),
                          );
                        }
                      }
                    : null,
                child: Text(
                  'Mark all as read',
                  style: TextStyle(
                    color: notificationProvider.unreadCount > 0
                        ? AppColors.primary
                        : AppColors.textMuted,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              );
            },
          ),
        ],
      ),
      body: Consumer<NotificationProvider>(
        builder: (context, notificationProvider, child) {
          if (notificationProvider.isLoading &&
              notificationProvider.notifications.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }

          if (notificationProvider.error != null &&
              notificationProvider.notifications.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.error_outline, size: 64, color: AppColors.textMuted),
                  const SizedBox(height: 16),
                  Text(
                    'Error loading notifications',
                    style: TextStyle(fontSize: 18, color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    notificationProvider.error!,
                    style: const TextStyle(fontSize: 14, color: AppColors.textMuted),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 24),
                  ElevatedButton(
                    onPressed: () => notificationProvider.fetchNotifications(),
                    child: const Text('Retry'),
                  ),
                ],
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () => notificationProvider.fetchNotifications(),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Padding(
                  padding: EdgeInsets.fromLTRB(16, 16, 16, 12),
                  child: Text(
                    'Auction Notifications',
                    style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                _buildFilterChips(notificationProvider),
                Expanded(
                  child: _buildNotificationList(notificationProvider),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildFilterChips(NotificationProvider provider) {
    final filters = [
      {'label': 'All', 'count': provider.notifications.length},
      {'label': 'Unread', 'count': provider.unreadCount},
      {'label': 'Auctions', 'count': provider.auctionNotifications.length},
      {'label': 'Offers', 'count': provider.offerNotifications.length},
    ];

    return Container(
      height: 50,
      margin: const EdgeInsets.only(bottom: 8),
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: filters.length,
        itemBuilder: (context, index) {
          final filter = filters[index];
          final isSelected = _selectedFilter == index;
          final count = filter['count'] as int;

          return GestureDetector(
            onTap: () {
              setState(() {
                _selectedFilter = index;
              });
            },
            child: Container(
              margin: const EdgeInsets.only(right: 8),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              decoration: BoxDecoration(
                color: isSelected ? AppColors.textPrimary : AppColors.surface,
                borderRadius: BorderRadius.circular(AppRadius.xl),
                border: Border.all(
                  color: isSelected ? AppColors.textPrimary : AppColors.border,
                ),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    filter['label'] as String,
                    style: TextStyle(
                      color: isSelected ? Colors.white : AppColors.textPrimary,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  if (count > 0 && index == 1) ...[
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 6,
                        vertical: 2,
                      ),
                      decoration: BoxDecoration(
                        color: isSelected ? Colors.white : AppColors.primary,
                        borderRadius: BorderRadius.circular(AppRadius.sm),
                      ),
                      child: Text(
                        count.toString(),
                        style: TextStyle(
                          color: isSelected ? AppColors.textPrimary : Colors.white,
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildNotificationList(NotificationProvider provider) {
    List<Map<String, dynamic>> filteredNotifications;

    switch (_selectedFilter) {
      case 1:
        filteredNotifications = provider.unreadNotifications;
        break;
      case 2:
        filteredNotifications = provider.auctionNotifications;
        break;
      case 3:
        filteredNotifications = provider.offerNotifications;
        break;
      default:
        filteredNotifications = provider.notifications;
    }

    if (filteredNotifications.isEmpty) {
      return _buildEmptyState();
    }

    // Group by date
    final today = <Map<String, dynamic>>[];
    final yesterday = <Map<String, dynamic>>[];
    final earlier = <Map<String, dynamic>>[];

    final now = DateTime.now();
    for (var notification in filteredNotifications) {
      final timestamp = notification['timestamp'] ?? notification['created_at'] ?? '';
      if (timestamp.isEmpty) {
        earlier.add(notification);
        continue;
      }

      try {
        final date = DateTime.parse(timestamp);
        final difference = now.difference(date);

        if (difference.inDays == 0) {
          today.add(notification);
        } else if (difference.inDays == 1) {
          yesterday.add(notification);
        } else {
          earlier.add(notification);
        }
      } catch (e) {
        earlier.add(notification);
      }
    }

    return ListView(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      children: [
        if (today.isNotEmpty) ...[
          _buildDateHeader('TODAY'),
          ...today.map((n) => _buildNotificationCard(n, provider)),
        ],
        if (yesterday.isNotEmpty) ...[
          _buildDateHeader('YESTERDAY'),
          ...yesterday.map((n) => _buildNotificationCard(n, provider)),
        ],
        if (earlier.isNotEmpty) ...[
          _buildDateHeader('EARLIER'),
          ...earlier.map((n) => _buildNotificationCard(n, provider)),
        ],
      ],
    );
  }

  Widget _buildDateHeader(String label) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.bold,
          color: AppColors.textSecondary,
          letterSpacing: 0.5,
        ),
      ),
    );
  }

  Widget _buildNotificationCard(
    Map<String, dynamic> notification,
    NotificationProvider provider,
  ) {
    final id = notification['id'] ?? notification['notificationId'] ?? 0;
    final type = notification['type'] ?? 'general';
    final title = notification['title'] ?? 'Notification';
    final message = notification['message'] ?? '';
    final timestamp = notification['timestamp'] ?? notification['created_at'] ?? '';
    final isRead = notification['isRead'] ?? notification['is_read'] ?? false;
    final itemId = notification['itemId'] ?? notification['item_id'];

    // Type-specific styling
    IconData icon;
    Color iconColor;
    Color iconBgColor;

    switch (type.toLowerCase()) {
      case 'ending_soon':
        icon = Icons.access_time;
        iconColor = AppColors.primary;
        iconBgColor = AppColors.primary.withValues(alpha: 0.1);
        break;
      case 'outbid':
        icon = Icons.trending_down;
        iconColor = AppColors.error;
        iconBgColor = AppColors.error.withValues(alpha: 0.1);
        break;
      case 'won':
        icon = Icons.emoji_events;
        iconColor = AppColors.warning;
        iconBgColor = AppColors.warning.withValues(alpha: 0.1);
        break;
      case 'bid_confirmed':
        icon = Icons.check_circle;
        iconColor = AppColors.success;
        iconBgColor = AppColors.success.withValues(alpha: 0.1);
        break;
      default:
        icon = Icons.notifications;
        iconColor = AppColors.textSecondary;
        iconBgColor = AppColors.textSecondary.withValues(alpha: 0.1);
    }

    return GestureDetector(
      onTap: () async {
        if (!isRead) {
          await provider.markAsRead(id);
        }
        if (itemId != null && mounted) {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => ItemDetailsScreen(itemId: itemId),
            ),
          );
        }
      },
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isRead ? AppColors.surface : AppColors.primary.withValues(alpha: 0.05),
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(
            color: isRead ? AppColors.surfaceVariant : AppColors.primary.withValues(alpha: 0.2),
          ),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: iconBgColor,
                borderRadius: BorderRadius.circular(AppRadius.sm),
              ),
              child: Icon(icon, color: iconColor, size: 24),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          title,
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: isRead ? FontWeight.w500 : FontWeight.bold,
                          ),
                        ),
                      ),
                      if (!isRead)
                        Container(
                          width: 8,
                          height: 8,
                          decoration: const BoxDecoration(
                            color: AppColors.primary,
                            shape: BoxShape.circle,
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    message,
                    style: const TextStyle(
                      fontSize: 13,
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    _formatTimestamp(timestamp),
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppColors.textMuted,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.notifications_none,
            size: 100,
            color: AppColors.border,
          ),
          const SizedBox(height: 24),
          const Text(
            'You\'re all caught up!',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'No notifications to show',
            style: TextStyle(
              fontSize: 14,
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }

  String _formatTimestamp(String timestamp) {
    if (timestamp.isEmpty) return '';

    try {
      final date = DateTime.parse(timestamp);
      final now = DateTime.now();
      final difference = now.difference(date);

      if (difference.inMinutes < 1) {
        return 'Just now';
      } else if (difference.inMinutes < 60) {
        return '${difference.inMinutes}m ago';
      } else if (difference.inHours < 24) {
        return '${difference.inHours}h ago';
      } else if (difference.inDays < 7) {
        return '${difference.inDays}d ago';
      } else {
        return '${(difference.inDays / 7).floor()}w ago';
      }
    } catch (e) {
      return timestamp;
    }
  }
}
