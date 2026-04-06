class AuctionItem {
  final int id;
  final String title;
  final String description;
  final double startingPrice;
  final double? currentBid;
  final DateTime endTime;
  final String? status;
  final List<String> images;
  final int sellerId;
  final int bidCount;

  AuctionItem({
    required this.id,
    required this.title,
    required this.description,
    required this.startingPrice,
    this.currentBid,
    required this.endTime,
    this.status,
    this.images = const [],
    required this.sellerId,
    this.bidCount = 0,
  });

  factory AuctionItem.fromJson(Map<String, dynamic> json) {
    const String serverUrl = 'http://localhost:8000'; // Match your backend URL

    List<String> parsedImages = [];
    if (json['images'] != null) {
      for (var img in json['images']) {
        if (img is Map && img.containsKey('imageUrl')) {
          String url = img['imageUrl'];
          // Ensure URL is absolute
          if (url.startsWith('/')) {
            parsedImages.add('$serverUrl$url');
          } else if (!url.startsWith('http')) {
            parsedImages.add('$serverUrl/$url');
          } else {
            parsedImages.add(url);
          }
        } else if (img is String) {
          parsedImages.add(img);
        }
      }
    }

    return AuctionItem(
      id: json['itemId'] ?? json['id'],
      title: json['title'],
      description: json['description'] ?? '',
      startingPrice: (json['startingPrice'] ?? json['starting_price'] ?? 0.0)
          .toDouble(),
      currentBid: json['currentPrice'] != null
          ? (json['currentPrice']).toDouble()
          : (json['currentBid'] != null
                ? (json['currentBid']).toDouble()
                : null),
      endTime: DateTime.parse(json['endTime'] ?? json['end_time']),
      status: json['status'],
      images: parsedImages,
      sellerId: json['sellerId'] ?? json['seller_id'] ?? 0,
      bidCount: json['bidCount'] ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'description': description,
      'startingPrice': startingPrice,
      'currentBid': currentBid,
      'endTime': endTime.toIso8601String(),
      'status': status,
      'images': images,
      'sellerId': sellerId,
      'bidCount': bidCount,
    };
  }
}
