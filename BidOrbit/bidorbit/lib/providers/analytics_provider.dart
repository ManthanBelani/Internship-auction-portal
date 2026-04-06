import 'package:flutter/foundation.dart';
import '../models/analytics_data.dart';
import '../services/api_service.dart';

class AnalyticsProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  AnalyticsData? _analyticsData;
  bool _isLoading = false;
  String? _error;

  AnalyticsData? get analyticsData => _analyticsData;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchAnalytics({String period = 'month'}) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get('/seller/analytics/revenue?period=$period');
      _analyticsData = AnalyticsData.fromJson(response);
    } catch (e) {
      _error = e.toString();
      debugPrint('Error fetching analytics: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchOverview() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get('/seller/analytics/overview');
      _analyticsData = AnalyticsData.fromJson(response);
    } catch (e) {
      _error = e.toString();
      debugPrint('Error fetching overview: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
