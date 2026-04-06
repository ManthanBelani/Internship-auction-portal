class AnalyticsData {
  final RevenueData revenue;
  final PerformanceData performance;
  final List<CategoryData> categories;

  AnalyticsData({
    required this.revenue,
    required this.performance,
    required this.categories,
  });

  factory AnalyticsData.fromJson(Map<String, dynamic> json) {
    return AnalyticsData(
      revenue: RevenueData.fromJson(json['revenue'] ?? {}),
      performance: PerformanceData.fromJson(json['performance'] ?? {}),
      categories: (json['categories'] as List?)
              ?.map((c) => CategoryData.fromJson(c))
              .toList() ??
          [],
    );
  }
}

class RevenueData {
  final double total;
  final double thisMonth;
  final double lastMonth;
  final double growth;
  final List<RevenuePoint> chartData;

  RevenueData({
    required this.total,
    required this.thisMonth,
    required this.lastMonth,
    required this.growth,
    required this.chartData,
  });

  factory RevenueData.fromJson(Map<String, dynamic> json) {
    return RevenueData(
      total: (json['total'] ?? 0).toDouble(),
      thisMonth: (json['thisMonth'] ?? 0).toDouble(),
      lastMonth: (json['lastMonth'] ?? 0).toDouble(),
      growth: (json['growth'] ?? 0).toDouble(),
      chartData: (json['chartData'] as List?)
              ?.map((p) => RevenuePoint.fromJson(p))
              .toList() ??
          [],
    );
  }
}

class RevenuePoint {
  final String date;
  final double amount;

  RevenuePoint({required this.date, required this.amount});

  factory RevenuePoint.fromJson(Map<String, dynamic> json) {
    return RevenuePoint(
      date: json['date'] ?? '',
      amount: (json['amount'] ?? 0).toDouble(),
    );
  }
}

class PerformanceData {
  final int totalSales;
  final double averageSalePrice;
  final int totalListings;
  final double conversionRate;
  final int totalViews;
  final int totalBids;

  PerformanceData({
    required this.totalSales,
    required this.averageSalePrice,
    required this.totalListings,
    required this.conversionRate,
    required this.totalViews,
    required this.totalBids,
  });

  factory PerformanceData.fromJson(Map<String, dynamic> json) {
    return PerformanceData(
      totalSales: json['totalSales'] ?? 0,
      averageSalePrice: (json['averageSalePrice'] ?? 0).toDouble(),
      totalListings: json['totalListings'] ?? 0,
      conversionRate: (json['conversionRate'] ?? 0).toDouble(),
      totalViews: json['totalViews'] ?? 0,
      totalBids: json['totalBids'] ?? 0,
    );
  }
}

class CategoryData {
  final String category;
  final int sales;
  final double revenue;
  final double percentage;

  CategoryData({
    required this.category,
    required this.sales,
    required this.revenue,
    required this.percentage,
  });

  factory CategoryData.fromJson(Map<String, dynamic> json) {
    return CategoryData(
      category: json['category'] ?? '',
      sales: json['sales'] ?? 0,
      revenue: (json['revenue'] ?? 0).toDouble(),
      percentage: (json['percentage'] ?? 0).toDouble(),
    );
  }
}
