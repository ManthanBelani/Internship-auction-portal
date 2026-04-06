import 'package:flutter/foundation.dart';
import '../models/sale.dart';
import '../services/api_service.dart';

class SalesProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<Sale> _sales = [];
  bool _isLoading = false;
  String? _error;

  List<Sale> get sales => _sales;
  bool get isLoading => _isLoading;
  String? get error => _error;

  List<Sale> get paidSales => _sales.where((s) => s.status == 'paid').toList();
  List<Sale> get shippedSales => _sales.where((s) => s.status == 'shipped').toList();
  List<Sale> get deliveredSales => _sales.where((s) => s.status == 'delivered').toList();

  Future<void> fetchSales({String? status}) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final url = status != null ? '/seller/sales?status=$status' : '/seller/sales';
      final response = await _apiService.get(url);
      if (response['sales'] != null) {
        _sales = (response['sales'] as List)
            .map((json) => Sale.fromJson(json))
            .toList();
      }
    } catch (e) {
      _error = e.toString();
      debugPrint('Error fetching sales: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Sale?> getSaleDetails(int saleId) async {
    try {
      final response = await _apiService.get('/seller/sales/$saleId');
      return Sale.fromJson(response);
    } catch (e) {
      _error = e.toString();
      debugPrint('Error fetching sale details: $e');
      notifyListeners();
      return null;
    }
  }

  Future<bool> markAsShipped(int saleId, String trackingNumber, {String? carrier}) async {
    try {
      await _apiService.put('/seller/sales/$saleId/ship', {
        'trackingNumber': trackingNumber,
        if (carrier != null) 'carrier': carrier,
      });
      await fetchSales();
      return true;
    } catch (e) {
      _error = e.toString();
      debugPrint('Error marking as shipped: $e');
      notifyListeners();
      return false;
    }
  }

  Future<bool> markAsDelivered(int saleId) async {
    try {
      await _apiService.put('/seller/sales/$saleId/deliver', {});
      await fetchSales();
      return true;
    } catch (e) {
      _error = e.toString();
      debugPrint('Error marking as delivered: $e');
      notifyListeners();
      return false;
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
