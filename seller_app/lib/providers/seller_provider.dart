import 'dart:convert';
import 'package:flutter/material.dart';
import '../models/auction_item.dart';
import '../services/api_service.dart';
import 'package:http/http.dart' as http;

class SellerProvider extends ChangeNotifier {
  List<AuctionItem> _items = [];
  Map<String, dynamic>? _stats;
  List<dynamic> _transactions = [];
  List<dynamic> _reviews = [];
  bool _isLoading = false;
  String? _error;

  List<AuctionItem> get items => _items;
  Map<String, dynamic>? get stats => _stats;
  List<dynamic> get transactions => _transactions;
  List<dynamic> get reviews => _reviews;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchMyItems(int sellerId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      // Use the dedicated seller listing endpoint
      final response = await ApiService.get('/seller/listings');
      if (response.statusCode == 200) {
        final Map<String, dynamic> data = jsonDecode(response.body);
        final List<dynamic> listings = data['listings'] ?? [];
        _items = listings.map((item) => AuctionItem.fromJson(item)).toList();
      } else {
        _error = 'Failed to load items: ${response.statusCode}';
      }
    } catch (e) {
      _error = 'Error fetching items: $e';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<void> fetchStats() async {
    _isLoading = true;
    notifyListeners();
    try {
      final response = await ApiService.get('/seller/stats');
      if (response.statusCode == 200) {
        _stats = jsonDecode(response.body);
      }
    } catch (e) {
      print('Fetch stats error: $e');
    }
    _isLoading = false;
    notifyListeners();
  }

  Future<void> fetchTransactions() async {
    _isLoading = true;
    notifyListeners();
    try {
      final response = await ApiService.get('/transactions');
      if (response.statusCode == 200) {
        final Map<String, dynamic> data = jsonDecode(response.body);
        _transactions = data['transactions'] ?? [];
      }
    } catch (e) {
      print('Fetch transactions error: $e');
    }
    _isLoading = false;
    notifyListeners();
  }

  Future<void> fetchReviews(int userId) async {
    _isLoading = true;
    notifyListeners();
    try {
      final response = await ApiService.get('/users/$userId/reviews');
      if (response.statusCode == 200) {
        final Map<String, dynamic> data = jsonDecode(response.body);
        _reviews = data['reviews'] ?? [];
      }
    } catch (e) {
      print('Fetch reviews error: $e');
    }
    _isLoading = false;
    notifyListeners();
  }

  Future<bool> updateShippingStatus(
    int transactionId,
    String trackingNumber,
  ) async {
    try {
      final response = await ApiService.post('/seller/shipping/track', {
        'transactionId': transactionId,
        'trackingNumber': trackingNumber,
      });
      return response.statusCode == 200;
    } catch (e) {
      print('Update shipping error: $e');
      return false;
    }
  }

  Future<bool> requestPayout(double amount, String method) async {
    try {
      final response = await ApiService.post('/seller/payouts', {
        'amount': amount,
        'method': method,
      });
      return response.statusCode == 201 || response.statusCode == 200;
    } catch (e) {
      print('Payout request error: $e');
      return false;
    }
  }

  Future<bool> createAuction(Map<String, dynamic> itemData) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await ApiService.post('/items', itemData);
      if (response.statusCode == 201 || response.statusCode == 200) {
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } catch (e) {
      print('Create auction error: $e');
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  Future<bool> createAuctionWithImage({
    required Map<String, String> fields,
    required String imagePath,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final createResponse = await ApiService.post('/items', fields);
      final Map<String, dynamic> responseData = jsonDecode(createResponse.body);

      if (createResponse.statusCode == 201 ||
          createResponse.statusCode == 200) {
        final int? itemId = responseData['itemId'];

        if (itemId != null) {
          final multipartFile = await http.MultipartFile.fromPath(
            'images[]',
            imagePath,
          );

          final uploadResponse = await ApiService.multipartPost(
            '/seller/items/$itemId/images/bulk',
            fields: {},
            files: [multipartFile],
          );

          if (uploadResponse.statusCode == 201 ||
              uploadResponse.statusCode == 200) {
            _isLoading = false;
            notifyListeners();
            return true;
          } else {
            final errorBody = await uploadResponse.stream.bytesToString();
            final Map<String, dynamic> errorData = jsonDecode(errorBody);
            _error = errorData['message'] ?? 'Image upload failed';
          }
        } else {
          _error = 'Failed to get item ID from response';
        }
      } else {
        _error = responseData['message'] ?? 'Item creation failed';
      }
    } catch (e) {
      _error = 'An error occurred: $e';
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }
}
