class Conversation {
  final int id;
  final int userId;
  final String userName;
  final String? userAvatar;
  final String lastMessage;
  final String lastMessageTime;
  final int unreadCount;
  final int? itemId;
  final String? itemTitle;

  Conversation({
    required this.id,
    required this.userId,
    required this.userName,
    this.userAvatar,
    required this.lastMessage,
    required this.lastMessageTime,
    required this.unreadCount,
    this.itemId,
    this.itemTitle,
  });

  factory Conversation.fromJson(Map<String, dynamic> json) {
    return Conversation(
      id: json['id'] ?? 0,
      userId: json['userId'] ?? 0,
      userName: json['userName'] ?? '',
      userAvatar: json['userAvatar'],
      lastMessage: json['lastMessage'] ?? '',
      lastMessageTime: json['lastMessageTime'] ?? '',
      unreadCount: json['unreadCount'] ?? 0,
      itemId: json['itemId'],
      itemTitle: json['itemTitle'],
    );
  }
}

class Message {
  final int id;
  final int conversationId;
  final int senderId;
  final int receiverId;
  final String message;
  final bool isRead;
  final String createdAt;

  Message({
    required this.id,
    required this.conversationId,
    required this.senderId,
    required this.receiverId,
    required this.message,
    required this.isRead,
    required this.createdAt,
  });

  factory Message.fromJson(Map<String, dynamic> json) {
    return Message(
      id: json['id'] ?? 0,
      conversationId: json['conversationId'] ?? 0,
      senderId: json['senderId'] ?? 0,
      receiverId: json['receiverId'] ?? 0,
      message: json['message'] ?? '',
      isRead: json['isRead'] == 1 || json['isRead'] == true,
      createdAt: json['createdAt'] ?? '',
    );
  }
}
