import 'package:flutter/foundation.dart';
import '../models/payout.dart';
import '../services/api_service.dart';

class PayoutProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<Payout> _payouts = [];
  SellerBalance? _balance;
  bool _isLoading = false;
  String? _error;

  List<Payout> get payouts => _payouts;
  SellerBalance? get balance => _balance;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchPayouts() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get('/seller/payouts');
      if (response['payouts'] != null) {
        _payouts = (response['payouts'] as List)
            .map((json) => Payout.fromJson(json))
            .toList();
      }
    } catch (e) {
      _error = e.toString();
      debugPrint('Error fetching payouts: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchBalance() async {
    try {
      final response = await _apiService.get('/seller/balance');
      _balance = SellerBalance.fromJson(response);
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      debugPrint('Error fetching balance: $e');
      notifyListeners();
    }
  }

  Future<bool> requestPayout(double amount) async {
    try {
      await _apiService.post('/seller/payouts/request', {
        'amount': amount,
      });
      await fetchPayouts();
      await fetchBalance();
      return true;
    } catch (e) {
      _error = e.toString();
      debugPrint('Error requesting payout: $e');
      notifyListeners();
      return false;
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
