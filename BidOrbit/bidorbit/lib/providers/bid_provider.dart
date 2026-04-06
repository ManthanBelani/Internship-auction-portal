import 'package:flutter/foundation.dart';
import '../services/api_service.dart';
import '../config/api_config.dart';

class BidProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<Map<String, dynamic>> _myBids = [];
  bool _isLoading = false;
  String? _error;

  List<Map<String, dynamic>> get myBids => _myBids;
  bool get isLoading => _isLoading;
  String? get error => _error;

  // Filter bids by status
  List<Map<String, dynamic>> get winningBids =>
      _myBids.where((bid) => bid['status'] == 'winning').toList();

  List<Map<String, dynamic>> get outbidBids =>
      _myBids.where((bid) => bid['status'] == 'outbid').toList();

  List<Map<String, dynamic>> get wonBids =>
      _myBids.where((bid) => bid['status'] == 'won').toList();

  List<Map<String, dynamic>> get endedBids =>
      _myBids.where((bid) => bid['status'] == 'ended').toList();

  Future<void> fetchMyBids() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get(ApiConfig.myBids);
      
      // Handle different response structures
      if (response is Map<String, dynamic>) {
        if (response.containsKey('bids')) {
          _myBids = List<Map<String, dynamic>>.from(response['bids']);
        } else if (response.containsKey('data')) {
          _myBids = List<Map<String, dynamic>>.from(response['data']);
        } else {
          _myBids = [];
        }
      } else if (response is List) {
        _myBids = List<Map<String, dynamic>>.from(response);
      } else {
        _myBids = [];
      }

      _error = null;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      _myBids = [];
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> placeBid(int itemId, double amount) async {
    try {
      await _apiService.post(ApiConfig.bids, {
        'itemId': itemId,
        'amount': amount,
      });

      // Refresh bids after placing a new one
      await fetchMyBids();
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
