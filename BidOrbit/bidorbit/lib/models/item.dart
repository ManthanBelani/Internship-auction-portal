class Item {
  final String id;
  final String title;
  final String description;
  final double startingPrice;
  final double currentPrice;
  final double? reservePrice;
  final DateTime startTime;
  final DateTime endTime;
  final String status;
  final String sellerId;
  final String? sellerName;
  final List<String> images;
  final String? location;
  final String? category;
  final int bidCount;
  final bool isFavorite;

  Item({
    required this.id,
    required this.title,
    required this.description,
    required this.startingPrice,
    required this.currentPrice,
    this.reservePrice,
    required this.startTime,
    required this.endTime,
    required this.status,
    required this.sellerId,
    this.sellerName,
    this.images = const [],
    this.location,
    this.category,
    this.bidCount = 0,
    this.isFavorite = false,
  });

  factory Item.fromJson(Map<String, dynamic> json) {
    return Item(
      id: (json['itemId'] ?? json['id'])?.toString() ?? '',
      title: json['title'] ?? '',
      description: json['description'] ?? '',
      startingPrice: (json['startingPrice'] ?? json['starting_price'] ?? 0).toDouble(),
      currentPrice: (json['currentPrice'] ?? json['current_price'] ?? json['startingPrice'] ?? json['starting_price'] ?? 0).toDouble(),
      reservePrice: (json['reservePrice'] ?? json['reserve_price'])?.toDouble(),
      startTime: (json['startTime'] ?? json['start_time']) != null
          ? DateTime.parse(json['startTime'] ?? json['start_time'])
          : DateTime.now(),
      endTime: (json['endTime'] ?? json['end_time']) != null
          ? DateTime.parse(json['endTime'] ?? json['end_time'])
          : DateTime.now().add(const Duration(days: 7)),
      status: json['status'] ?? 'active',
      sellerId: (json['sellerId'] ?? json['seller_id'])?.toString() ?? '',
      sellerName: json['sellerName'] ?? json['seller_name'],
      images: json['images'] != null
          ? List<String>.from(json['images'])
          : [],
      location: json['location'],
      category: json['category'],
      bidCount: (json['bidCount'] ?? json['bid_count'] ?? 0),
      isFavorite: json['isWatching'] ?? json['is_favorite'] ?? false,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'description': description,
      'starting_price': startingPrice,
      'current_price': currentPrice,
      'reserve_price': reservePrice,
      'start_time': startTime.toIso8601String(),
      'end_time': endTime.toIso8601String(),
      'status': status,
      'seller_id': sellerId,
      'seller_name': sellerName,
      'images': images,
      'location': location,
      'category': category,
      'bid_count': bidCount,
      'is_favorite': isFavorite,
    };
  }

  bool get isActive => status == 'active' && DateTime.now().isBefore(endTime);
  bool get hasEnded => DateTime.now().isAfter(endTime) || status == 'ended';
  
  Duration get timeRemaining {
    if (hasEnded) return Duration.zero;
    return endTime.difference(DateTime.now());
  }

  Item copyWith({
    String? id,
    String? title,
    String? description,
    double? startingPrice,
    double? currentPrice,
    double? reservePrice,
    DateTime? startTime,
    DateTime? endTime,
    String? status,
    String? sellerId,
    String? sellerName,
    List<String>? images,
    String? location,
    String? category,
    int? bidCount,
    bool? isFavorite,
  }) {
    return Item(
      id: id ?? this.id,
      title: title ?? this.title,
      description: description ?? this.description,
      startingPrice: startingPrice ?? this.startingPrice,
      currentPrice: currentPrice ?? this.currentPrice,
      reservePrice: reservePrice ?? this.reservePrice,
      startTime: startTime ?? this.startTime,
      endTime: endTime ?? this.endTime,
      status: status ?? this.status,
      sellerId: sellerId ?? this.sellerId,
      sellerName: sellerName ?? this.sellerName,
      images: images ?? this.images,
      location: location ?? this.location,
      category: category ?? this.category,
      bidCount: bidCount ?? this.bidCount,
      isFavorite: isFavorite ?? this.isFavorite,
    );
  }
}
