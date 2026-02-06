import 'package:flutter/foundation.dart';
import '../models/item.dart';
import '../services/api_service.dart';
import '../config/api_config.dart';

class WatchlistProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<Item> _watchlist = [];
  bool _isLoading = false;
  String? _error;
  final Set<String> _watchlistIds = {};

  List<Item> get watchlist => _watchlist;
  bool get isLoading => _isLoading;
  String? get error => _error;

  bool isInWatchlist(String itemId) {
    return _watchlistIds.contains(itemId);
  }

  Future<void> fetchWatchlist() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      print('Fetching watchlist...');
      final response = await _apiService.get(ApiConfig.watchlist);
      print('Watchlist response: $response');
      
      final List<dynamic> itemsJson = response['items'] ?? response['data'] ?? [];
      print('Items count: ${itemsJson.length}');
      
      _watchlist = itemsJson.map((json) => Item.fromJson(json)).toList();
      
      _watchlistIds.clear();
      for (var item in _watchlist) {
        _watchlistIds.add(item.id);
      }
      
      print('Watchlist IDs: $_watchlistIds');
      _error = null;
    } catch (e) {
      print('Watchlist fetch error: $e');
      _error = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> addToWatchlist(String itemId) async {
    try {
      await _apiService.post(
        ApiConfig.watchlist,
        {'itemId': itemId},
      );
      
      _watchlistIds.add(itemId);
      await fetchWatchlist();
      return true;
    } catch (e) {
      final errorMsg = e.toString().replaceAll('Exception: ', '');
      
      // If item is already in watchlist, just update local state
      if (errorMsg.contains('already in watchlist')) {
        _watchlistIds.add(itemId);
        await fetchWatchlist();
        return true;
      }
      
      _error = errorMsg;
      notifyListeners();
      return false;
    }
  }

  Future<bool> removeFromWatchlist(String itemId) async {
    try {
      await _apiService.delete('${ApiConfig.watchlist}/$itemId');
      
      _watchlistIds.remove(itemId);
      _watchlist.removeWhere((item) => item.id == itemId);
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  Future<bool> toggleWatchlist(String itemId) async {
    // Check current state before toggling
    final isCurrentlyInWatchlist = isInWatchlist(itemId);
    
    if (isCurrentlyInWatchlist) {
      return await removeFromWatchlist(itemId);
    } else {
      return await addToWatchlist(itemId);
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
