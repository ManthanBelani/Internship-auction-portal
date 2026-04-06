class PaymentMethod {
  final int id;
  final String type;
  final String last4;
  final String brand;
  final int expiryMonth;
  final int expiryYear;
  final bool isDefault;

  PaymentMethod({
    required this.id,
    required this.type,
    required this.last4,
    required this.brand,
    required this.expiryMonth,
    required this.expiryYear,
    required this.isDefault,
  });

  factory PaymentMethod.fromJson(Map<String, dynamic> json) {
    return PaymentMethod(
      id: json['id'] ?? 0,
      type: json['type'] ?? 'card',
      last4: json['last4'] ?? '0000',
      brand: json['brand'] ?? 'visa',
      expiryMonth: json['expiryMonth'] ?? 12,
      expiryYear: json['expiryYear'] ?? 2025,
      isDefault: json['isDefault'] == 1 || json['isDefault'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'type': type,
      'last4': last4,
      'brand': brand,
      'expiryMonth': expiryMonth,
      'expiryYear': expiryYear,
      'isDefault': isDefault,
    };
  }

  String get displayName => '${brand.toUpperCase()} •••• $last4';
  String get expiryDisplay => '$expiryMonth/$expiryYear';
}
