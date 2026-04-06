class ShippingAddress {
  final int id;
  final String fullName;
  final String addressLine1;
  final String? addressLine2;
  final String city;
  final String state;
  final String zipCode;
  final String country;
  final String phone;
  final String addressType;
  final bool isDefault;

  ShippingAddress({
    required this.id,
    required this.fullName,
    required this.addressLine1,
    this.addressLine2,
    required this.city,
    required this.state,
    required this.zipCode,
    required this.country,
    required this.phone,
    required this.addressType,
    required this.isDefault,
  });

  factory ShippingAddress.fromJson(Map<String, dynamic> json) {
    return ShippingAddress(
      id: json['id'] ?? 0,
      fullName: json['fullName'] ?? '',
      addressLine1: json['addressLine1'] ?? '',
      addressLine2: json['addressLine2'],
      city: json['city'] ?? '',
      state: json['state'] ?? '',
      zipCode: json['zipCode'] ?? '',
      country: json['country'] ?? '',
      phone: json['phone'] ?? '',
      addressType: json['addressType'] ?? 'home',
      isDefault: json['isDefault'] == 1 || json['isDefault'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'fullName': fullName,
      'addressLine1': addressLine1,
      'addressLine2': addressLine2,
      'city': city,
      'state': state,
      'zipCode': zipCode,
      'country': country,
      'phone': phone,
      'addressType': addressType,
      'isDefault': isDefault,
    };
  }

  String get fullAddress {
    final line2 = addressLine2 != null && addressLine2!.isNotEmpty ? '\n$addressLine2' : '';
    return '$addressLine1$line2\n$city, $state $zipCode\n$country';
  }

  String get shortAddress => '$city, $state $zipCode';
}
