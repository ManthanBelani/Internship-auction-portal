import 'package:flutter/foundation.dart';
import '../models/payment_method.dart';
import '../services/api_service.dart';

class PaymentProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<PaymentMethod> _paymentMethods = [];
  bool _isLoading = false;
  String? _error;

  List<PaymentMethod> get paymentMethods => _paymentMethods;
  bool get isLoading => _isLoading;
  String? get error => _error;
  PaymentMethod? get defaultMethod =>
      _paymentMethods.firstWhere((m) => m.isDefault, orElse: () => _paymentMethods.isNotEmpty ? _paymentMethods.first : null as PaymentMethod);

  Future<void> fetchPaymentMethods() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get('/payments/methods');
      if (response['methods'] != null) {
        _paymentMethods = (response['methods'] as List)
            .map((json) => PaymentMethod.fromJson(json))
            .toList();
      }
    } catch (e) {
      _error = e.toString();
      debugPrint('Error fetching payment methods: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> addPaymentMethod(Map<String, dynamic> data) async {
    try {
      await _apiService.post('/payments/methods', data);
      await fetchPaymentMethods();
      return true;
    } catch (e) {
      _error = e.toString();
      debugPrint('Error adding payment method: $e');
      notifyListeners();
      return false;
    }
  }

  Future<bool> deletePaymentMethod(int methodId) async {
    try {
      await _apiService.delete('/payments/methods/$methodId');
      _paymentMethods.removeWhere((m) => m.id == methodId);
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString();
      debugPrint('Error deleting payment method: $e');
      notifyListeners();
      return false;
    }
  }

  Future<Map<String, dynamic>?> createPaymentIntent(int itemId, double amount) async {
    try {
      final response = await _apiService.post('/payments/create-intent', {
        'itemId': itemId,
        'amount': amount,
      });
      return response;
    } catch (e) {
      _error = e.toString();
      debugPrint('Error creating payment intent: $e');
      notifyListeners();
      return null;
    }
  }

  Future<bool> confirmPayment(int itemId, String paymentIntentId, String paymentMethod) async {
    try {
      await _apiService.post('/payments/confirm', {
        'itemId': itemId,
        'paymentIntentId': paymentIntentId,
        'paymentMethod': paymentMethod,
      });
      return true;
    } catch (e) {
      _error = e.toString();
      debugPrint('Error confirming payment: $e');
      notifyListeners();
      return false;
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
