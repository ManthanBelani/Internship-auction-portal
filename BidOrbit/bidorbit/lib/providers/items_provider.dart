import 'package:flutter/foundation.dart';
import '../models/item.dart';
import '../models/bid.dart';
import '../services/api_service.dart';
import '../services/websocket_service.dart';
import '../config/api_config.dart';

class ItemsProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();
  final WebSocketService _wsService = WebSocketService();

  List<Item> _items = [];
  Item? _selectedItem;
  List<Bid> _itemBids = [];
  bool _isLoading = false;
  String? _error;
  int _currentPage = 1;
  bool _hasMore = true;
  String? _searchQuery;
  String? _sortBy;
  double? _minPrice;
  double? _maxPrice;

  List<Item> get items => _items;
  Item? get selectedItem => _selectedItem;
  List<Bid> get itemBids => _itemBids;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get hasMore => _hasMore;

  ItemsProvider() {
    // WebSocket disabled until server is available
    // _initWebSocket();
  }

  void _initWebSocket() {
    // Disabled - uncomment when WebSocket server is running
    // _wsService.connect();
    // _wsService.stream.listen((data) {
    //   _handleWebSocketMessage(data);
    // });
  }

  void _handleWebSocketMessage(Map<String, dynamic> data) {
    final type = data['type'];
    
    if (type == 'bid_placed' || type == 'price_update') {
      final itemId = data['itemId']?.toString();
      final newPrice = data['currentPrice']?.toDouble();
      
      if (itemId != null && newPrice != null) {
        _updateItemPrice(itemId, newPrice);
      }
    }
  }

  void _updateItemPrice(String itemId, double newPrice) {
    final index = _items.indexWhere((item) => item.id == itemId);
    if (index != -1) {
      _items[index] = _items[index].copyWith(
        currentPrice: newPrice,
        bidCount: _items[index].bidCount + 1,
      );
      
      if (_selectedItem?.id == itemId) {
        _selectedItem = _selectedItem!.copyWith(
          currentPrice: newPrice,
          bidCount: _selectedItem!.bidCount + 1,
        );
      }
      
      notifyListeners();
    }
  }

  Future<void> fetchItems({bool refresh = false}) async {
    if (refresh) {
      _currentPage = 1;
      _hasMore = true;
      _items.clear();
    }

    if (_isLoading || !_hasMore) return;

    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final queryParams = <String, String>{
        'page': _currentPage.toString(),
        'limit': '20',
      };

      if (_searchQuery != null && _searchQuery!.isNotEmpty) {
        queryParams['search'] = _searchQuery!;
      }
      if (_sortBy != null) {
        queryParams['sort'] = _sortBy!;
      }
      if (_minPrice != null) {
        queryParams['minPrice'] = _minPrice.toString();
      }
      if (_maxPrice != null) {
        queryParams['maxPrice'] = _maxPrice.toString();
      }

      final queryString = queryParams.entries
          .map((e) => '${e.key}=${Uri.encodeComponent(e.value)}')
          .join('&');

      final response = await _apiService.get(
        '${ApiConfig.items}?$queryString',
      );

      final List<dynamic> itemsJson = response['items'] ?? response['data'] ?? [];
      final newItems = itemsJson.map((json) => Item.fromJson(json)).toList();

      if (refresh) {
        _items = newItems;
      } else {
        _items.addAll(newItems);
      }

      _hasMore = newItems.length >= 20;
      _currentPage++;
      _error = null;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchItemDetails(String itemId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get('${ApiConfig.items}/$itemId');
      _selectedItem = Item.fromJson(response['item'] ?? response);
      
      // Subscribe to WebSocket updates for this item
      _wsService.subscribe('item_updates', itemId);
      
      _error = null;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchItemBids(String itemId) async {
    try {
      final response = await _apiService.get('${ApiConfig.bids}/$itemId');
      final List<dynamic> bidsJson = response['bids'] ?? response['data'] ?? [];
      _itemBids = bidsJson.map((json) => Bid.fromJson(json)).toList();
      notifyListeners();
    } catch (e) {
      print('Error fetching bids: $e');
    }
  }

  Future<bool> placeBid(String itemId, double amount) async {
    try {
      await _apiService.post(
        ApiConfig.bids,
        {
          'itemId': itemId,
          'amount': amount,
        },
      );
      
      // Refresh item details
      await fetchItemDetails(itemId);
      await fetchItemBids(itemId);
      
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  void setSearchQuery(String? query) {
    _searchQuery = query;
    fetchItems(refresh: true);
  }

  void setSortBy(String? sortBy) {
    _sortBy = sortBy;
    fetchItems(refresh: true);
  }

  void setPriceRange(double? minPrice, double? maxPrice) {
    _minPrice = minPrice;
    _maxPrice = maxPrice;
    fetchItems(refresh: true);
  }

  void clearFilters() {
    _searchQuery = null;
    _sortBy = null;
    _minPrice = null;
    _maxPrice = null;
    fetchItems(refresh: true);
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }

  @override
  void dispose() {
    if (_selectedItem != null) {
      _wsService.unsubscribe('item_updates', _selectedItem!.id);
    }
    super.dispose();
  }
}
