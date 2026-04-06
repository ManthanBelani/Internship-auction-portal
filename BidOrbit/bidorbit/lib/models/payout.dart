class Payout {
  final int id;
  final int sellerId;
  final double amount;
  final String status;
  final String? bankAccount;
  final String? transactionId;
  final String? processedAt;
  final String createdAt;

  Payout({
    required this.id,
    required this.sellerId,
    required this.amount,
    required this.status,
    this.bankAccount,
    this.transactionId,
    this.processedAt,
    required this.createdAt,
  });

  factory Payout.fromJson(Map<String, dynamic> json) {
    return Payout(
      id: json['id'] ?? 0,
      sellerId: json['sellerId'] ?? 0,
      amount: (json['amount'] ?? 0).toDouble(),
      status: json['status'] ?? 'pending',
      bankAccount: json['bankAccount'],
      transactionId: json['transactionId'],
      processedAt: json['processedAt'],
      createdAt: json['createdAt'] ?? '',
    );
  }

  String get statusDisplay {
    switch (status) {
      case 'pending':
        return 'Pending';
      case 'processing':
        return 'Processing';
      case 'completed':
        return 'Completed';
      case 'failed':
        return 'Failed';
      default:
        return status;
    }
  }
}

class SellerBalance {
  final double available;
  final double pending;
  final double total;

  SellerBalance({
    required this.available,
    required this.pending,
    required this.total,
  });

  factory SellerBalance.fromJson(Map<String, dynamic> json) {
    return SellerBalance(
      available: (json['available'] ?? 0).toDouble(),
      pending: (json['pending'] ?? 0).toDouble(),
      total: (json['total'] ?? 0).toDouble(),
    );
  }
}
