class Bid {
  final String id;
  final String itemId;
  final String bidderId;
  final String? bidderName;
  final double amount;
  final DateTime timestamp;
  final String status;
  final String? itemTitle;
  final String? itemImage;

  Bid({
    required this.id,
    required this.itemId,
    required this.bidderId,
    this.bidderName,
    required this.amount,
    required this.timestamp,
    required this.status,
    this.itemTitle,
    this.itemImage,
  });

  factory Bid.fromJson(Map<String, dynamic> json) {
    return Bid(
      id: (json['bidId'] ?? json['id'])?.toString() ?? '',
      itemId: (json['itemId'] ?? json['item_id'])?.toString() ?? '',
      bidderId: (json['bidderId'] ?? json['bidder_id'])?.toString() ?? '',
      bidderName: json['bidderName'] ?? json['bidder_name'],
      amount: (json['amount'] ?? 0).toDouble(),
      timestamp: json['timestamp'] != null
          ? DateTime.parse(json['timestamp'])
          : DateTime.now(),
      status: json['status'] ?? 'active',
      itemTitle: json['itemTitle'] ?? json['item_title'],
      itemImage: json['itemImage'] ?? json['item_image'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'item_id': itemId,
      'bidder_id': bidderId,
      'bidder_name': bidderName,
      'amount': amount,
      'timestamp': timestamp.toIso8601String(),
      'status': status,
      'item_title': itemTitle,
      'item_image': itemImage,
    };
  }

  bool get isWinning => status == 'winning';
  bool get isOutbid => status == 'outbid';
  bool get isWon => status == 'won';
  bool get isLost => status == 'lost';
}
