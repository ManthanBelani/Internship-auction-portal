class Order {
  final int id;
  final int itemId;
  final String itemTitle;
  final String? imageUrl;
  final int buyerId;
  final int sellerId;
  final String sellerName;
  final double totalAmount;
  final double shippingCost;
  final String status;
  final String? trackingNumber;
  final String? shippedAt;
  final String? deliveredAt;
  final String createdAt;
  final ShippingAddressData? shippingAddress;

  Order({
    required this.id,
    required this.itemId,
    required this.itemTitle,
    this.imageUrl,
    required this.buyerId,
    required this.sellerId,
    required this.sellerName,
    required this.totalAmount,
    required this.shippingCost,
    required this.status,
    this.trackingNumber,
    this.shippedAt,
    this.deliveredAt,
    required this.createdAt,
    this.shippingAddress,
  });

  factory Order.fromJson(Map<String, dynamic> json) {
    return Order(
      id: json['id'] ?? 0,
      itemId: json['itemId'] ?? 0,
      itemTitle: json['itemTitle'] ?? '',
      imageUrl: json['imageUrl'],
      buyerId: json['buyerId'] ?? 0,
      sellerId: json['sellerId'] ?? 0,
      sellerName: json['sellerName'] ?? '',
      totalAmount: (json['totalAmount'] ?? 0).toDouble(),
      shippingCost: (json['shippingCost'] ?? 0).toDouble(),
      status: json['status'] ?? 'pending_payment',
      trackingNumber: json['trackingNumber'],
      shippedAt: json['shippedAt'],
      deliveredAt: json['deliveredAt'],
      createdAt: json['createdAt'] ?? '',
      shippingAddress: json['fullName'] != null
          ? ShippingAddressData.fromJson(json)
          : null,
    );
  }

  String get statusDisplay {
    switch (status) {
      case 'pending_payment':
        return 'Awaiting Payment';
      case 'paid':
        return 'Paid';
      case 'shipped':
        return 'Shipped';
      case 'delivered':
        return 'Delivered';
      case 'cancelled':
        return 'Cancelled';
      default:
        return status;
    }
  }

  bool get canCancel => status == 'pending_payment';
  bool get isPaid => status != 'pending_payment' && status != 'cancelled';
}

class ShippingAddressData {
  final String fullName;
  final String addressLine1;
  final String? addressLine2;
  final String city;
  final String state;
  final String zipCode;
  final String country;
  final String phone;

  ShippingAddressData({
    required this.fullName,
    required this.addressLine1,
    this.addressLine2,
    required this.city,
    required this.state,
    required this.zipCode,
    required this.country,
    required this.phone,
  });

  factory ShippingAddressData.fromJson(Map<String, dynamic> json) {
    return ShippingAddressData(
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
