import 'package:flutter/foundation.dart';
import '../models/order.dart';
import '../models/item.dart';
import '../services/api_service.dart';

class OrderProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<Order> _orders = [];
  List<Item> _wonItems = [];
  bool _isLoading = false;
  String? _error;

  List<Order> get orders => _orders;
  List<Item> get wonItems => _wonItems;
  bool get isLoading => _isLoading;
  String? get error => _error;

  List<Order> get pendingOrders =>
      _orders.where((o) => o.status == 'pending_payment').toList();
  List<Order> get paidOrders =>
      _orders.where((o) => o.status == 'paid').toList();
  List<Order> get shippedOrders =>
      _orders.where((o) => o.status == 'shipped').toList();
  List<Order> get deliveredOrders =>
      _orders.where((o) => o.status == 'delivered').toList();

  Future<void> fetchOrders({String? status}) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final url = status != null ? '/orders?status=$status' : '/orders';
      final response = await _apiService.get(url);
      if (response['orders'] != null) {
        _orders = (response['orders'] as List)
            .map((json) => Order.fromJson(json))
            .toList();
      }
    } catch (e) {
      _error = e.toString();
      debugPrint('Error fetching orders: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchWonItems() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get('/orders/won-items');
      if (response['items'] != null) {
        _wonItems = (response['items'] as List)
            .map((json) => Item.fromJson(json))
            .toList();
      }
    } catch (e) {
      _error = e.toString();
      debugPrint('Error fetching won items: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Order?> getOrderById(int orderId) async {
    try {
      final response = await _apiService.get('/orders/$orderId');
      return Order.fromJson(response);
    } catch (e) {
      _error = e.toString();
      debugPrint('Error fetching order: $e');
      notifyListeners();
      return null;
    }
  }

  Future<bool> createOrder(int itemId, int shippingAddressId) async {
    try {
      await _apiService.post('/orders/create', {
        'itemId': itemId,
        'shippingAddressId': shippingAddressId,
      });
      await fetchOrders();
      await fetchWonItems();
      return true;
    } catch (e) {
      _error = e.toString();
      debugPrint('Error creating order: $e');
      notifyListeners();
      return false;
    }
  }

  Future<bool> updateOrderStatus(int orderId, String status, {String? trackingNumber}) async {
    try {
      await _apiService.put('/orders/$orderId/status', {
        'status': status,
        if (trackingNumber != null) 'trackingNumber': trackingNumber,
      });
      await fetchOrders();
      return true;
    } catch (e) {
      _error = e.toString();
      debugPrint('Error updating order status: $e');
      notifyListeners();
      return false;
    }
  }

  Future<bool> cancelOrder(int orderId) async {
    try {
      await _apiService.post('/orders/$orderId/cancel', {});
      await fetchOrders();
      return true;
    } catch (e) {
      _error = e.toString();
      debugPrint('Error cancelling order: $e');
      notifyListeners();
      return false;
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
