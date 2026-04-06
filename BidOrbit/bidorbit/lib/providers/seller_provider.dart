import 'dart:io';
import 'package:flutter/foundation.dart';
import '../models/item.dart';
import '../models/sale.dart';
import '../models/payout.dart';
import '../models/analytics_data.dart';
import '../services/api_service.dart';
import '../services/logger_service.dart';
import '../config/api_config.dart';

class SellerProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  // State
  List<Item> _inventory = [];
  List<Sale> _sales = [];
  List<Payout> _payouts = [];
  Map<String, dynamic> _stats = {};
  AnalyticsData? _analytics;
  bool _isLoading = false;
  String? _error;

  // Getters
  List<Item> get inventory => _inventory;
  List<Sale> get sales => _sales;
  List<Payout> get payouts => _payouts;
  Map<String, dynamic> get stats => _stats;
  AnalyticsData? get analytics => _analytics;
  bool get isLoading => _isLoading;
  String? get error => _error;

  /// Fetch seller dashboard stats
  Future<void> fetchSellerStats() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get(ApiConfig.sellerStats);
      _stats = response['data'] ?? response;
      LoggerService.debug('Seller stats loaded', tag: 'SellerProvider');
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      LoggerService.error('Failed to fetch seller stats', error: e);
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Fetch seller's inventory/listings
  Future<void> fetchInventory() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get(ApiConfig.sellerListings);
      final List<dynamic> itemsJson =
          response['items'] ?? response['data'] ?? [];
      _inventory = itemsJson.map((json) => Item.fromJson(json)).toList();
      LoggerService.debug(
        'Inventory loaded: ${_inventory.length} items',
        tag: 'SellerProvider',
      );
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      LoggerService.error('Failed to fetch inventory', error: e);
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Create a new auction item
  Future<bool> createItem(
    Map<String, String> itemData,
    List<File> images,
  ) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      // Step 1: Create the item with JSON data
      final Map<String, dynamic> jsonData = {
        'title': itemData['title'],
        'description': itemData['description'],
        'startingPrice': itemData['startingPrice'],
        'endTime': itemData['endTime'],
      };

      // Add optional fields
      if (itemData.containsKey('reservePrice') &&
          itemData['reservePrice']!.isNotEmpty) {
        jsonData['reservePrice'] = itemData['reservePrice'];
      }
      if (itemData.containsKey('category')) {
        jsonData['category'] = itemData['category'];
      }
      if (itemData.containsKey('condition')) {
        jsonData['condition'] = itemData['condition'];
      }

      final response = await _apiService.post(ApiConfig.items, jsonData);

      // The response structure is: { itemId, title, description, ... }
      final itemId = response['itemId'] ?? response['id'];

      // Step 2: Upload images if any
      if (images.isNotEmpty && itemId != null) {
        await _apiService.postMultipart(
          '/seller/items/$itemId/images/bulk',
          {},
          images,
          fileField: 'images',
        );
      }

      await fetchInventory(); // Refresh inventory
      LoggerService.info(
        'Item created successfully: $itemId',
        tag: 'SellerProvider',
      );
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      LoggerService.error('Failed to create item', error: e);
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Update an existing item
  Future<bool> updateItem(int itemId, Map<String, dynamic> itemData) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      await _apiService.put('/seller/items/$itemId', itemData);
      await fetchInventory(); // Refresh inventory
      LoggerService.info('Item updated: $itemId', tag: 'SellerProvider');
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      LoggerService.error('Failed to update item', error: e);
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Fetch seller's sales
  Future<void> fetchSales() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get(ApiConfig.sellerSales);
      final List<dynamic> salesJson =
          response['sales'] ?? response['data'] ?? [];
      _sales = salesJson.map((json) => Sale.fromJson(json)).toList();
      LoggerService.debug(
        'Sales loaded: ${_sales.length}',
        tag: 'SellerProvider',
      );
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      LoggerService.error('Failed to fetch sales', error: e);
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Get sale details
  Future<Map<String, dynamic>?> getSaleDetails(int saleId) async {
    try {
      final response = await _apiService.get(
        '${ApiConfig.sellerSales}/$saleId',
      );
      return response;
    } catch (e) {
      LoggerService.error('Failed to fetch sale details', error: e);
      return null;
    }
  }

  /// Mark sale as shipped
  Future<bool> markAsShipped(int saleId, String trackingNumber) async {
    try {
      await _apiService.put('/seller/sales/$saleId/ship', {
        'trackingNumber': trackingNumber,
      });
      await fetchSales();
      LoggerService.info(
        'Sale marked as shipped: $saleId',
        tag: 'SellerProvider',
      );
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      LoggerService.error('Failed to mark as shipped', error: e);
      return false;
    }
  }

  /// Mark sale as delivered
  Future<bool> markAsDelivered(int saleId) async {
    try {
      await _apiService.put('/seller/sales/$saleId/deliver', {});
      await fetchSales();
      LoggerService.info(
        'Sale marked as delivered: $saleId',
        tag: 'SellerProvider',
      );
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      LoggerService.error('Failed to mark as delivered', error: e);
      return false;
    }
  }

  /// Fetch seller analytics
  Future<void> fetchAnalytics() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final revenueResponse = await _apiService.get(
        '${ApiConfig.sellerAnalytics}/revenue',
      );
      final performanceResponse = await _apiService.get(
        '${ApiConfig.sellerAnalytics}/performance',
      );
      final categoriesResponse = await _apiService.get(
        '${ApiConfig.sellerAnalytics}/categories',
      );

      // Combine all analytics data
      final combinedData = {
        'revenue': revenueResponse['data'] ?? revenueResponse,
        'performance': performanceResponse['data'] ?? performanceResponse,
        'categories': categoriesResponse['data'] ?? categoriesResponse,
      };

      _analytics = AnalyticsData.fromJson(combinedData);

      LoggerService.debug('Analytics loaded', tag: 'SellerProvider');
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      LoggerService.error('Failed to fetch analytics', error: e);
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Fetch seller's payout history
  Future<void> fetchPayouts() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get(ApiConfig.sellerPayouts);
      final List<dynamic> payoutsJson =
          response['payouts'] ?? response['data'] ?? [];
      _payouts = payoutsJson.map((json) => Payout.fromJson(json)).toList();
      LoggerService.debug(
        'Payouts loaded: ${_payouts.length}',
        tag: 'SellerProvider',
      );
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      LoggerService.error('Failed to fetch payouts', error: e);
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Request a new payout
  Future<bool> requestPayout(double amount, String paymentMethod) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      await _apiService.post(ApiConfig.sellerPayouts, {
        'amount': amount,
        'paymentMethod': paymentMethod,
      });
      await fetchPayouts();
      LoggerService.info('Payout requested: \$$amount', tag: 'SellerProvider');
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      LoggerService.error('Failed to request payout', error: e);
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Get seller balance
  Future<Map<String, dynamic>?> getBalance() async {
    try {
      final response = await _apiService.get(ApiConfig.sellerBalance);
      return response['data'] ?? response;
    } catch (e) {
      LoggerService.error('Failed to fetch balance', error: e);
      return null;
    }
  }

  /// Clear error
  void clearError() {
    _error = null;
    notifyListeners();
  }

  /// Refresh all seller data
  Future<void> refreshAll() async {
    await Future.wait([fetchSellerStats(), fetchInventory(), fetchSales()]);
  }
}
