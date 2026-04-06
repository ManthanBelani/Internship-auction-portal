import 'package:flutter/foundation.dart';
import '../models/shipping_address.dart';
import '../services/api_service.dart';

class ShippingProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<ShippingAddress> _addresses = [];
  bool _isLoading = false;
  String? _error;

  List<ShippingAddress> get addresses => _addresses;
  bool get isLoading => _isLoading;
  String? get error => _error;
  ShippingAddress? get defaultAddress =>
      _addresses.firstWhere((a) => a.isDefault, orElse: () => _addresses.isNotEmpty ? _addresses.first : null as ShippingAddress);

  Future<void> fetchAddresses() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get('/shipping/addresses');
      if (response['addresses'] != null) {
        _addresses = (response['addresses'] as List)
            .map((json) => ShippingAddress.fromJson(json))
            .toList();
      }
    } catch (e) {
      _error = e.toString();
      debugPrint('Error fetching addresses: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> addAddress(Map<String, dynamic> data) async {
    try {
      await _apiService.post('/shipping/addresses', data);
      await fetchAddresses();
      return true;
    } catch (e) {
      _error = e.toString();
      debugPrint('Error adding address: $e');
      notifyListeners();
      return false;
    }
  }

  Future<bool> updateAddress(int addressId, Map<String, dynamic> data) async {
    try {
      await _apiService.put('/shipping/addresses/$addressId', data);
      await fetchAddresses();
      return true;
    } catch (e) {
      _error = e.toString();
      debugPrint('Error updating address: $e');
      notifyListeners();
      return false;
    }
  }

  Future<bool> deleteAddress(int addressId) async {
    try {
      await _apiService.delete('/shipping/addresses/$addressId');
      _addresses.removeWhere((a) => a.id == addressId);
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString();
      debugPrint('Error deleting address: $e');
      notifyListeners();
      return false;
    }
  }

  Future<double?> calculateShipping(int itemId, int addressId) async {
    try {
      final response = await _apiService.post('/shipping/calculate', {
        'itemId': itemId,
        'addressId': addressId,
      });
      return (response['shippingCost'] ?? 0).toDouble();
    } catch (e) {
      _error = e.toString();
      debugPrint('Error calculating shipping: $e');
      notifyListeners();
      return null;
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
