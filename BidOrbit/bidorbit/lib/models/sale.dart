class Sale {
  final int id;
  final int itemId;
  final String itemTitle;
  final String? imageUrl;
  final int buyerId;
  final String buyerName;
  final String buyerEmail;
  final double finalPrice;
  final double shippingCost;
  final double totalAmount;
  final String status;
  final String? trackingNumber;
  final String? carrier;
  final String? shippedAt;
  final String? deliveredAt;
  final String createdAt;
  final SaleShippingAddress? shippingAddress;

  Sale({
    required this.id,
    required this.itemId,
    required this.itemTitle,
    this.imageUrl,
    required this.buyerId,
    required this.buyerName,
    required this.buyerEmail,
    required this.finalPrice,
    required this.shippingCost,
    required this.totalAmount,
    required this.status,
    this.trackingNumber,
    this.carrier,
    this.shippedAt,
    this.deliveredAt,
    required this.createdAt,
    this.shippingAddress,
  });

  factory Sale.fromJson(Map<String, dynamic> json) {
    return Sale(
      id: json['id'] ?? 0,
      itemId: json['itemId'] ?? 0,
      itemTitle: json['itemTitle'] ?? '',
      imageUrl: json['imageUrl'],
      buyerId: json['buyerId'] ?? 0,
      buyerName: json['buyerName'] ?? '',
      buyerEmail: json['buyerEmail'] ?? '',
      finalPrice: (json['finalPrice'] ?? 0).toDouble(),
      shippingCost: (json['shippingCost'] ?? 0).toDouble(),
      totalAmount: (json['totalAmount'] ?? 0).toDouble(),
      status: json['status'] ?? 'paid',
      trackingNumber: json['trackingNumber'],
      carrier: json['carrier'],
      shippedAt: json['shippedAt'],
      deliveredAt: json['deliveredAt'],
      createdAt: json['createdAt'] ?? '',
      shippingAddress: json['fullName'] != null
          ? SaleShippingAddress.fromJson(json)
          : null,
    );
  }

  String get statusDisplay {
    switch (status) {
      case 'paid':
        return 'Paid';
      case 'shipped':
        return 'Shipped';
      case 'delivered':
        return 'Delivered';
      default:
        return status;
    }
  }

  bool get canShip => status == 'paid';
  bool get canMarkDelivered => status == 'shipped';
}

class SaleShippingAddress {
  final String fullName;
  final String addressLine1;
  final String? addressLine2;
  final String city;
  final String state;
  final String zipCode;
  final String country;
  final String phone;

  SaleShippingAddress({
    required this.fullName,
    required this.addressLine1,
    this.addressLine2,
    required this.city,
    required this.state,
    required this.zipCode,
    required this.country,
    required this.phone,
  });

  factory SaleShippingAddress.fromJson(Map<String, dynamic> json) {
    return SaleShippingAddress(
      fullName: json['fullName'] ?? '',
      addressLine1: json['addressLine1'] ?? '',
      addressLine2: json['addressLine2'],
      city: json['city'] ?? '',
      state: json['state'] ?? '',
      zipCode: json['zipCode'] ?? '',
      country: json['country'] ?? '',
      phone: json['phone'] ?? '',
    );
  }

  String get fullAddress {
    final line2 = addressLine2 != null && addressLine2!.isNotEmpty ? '\n$addressLine2' : '';
    return '$addressLine1$line2\n$city, $state $zipCode\n$country';
  }
}
