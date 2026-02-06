class AppNotification {
  final String id;
  final String userId;
  final String type;
  final String title;
  final String message;
  final String? itemId;
  final String? itemTitle;
  final bool isRead;
  final DateTime timestamp;
  final Map<String, dynamic>? data;

  AppNotification({
    required this.id,
    required this.userId,
    required this.type,
    required this.title,
    required this.message,
    this.itemId,
    this.itemTitle,
    this.isRead = false,
    required this.timestamp,
    this.data,
  });

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id: json['id']?.toString() ?? '',
      userId: json['user_id']?.toString() ?? '',
      type: json['type'] ?? 'info',
      title: json['title'] ?? '',
      message: json['message'] ?? '',
      itemId: json['item_id']?.toString(),
      itemTitle: json['item_title'],
      isRead: json['is_read'] ?? false,
      timestamp: json['timestamp'] != null
          ? DateTime.parse(json['timestamp'])
          : DateTime.now(),
      data: json['data'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'type': type,
      'title': title,
      'message': message,
      'item_id': itemId,
      'item_title': itemTitle,
      'is_read': isRead,
      'timestamp': timestamp.toIso8601String(),
      'data': data,
    };
  }

  AppNotification copyWith({
    String? id,
    String? userId,
    String? type,
    String? title,
    String? message,
    String? itemId,
    String? itemTitle,
    bool? isRead,
    DateTime? timestamp,
    Map<String, dynamic>? data,
  }) {
    return AppNotification(
      id: id ?? this.id,
      userId: userId ?? this.userId,
      type: type ?? this.type,
      title: title ?? this.title,
      message: message ?? this.message,
      itemId: itemId ?? this.itemId,
      itemTitle: itemTitle ?? this.itemTitle,
      isRead: isRead ?? this.isRead,
      timestamp: timestamp ?? this.timestamp,
      data: data ?? this.data,
    );
  }
}
