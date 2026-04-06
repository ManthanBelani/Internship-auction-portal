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
    _initWebSocket();
  }

  void _initWebSocket() {
    // Connect to WebSocket
    _wsService.connect();
    
    // Listen to bid updates
    _wsService.bidUpdates.listen((data) {
      _handleBidUpdate(data);
    });
    
    // Listen to auction status updates
    _wsService.auctionStatusUpdates.listen((data) {
      _handleAuctionStatusUpdate(data);
    });
  }

  void _handleBidUpdate(Map<String, dynamic> data) {
    final itemId = data['itemId'] != null
        ? (data['itemId'] is int
              ? data['itemId']
              : int.tryParse(data['itemId'].toString()))
        : null;
    final newPrice = (data['amount'] ?? data['bidAmount'] as num?)?.toDouble();
    final bidCount = data['bidCount'] as int?;

    if (itemId != null && newPrice != null) {
      _updateItemPrice(itemId, newPrice, bidCount);
    }
  }

  void _handleAuctionStatusUpdate(Map<String, dynamic> data) {
    final itemId = data['itemId'] as int?;
    final status = data['status'] as String?;

    if (itemId != null && status != null) {
      _updateItemStatus(itemId, status);
    }
  }

  void _updateItemPrice(int itemId, double newPrice, int? bidCount) {
    final index = _items.indexWhere((item) => item.id == itemId);
    if (index != -1) {
      _items[index] = Item(
        id: _items[index].id,
        title: _items[index].title,
        description: _items[index].description,
        startingPrice: _items[index].startingPrice,
        currentPrice: newPrice,
        reservePrice: _items[index].reservePrice,
        startTime: _items[index].startTime,
        endTime: _items[index].endTime,
        status: _items[index].status,
        sellerId: _items[index].sellerId,
        sellerName: _items[index].sellerName,
        images: _items[index].images,
        location: _items[index].location,
        category: _items[index].category,
        bidCount: bidCount ?? (_items[index].bidCount + 1),
        isFavorite: _items[index].isFavorite,
        reserveMet: _items[index].reserveMet,
      );

      if (_selectedItem?.id == itemId) {
        _selectedItem = Item(
          id: _selectedItem!.id,
          title: _selectedItem!.title,
          description: _selectedItem!.description,
          startingPrice: _selectedItem!.startingPrice,
          currentPrice: newPrice,
          reservePrice: _selectedItem!.reservePrice,
          startTime: _selectedItem!.startTime,
          endTime: _selectedItem!.endTime,
          status: _selectedItem!.status,
          sellerId: _selectedItem!.sellerId,
          sellerName: _selectedItem!.sellerName,
          images: _selectedItem!.images,
          location: _selectedItem!.location,
          category: _selectedItem!.category,
          bidCount: bidCount ?? (_selectedItem!.bidCount + 1),
          isFavorite: _selectedItem!.isFavorite,
          reserveMet: _selectedItem!.reserveMet,
        );
      }

      notifyListeners();
    }
  }

  void _updateItemStatus(int itemId, String status) {
    final index = _items.indexWhere((item) => item.id == itemId);
    if (index != -1) {
      _items[index] = Item(
        id: _items[index].id,
        title: _items[index].title,
        description: _items[index].description,
        startingPrice: _items[index].startingPrice,
        currentPrice: _items[index].currentPrice,
        reservePrice: _items[index].reservePrice,
        startTime: _items[index].startTime,
        endTime: _items[index].endTime,
        status: status,
        sellerId: _items[index].sellerId,
        sellerName: _items[index].sellerName,
        images: _items[index].images,
        location: _items[index].location,
        category: _items[index].category,
        bidCount: _items[index].bidCount,
        isFavorite: _items[index].isFavorite,
        reserveMet: _items[index].reserveMet,
      );

      if (_selectedItem?.id == itemId) {
        _selectedItem = Item(
          id: _selectedItem!.id,
          title: _selectedItem!.title,
          description: _selectedItem!.description,
          startingPrice: _selectedItem!.startingPrice,
          currentPrice: _selectedItem!.currentPrice,
          reservePrice: _selectedItem!.reservePrice,
          startTime: _selectedItem!.startTime,
          endTime: _selectedItem!.endTime,
          status: status,
          sellerId: _selectedItem!.sellerId,
          sellerName: _selectedItem!.sellerName,
          images: _selectedItem!.images,
          location: _selectedItem!.location,
          category: _selectedItem!.category,
          bidCount: _selectedItem!.bidCount,
          isFavorite: _selectedItem!.isFavorite,
          reserveMet: _selectedItem!.reserveMet,
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

      final response = await _apiService.get('${ApiConfig.items}?$queryString');

      final List<dynamic> itemsJson =
          response['items'] ?? response['data'] ?? [];
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

  Future<void> fetchItemDetails(int itemId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get('${ApiConfig.items}/$itemId');
      _selectedItem = Item.fromJson(response['item'] ?? response);

      // Subscribe to WebSocket updates for this item
      _wsService.subscribeToItem(itemId);

      _error = null;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchItemBids(int itemId) async {
    try {
      final response = await _apiService.get('${ApiConfig.bids}/$itemId');
      final List<dynamic> bidsJson = response['bids'] ?? response['data'] ?? [];
      _itemBids = bidsJson.map((json) => Bid.fromJson(json)).toList();
      notifyListeners();
    } catch (e) {
      print('Error fetching bids: $e');
    }
  }

  Future<bool> placeBid(int itemId, double amount) async {
    try {
      await _apiService.post(ApiConfig.bids, {
        'itemId': itemId,
        'amount': amount,
      });

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
      _wsService.unsubscribeFromItem(_selectedItem!.id);
    }
    super.dispose();
  }
}
