# Flutter Integration Guide for Auction Portal Backend

## Overview

This guide explains how to connect a Flutter mobile application to the Auction Portal PHP backend. It covers API integration, WebSocket connections, state management, and the necessary Flutter components.

---

## Table of Contents

- [Flutter Integration Guide for Auction Portal Backend](#flutter-integration-guide-for-auction-portal-backend)
  - [Overview](#overview)
  - [Table of Contents](#table-of-contents)
  - [Prerequisites](#prerequisites)
    - [Backend Requirements](#backend-requirements)
    - [Flutter Requirements](#flutter-requirements)
  - [Backend Configuration](#backend-configuration)
    - [1. Update CORS Settings](#1-update-cors-settings)
    - [2. Backend URLs](#2-backend-urls)
    - [3. Test Backend Connectivity](#3-test-backend-connectivity)
  - [Flutter Dependencies](#flutter-dependencies)
  - [Project Structure](#project-structure)
  - [API Service Layer](#api-service-layer)
    - [1. API Configuration (`config/api_config.dart`)](#1-api-configuration-configapi_configdart)
    - [2. Base API Service (`services/api_service.dart`)](#2-base-api-service-servicesapi_servicedart)
  - [WebSocket Integration](#websocket-integration)
    - [WebSocket Service (`services/websocket_service.dart`)](#websocket-service-serviceswebsocket_servicedart)
    - [WebSocket Provider (`providers/websocket_provider.dart`)](#websocket-provider-providerswebsocket_providerdart)
  - [State Management](#state-management)
    - [Authentication Provider (`providers/auth_provider.dart`)](#authentication-provider-providersauth_providerdart)
  - [Core Components](#core-components)
    - [1. Models](#1-models)
      - [User Model (`models/user.dart`)](#user-model-modelsuserdart)
      - [Item Model (`models/item.dart`)](#item-model-modelsitemdart)
    - [2. Services](#2-services)
      - [Auth Service (`services/auth_service.dart`)](#auth-service-servicesauth_servicedart)
      - [Item Service (`services/item_service.dart`)](#item-service-servicesitem_servicedart)
      - [Bid Service (`services/bid_service.dart`)](#bid-service-servicesbid_servicedart)
      - [Watchlist Service (`services/watchlist_service.dart`)](#watchlist-service-serviceswatchlist_servicedart)
      - [Review Service (`services/review_service.dart`)](#review-service-servicesreview_servicedart)
      - [Image Service (`services/image_service.dart`)](#image-service-servicesimage_servicedart)
  - [Authentication Flow](#authentication-flow)
    - [Login Screen (`screens/auth/login_screen.dart`)](#login-screen-screensauthlogin_screendart)
  - [Feature Implementation](#feature-implementation)
    - [Item List Screen (`screens/items/item_list_screen.dart`)](#item-list-screen-screensitemsitem_list_screendart)
    - [Item Detail Screen with WebSocket (`screens/items/item_detail_screen.dart`)](#item-detail-screen-with-websocket-screensitemsitem_detail_screendart)
    - [Countdown Timer Widget (`widgets/countdown_timer.dart`)](#countdown-timer-widget-widgetscountdown_timerdart)
    - [Item Card Widget (`widgets/item_card.dart`)](#item-card-widget-widgetsitem_carddart)
  - [Main App Setup](#main-app-setup)
    - [Main Entry Point (`main.dart`)](#main-entry-point-maindart)
  - [Testing the Integration](#testing-the-integration)
    - [1. Test REST API Connection](#1-test-rest-api-connection)
    - [2. Test WebSocket Connection](#2-test-websocket-connection)
  - [Common Issues \& Solutions](#common-issues--solutions)
    - [Issue 1: Cannot Connect to Localhost](#issue-1-cannot-connect-to-localhost)
    - [Issue 2: CORS Errors](#issue-2-cors-errors)
    - [Issue 3: WebSocket Connection Failed](#issue-3-websocket-connection-failed)
    - [Issue 4: Image Upload Fails](#issue-4-image-upload-fails)
    - [Issue 5: Authentication Token Expired](#issue-5-authentication-token-expired)
  - [Performance Optimization](#performance-optimization)
    - [1. Image Caching](#1-image-caching)
    - [2. Pagination](#2-pagination)
    - [3. WebSocket Reconnection](#3-websocket-reconnection)
  - [Security Best Practices](#security-best-practices)
    - [1. Secure Token Storage](#1-secure-token-storage)
    - [2. HTTPS in Production](#2-https-in-production)
    - [3. Input Validation](#3-input-validation)
    - [4. Error Handling](#4-error-handling)
  - [Deployment Checklist](#deployment-checklist)
    - [Backend](#backend)
    - [Flutter App](#flutter-app)
  - [Additional Resources](#additional-resources)
    - [Backend Documentation](#backend-documentation)
    - [Flutter Resources](#flutter-resources)
    - [Example Projects](#example-projects)
  - [Support](#support)

---

## Prerequisites

### Backend Requirements
- PHP backend running on accessible server (localhost or deployed)
- MySQL database configured
- WebSocket server running on port 8080 (or configured port)
- CORS enabled for mobile app domain

### Flutter Requirements
- Flutter SDK 3.0+
- Dart 3.0+
- Android Studio / Xcode for mobile development

---

## Backend Configuration

### 1. Update CORS Settings

In `public/index.php`, ensure CORS allows your Flutter app:

```php
// Enable CORS for Flutter app
header('Access-Control-Allow-Origin: *'); // Or specify your app domain
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

### 2. Backend URLs


**Development:**
- REST API: `http://localhost/api` (or your local IP for physical devices)
- WebSocket: `ws://localhost:8080`

**Production:**
- REST API: `https://your-domain.com/api`
- WebSocket: `wss://your-domain.com:8080`

### 3. Test Backend Connectivity

```bash
# Test REST API
curl http://localhost/health

# Expected response:
# {"status":"ok","message":"Auction Portal Backend is running"}
```

---

## Flutter Dependencies

Add these to your `pubspec.yaml`:

```yaml
dependencies:
  flutter:
    sdk: flutter
  
  # HTTP & API
  http: ^1.1.0
  dio: ^5.4.0  # Alternative to http with interceptors
  
  # WebSocket
  web_socket_channel: ^2.4.0
  
  # State Management
  provider: ^6.1.1  # Or riverpod, bloc, getx
  
  # Local Storage
  shared_preferences: ^2.2.2
  flutter_secure_storage: ^9.0.0
  
  # JSON Serialization
  json_annotation: ^4.8.1
  
  # Image Handling
  image_picker: ^1.0.7
  cached_network_image: ^3.3.1
  
  # UI Components
  flutter_spinkit: ^5.2.0
  intl: ^0.18.1  # Date formatting
  
dev_dependencies:
  build_runner: ^2.4.8
  json_serializable: ^6.7.1
```

Run: `flutter pub get`

---

## Project Structure

```
lib/
├── main.dart
├── config/
│   ├── api_config.dart          # API endpoints & configuration
│   └── app_config.dart          # App-wide settings
├── models/
│   ├── user.dart
│   ├── item.dart
│   ├── bid.dart
│   ├── transaction.dart
│   ├── review.dart
│   ├── watchlist.dart
│   └── image.dart
├── services/
│   ├── api_service.dart         # Base API service
│   ├── auth_service.dart        # Authentication
│   ├── item_service.dart        # Item operations
│   ├── bid_service.dart         # Bidding
│   ├── review_service.dart      # Reviews
│   ├── watchlist_service.dart   # Watchlist
│   ├── image_service.dart       # Image upload
│   └── websocket_service.dart   # Real-time updates
├── providers/
│   ├── auth_provider.dart
│   ├── item_provider.dart
│   ├── bid_provider.dart
│   └── websocket_provider.dart
├── screens/
│   ├── auth/
│   │   ├── login_screen.dart
│   │   └── register_screen.dart
│   ├── home/
│   │   └── home_screen.dart
│   ├── items/
│   │   ├── item_list_screen.dart
│   │   ├── item_detail_screen.dart
│   │   └── create_item_screen.dart
│   ├── bids/
│   │   └── bid_history_screen.dart
│   ├── profile/
│   │   └── profile_screen.dart
│   ├── watchlist/
│   │   └── watchlist_screen.dart
│   └── reviews/
│       └── review_screen.dart
├── widgets/
│   ├── item_card.dart
│   ├── bid_card.dart
│   ├── review_card.dart
│   └── countdown_timer.dart
└── utils/
    ├── constants.dart
    ├── validators.dart
    └── helpers.dart
```

---


## API Service Layer

### 1. API Configuration (`config/api_config.dart`)

```dart
class ApiConfig {
  // Change these based on your environment
  static const String baseUrl = 'http://10.0.2.2'; // Android emulator localhost
  // static const String baseUrl = 'http://localhost'; // iOS simulator
  // static const String baseUrl = 'http://192.168.1.100'; // Physical device (your local IP)
  // static const String baseUrl = 'https://your-domain.com'; // Production
  
  static const String apiPath = '/api';
  static const String wsUrl = 'ws://10.0.2.2:8080';
  
  // Endpoints
  static String get apiBaseUrl => '$baseUrl$apiPath';
  
  // Auth
  static String get register => '$apiBaseUrl/users/register';
  static String get login => '$apiBaseUrl/users/login';
  static String get profile => '$apiBaseUrl/users/profile';
  
  // Items
  static String get items => '$apiBaseUrl/items';
  static String itemDetail(int id) => '$apiBaseUrl/items/$id';
  static String itemImages(int id) => '$apiBaseUrl/items/$id/images';
  
  // Bids
  static String get bids => '$apiBaseUrl/bids';
  static String bidHistory(int itemId) => '$apiBaseUrl/bids/$itemId';
  
  // Reviews
  static String get reviews => '$apiBaseUrl/reviews';
  static String userReviews(int userId) => '$apiBaseUrl/users/$userId/reviews';
  static String userRating(int userId) => '$apiBaseUrl/users/$userId/rating';
  
  // Watchlist
  static String get watchlist => '$apiBaseUrl/watchlist';
  static String watchlistItem(int itemId) => '$apiBaseUrl/watchlist/$itemId';
  static String checkWatching(int itemId) => '$apiBaseUrl/watchlist/check/$itemId';
  
  // Transactions
  static String get transactions => '$apiBaseUrl/transactions';
  static String transactionDetail(int id) => '$apiBaseUrl/transactions/$id';
}
```

### 2. Base API Service (`services/api_service.dart`)

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiService {
  final _storage = const FlutterSecureStorage();
  
  Future<String?> getToken() async {
    return await _storage.read(key: 'auth_token');
  }
  
  Future<void> saveToken(String token) async {
    await _storage.write(key: 'auth_token', value: token);
  }
  
  Future<void> deleteToken() async {
    await _storage.delete(key: 'auth_token');
  }
  
  Future<Map<String, String>> getHeaders({bool includeAuth = true}) async {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    
    if (includeAuth) {
      final token = await getToken();
      if (token != null) {
        headers['Authorization'] = 'Bearer $token';
      }
    }
    
    return headers;
  }
  
  Future<dynamic> get(String url, {bool requiresAuth = false}) async {
    try {
      final headers = await getHeaders(includeAuth: requiresAuth);
      final response = await http.get(Uri.parse(url), headers: headers);
      return _handleResponse(response);
    } catch (e) {
      throw Exception('Network error: $e');
    }
  }
  
  Future<dynamic> post(String url, Map<String, dynamic> body, {bool requiresAuth = false}) async {
    try {
      final headers = await getHeaders(includeAuth: requiresAuth);
      final response = await http.post(
        Uri.parse(url),
        headers: headers,
        body: json.encode(body),
      );
      return _handleResponse(response);
    } catch (e) {
      throw Exception('Network error: $e');
    }
  }
  
  Future<dynamic> put(String url, Map<String, dynamic> body, {bool requiresAuth = true}) async {
    try {
      final headers = await getHeaders(includeAuth: requiresAuth);
      final response = await http.put(
        Uri.parse(url),
        headers: headers,
        body: json.encode(body),
      );
      return _handleResponse(response);
    } catch (e) {
      throw Exception('Network error: $e');
    }
  }
  
  Future<dynamic> delete(String url, {bool requiresAuth = true}) async {
    try {
      final headers = await getHeaders(includeAuth: requiresAuth);
      final response = await http.delete(Uri.parse(url), headers: headers);
      return _handleResponse(response);
    } catch (e) {
      throw Exception('Network error: $e');
    }
  }
  
  dynamic _handleResponse(http.Response response) {
    if (response.statusCode >= 200 && response.statusCode < 300) {
      if (response.body.isEmpty) return null;
      return json.decode(response.body);
    } else {
      final error = json.decode(response.body);
      throw Exception(error['error'] ?? 'Request failed');
    }
  }
}
```

---


## WebSocket Integration

### WebSocket Service (`services/websocket_service.dart`)

```dart
import 'package:web_socket_channel/web_socket_channel.dart';
import 'dart:convert';
import '../config/api_config.dart';

class WebSocketService {
  WebSocketChannel? _channel;
  Stream? _stream;
  final String token;
  
  WebSocketService(this.token);
  
  void connect() {
    try {
      final uri = Uri.parse('${ApiConfig.wsUrl}?token=$token');
      _channel = WebSocketChannel.connect(uri);
      _stream = _channel!.stream.asBroadcastStream();
      
      print('WebSocket connected');
    } catch (e) {
      print('WebSocket connection error: $e');
    }
  }
  
  void subscribeToItem(int itemId) {
    if (_channel != null) {
      _channel!.sink.add(json.encode({
        'action': 'subscribe',
        'itemId': itemId,
      }));
    }
  }
  
  void unsubscribeFromItem(int itemId) {
    if (_channel != null) {
      _channel!.sink.add(json.encode({
        'action': 'unsubscribe',
        'itemId': itemId,
      }));
    }
  }
  
  Stream<dynamic>? get stream => _stream;
  
  void disconnect() {
    _channel?.sink.close();
    _channel = null;
    _stream = null;
  }
}
```

### WebSocket Provider (`providers/websocket_provider.dart`)

```dart
import 'package:flutter/foundation.dart';
import '../services/websocket_service.dart';
import 'dart:convert';

class WebSocketProvider with ChangeNotifier {
  WebSocketService? _wsService;
  Map<int, dynamic> _itemUpdates = {};
  
  void connect(String token) {
    _wsService = WebSocketService(token);
    _wsService!.connect();
    
    _wsService!.stream?.listen((message) {
      final data = json.decode(message);
      _handleMessage(data);
    });
  }
  
  void _handleMessage(Map<String, dynamic> data) {
    final type = data['type'];
    final itemId = data['itemId'];
    
    switch (type) {
      case 'bid_update':
        _itemUpdates[itemId] = {
          'type': 'bid_update',
          'bidAmount': data['bidAmount'],
          'bidderId': data['bidderId'],
          'bidderName': data['bidderName'],
          'timestamp': data['timestamp'],
          'reserveMet': data['reserveMet'],
        };
        notifyListeners();
        break;
        
      case 'outbid':
        _itemUpdates[itemId] = {
          'type': 'outbid',
          'newBidAmount': data['newBidAmount'],
          'yourBidAmount': data['yourBidAmount'],
        };
        notifyListeners();
        break;
        
      case 'auction_ending':
        _itemUpdates[itemId] = {
          'type': 'auction_ending',
          'secondsRemaining': data['secondsRemaining'],
        };
        notifyListeners();
        break;
        
      case 'auction_ended':
        _itemUpdates[itemId] = {
          'type': 'auction_ended',
          'finalPrice': data['finalPrice'],
          'winnerId': data['winnerId'],
          'winnerName': data['winnerName'],
        };
        notifyListeners();
        break;
    }
  }
  
  void subscribeToItem(int itemId) {
    _wsService?.subscribeToItem(itemId);
  }
  
  void unsubscribeFromItem(int itemId) {
    _wsService?.unsubscribeFromItem(itemId);
  }
  
  dynamic getItemUpdate(int itemId) {
    return _itemUpdates[itemId];
  }
  
  void clearItemUpdate(int itemId) {
    _itemUpdates.remove(itemId);
    notifyListeners();
  }
  
  void disconnect() {
    _wsService?.disconnect();
    _itemUpdates.clear();
  }
}
```

---


## State Management

### Authentication Provider (`providers/auth_provider.dart`)

```dart
import 'package:flutter/foundation.dart';
import '../services/auth_service.dart';
import '../models/user.dart';

class AuthProvider with ChangeNotifier {
  final AuthService _authService = AuthService();
  User? _user;
  bool _isAuthenticated = false;
  bool _isLoading = false;
  
  User? get user => _user;
  bool get isAuthenticated => _isAuthenticated;
  bool get isLoading => _isLoading;
  
  Future<void> checkAuthStatus() async {
    _isLoading = true;
    nry {
      final token = await _authService.getToken();
      if (token != null) {
        _user = await _authService.getProfile();
        _isAuthenticated = true;
      }
    } catch (e) {
      _isAuthenticated = false;
      _user = null;
    }
    
    _isLoading = false;
    notifyListeners();
  }
  
  Future<bool> login(String email, String password) async {
    _isLoading = true;
    notifyListeners();
    
    try {
      final result = await _authService.login(email, password);
      _user = result['user'];
      _isAuthenticated = true;
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      throw e;
    }
  }
  
  Future<bool> register(String name, String email, String password) async {
    _isLoading = true;
    notifyListeners();
    
    try {
      final result = await _authService.register(name, email, password);
      _user = result['user'];
      _isAuthenticated = true;
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      throw e;
    }
  }
  
  Future<void> logout() async {
    await _authService.logout();
    _user = null;
    _isAuthenticated = false;
    notifyListeners();
  }
}
```

---

## Core Components

### 1. Models

#### User Model (`models/user.dart`)

```dart
class User {
  final int userId;
  final String name;
  final String email;
  final String registeredAt;
  final double? averageRating;
  final int? totalReviews;
  
  User({
    required this.userId,
    required this.name,
    required this.email,
    required this.registeredAt,
    this.averageRating,
    this.totalReviews,
  });
  
  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      userId: json['userId'],
      name: json['name'],
      email: json['email'],
      registeredAt: json['registeredAt'],
      averageRating: json['averageRating']?.toDouble(),
      totalReviews: json['totalReviews'],
    );
  }
  
  Map<String, dynamic> toJson() {
    return {
      'userId': userId,
      'name': name,
      'email': email,
      'registeredAt': registeredAt,
      'averageRating': averageRating,
      'totalReviews': totalReviews,
    };
  }
}
```

#### Item Model (`models/item.dart`)

```dart
class Item {
  final int itemId;
  final String title;
  final String description;
  final double startingPrice;
  final double currentPrice;
  final String endTime;
  final int sellerId;
  final String sellerName;
  final String status;
  final List<ItemImage>? images;
  final bool? isWatching;
  final bool? reserveMet;
  final int? bidCount;
  
  Item({
    required this.itemId,
    required this.title,
    required this.description,
    required this.startingPrice,
    required this.currentPrice,
    required this.endTime,
    required this.sellerId,
    required this.sellerName,
    required this.status,
    this.images,
    this.isWatching,
    this.reserveMet,
    this.bidCount,
  });
  
  factory Item.fromJson(Map<String, dynamic> json) {
    return Item(
      itemId: json['itemId'],
      title: json['title'],
      description: json['description'],
      startingPrice: json['startingPrice'].toDouble(),
      currentPrice: json['currentPrice'].toDouble(),
      endTime: json['endTime'],
      sellerId: json['sellerId'],
      sellerName: json['sellerName'],
      status: json['status'],
      images: json['images'] != null
          ? (json['images'] as List).map((i) => ItemImage.fromJson(i)).toList()
          : null,
      isWatching: json['isWatching'],
      reserveMet: json['reserveMet'],
      bidCount: json['bidCount'],
    );
  }
}

class ItemImage {
  final int imageId;
  final String imageUrl;
  final String thumbnailUrl;
  
  ItemImage({
    required this.imageId,
    required this.imageUrl,
    required this.thumbnailUrl,
  });
  
  factory ItemImage.fromJson(Map<String, dynamic> json) {
    return ItemImage(
      imageId: json['imageId'],
      imageUrl: json['imageUrl'],
      thumbnailUrl: json['thumbnailUrl'],
    );
  }
}
```

otifyListeners();
    
    t

### 2. Services

#### Auth Service (`services/auth_service.dart`)

```dart
import 'api_service.dart';
import '../config/api_config.dart';
import '../models/user.dart';

class AuthService extends ApiService {
  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await post(
      ApiConfig.login,
      {'email': email, 'password': password},
    );
    
    await saveToken(response['token']);
    return {
      'user': User.fromJson(response['user']),
      'token': response['token'],
    };
  }
  
  Future<Map<String, dynamic>> register(String name, String email, String password) async {
    final response = await post(
      ApiConfig.register,
      {'name': name, 'email': email, 'password': password},
    );
    
    await saveToken(response['token']);
    return {
      'user': User.fromJson(response['user']),
      'token': response['token'],
    };
  }
  
  Future<User> getProfile() async {
    final response = await get(ApiConfig.profile, requiresAuth: true);
    return User.fromJson(response);
  }
  
  Future<void> logout() async {
    await deleteToken();
  }
}
```

#### Item Service (`services/item_service.dart`)

```dart
import 'api_service.dart';
import '../config/api_config.dart';
import '../models/item.dart';

class ItemService extends ApiService {
  Future<List<Item>> getItems({String? search, int? sellerId}) async {
    String url = ApiConfig.items;
    List<String> params = [];
    
    if (search != null) params.add('search=$search');
    if (sellerId != null) params.add('sellerId=$sellerId');
    
    if (params.isNotEmpty) {
      url += '?${params.join('&')}';
    }
    
    final response = await get(url);
    return (response['items'] as List)
        .map((item) => Item.fromJson(item))
        .toList();
  }
  
  Future<Item> getItemById(int itemId) async {
    final response = await get(ApiConfig.itemDetail(itemId));
    return Item.fromJson(response);
  }
  
  Future<Item> createItem({
    required String title,
    required String description,
    required double startingPrice,
    required String endTime,
  }) async {
    final response = await post(
      ApiConfig.items,
      {
        'title': title,
        'description': description,
        'startingPrice': startingPrice,
        'endTime': endTime,
      },
      requiresAuth: true,
    );
    
    return Item.fromJson(response);
  }
}
```

#### Bid Service (`services/bid_service.dart`)

```dart
import 'api_service.dart';
import '../config/api_config.dart';

class BidService extends ApiService {
  Future<Map<String, dynamic>> placeBid(int itemId, double amount) async {
    final response = await post(
      ApiConfig.bids,
      {'itemId': itemId, 'amount': amount},
      requiresAuth: true,
    );
    return response;
  }
  
  Future<List<dynamic>> getBidHistory(int itemId) async {
    final response = await get(ApiConfig.bidHistory(itemId));
    return response['bids'];
  }
}
```

#### Watchlist Service (`services/watchlist_service.dart`)

```dart
import 'api_service.dart';
import '../config/api_config.dart';
import '../models/item.dart';

class WatchlistService extends ApiService {
  Future<void> addToWatchlist(int itemId) async {
    await post(
      ApiConfig.watchlist,
      {'itemId': itemId},
      requiresAuth: true,
    );
  }
  
  Future<void> removeFromWatchlist(int itemId) async {
    await delete(
      ApiConfig.watchlistItem(itemId),
      requiresAuth: true,
    );
  }
  
  Future<List<Item>> getWatchlist() async {
    final response = await get(ApiConfig.watchlist, requiresAuth: true);
    return (response['items'] as List)
        .map((item) => Item.fromJson(item))
        .toList();
  }
  
  Future<bool> isWatching(int itemId) async {
    final response = await get(
      ApiConfig.checkWatching(itemId),
      requiresAuth: true,
    );
    return response['isWatching'];
  }
}
```

#### Review Service (`services/review_service.dart`)

```dart
import 'api_service.dart';
import '../config/api_config.dart';

class ReviewService extends ApiService {
  Future<void> createReview({
    required int transactionId,
    required int revieweeId,
    required int rating,
    required String reviewText,
  }) async {
    await post(
      ApiConfig.reviews,
      {
        'transactionId': transactionId,
        'revieweeId': revieweeId,
        'rating': rating,
        'reviewText': reviewText,
      },
      requiresAuth: true,
    );
  }
  
  Future<List<dynamic>> getUserReviews(int userId) async {
    final response = await get(ApiConfig.userReviews(userId));
    return response['reviews'];
  }
  
  Future<Map<String, dynamic>> getUserRating(int userId) async {
    final response = await get(ApiConfig.userRating(userId));
    return response;
  }
}
```


#### Image Service (`services/image_service.dart`)

```dart
import 'dart:io';
import 'package:http/http.dart' as http;
import 'api_service.dart';
import '../config/api_config.dart';

class ImageService extends ApiService {
  Future<Map<String, dynamic>> uploadImage(int itemId, File imageFile) async {
    final token = await getToken();
    final uri = Uri.parse(ApiConfig.itemImages(itemId));
    
    var request = http.MultipartRequest('POST', uri);
    request.headers['Authorization'] = 'Bearer $token';
    
    request.files.add(
      await http.MultipartFile.fromPath('image', imageFile.path),
    );
    
    final response = await request.send();
    final responseBody = await response.stream.bytesToString();
    
    if (response.statusCode == 201) {
      return json.decode(responseBody);
    } else {
      throw Exception('Image upload failed');
    }
  }
  
  Future<List<dynamic>> getItemImages(int itemId) async {
    final response = await get(ApiConfig.itemImages(itemId));
    return response['images'];
  }
  
  Future<void> deleteImage(int imageId) async {
    await delete('${ApiConfig.apiBaseUrl}/images/$imageId', requiresAuth: true);
  }
}
```

---

## Authentication Flow

### Login Screen (`screens/auth/login_screen.dart`)

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';

class LoginScreen extends StatefulWidget {
  @override
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;
  
  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;
    
    setState(() => _isLoading = true);
    
    try {
      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      await authProvider.login(
        _emailController.text,
        _passwordController.text,
      );
      
      Navigator.pushReplacementNamed(context, '/home');
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Login failed: $e')),
      );
    } finally {
      setState(() => _isLoading = false);
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Login')),
      body: Padding(
        padding: EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              TextFormField(
                controller: _emailController,
                decoration: InputDecoration(labelText: 'Email'),
                keyboardType: TextInputType.emailAddress,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter email';
                  }
                  return null;
                },
              ),
              SizedBox(height: 16),
              TextFormField(
                controller: _passwordController,
                decoration: InputDecoration(labelText: 'Password'),
                obscureText: true,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter password';
                  }
                  return null;
                },
              ),
              SizedBox(height: 24),
              _isLoading
                  ? CircularProgressIndicator()
                  : ElevatedButton(
                      onPressed: _login,
                      child: Text('Login'),
                    ),
              TextButton(
                onPressed: () => Navigator.pushNamed(context, '/register'),
                child: Text('Don\'t have an account? Register'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

---

## Feature Implementation

### Item List Screen (`screens/items/item_list_screen.dart`)

```dart
import 'package:flutter/material.dart';
import '../../services/item_service.dart';
import '../../models/item.dart';
import '../../widgets/item_card.dart';

class ItemListScreen extends StatefulWidget {
  @override
  _ItemListScreenState createState() => _ItemListScreenState();
}

class _ItemListScreenState extends State<ItemListScreen> {
  final ItemService _itemService = ItemService();
  List<Item> _items = [];
  bool _isLoading = true;
  
  @override
  void initState() {
    super.initState();
    _loadItems();
  }
  
  Future<void> _loadItems() async {
    setState(() => _isLoading = true);
    
    try {
      final items = await _itemService.getItems();
      setState(() {
        _items = items;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to load items: $e')),
      );
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Auctions')),
      body: _isLoading
          ? Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadItems,
              child: ListView.builder(
                itemCount: _items.length,
                itemBuilder: (context, index) {
                  return ItemCard(item: _items[index]);
                },
              ),
            ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => Navigator.pushNamed(context, '/create-item'),
        child: Icon(Icons.add),
      ),
    );
  }
}
```


### Item Detail Screen with WebSocket (`screens/items/item_detail_screen.dart`)

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../services/item_service.dart';
import '../../services/bid_service.dart';
import '../../models/item.dart';
import '../../providers/websocket_provider.dart';
import '../../widgets/countdown_timer.dart';

class ItemDetailScreen extends StatefulWidget {
  final int itemId;
  
  ItemDetailScreen({required this.itemId});
  
  @override
  _ItemDetailScreenState createState() => _ItemDetailScreenState();
}

class _ItemDetailScreenState extends State<ItemDetailScreen> {
  final ItemService _itemService = ItemService();
  final BidService _bidService = BidService();
  final _bidController = TextEditingController();
  
  Item? _item;
  bool _isLoading = true;
  
  @override
  void initState() {
    super.initState();
    _loadItem();
    
    // Subscribe to WebSocket updates
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<WebSocketProvider>(context, listen: false)
          .subscribeToItem(widget.itemId);
    });
  }
  
  @override
  void dispose() {
    Provider.of<WebSocketProvider>(context, listen: false)
        .unsubscribeFromItem(widget.itemId);
    super.dispose();
  }
  
  Future<void> _loadItem() async {
    try {
      final item = await _itemService.getItemById(widget.itemId);
      setState(() {
        _item = item;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to load item: $e')),
      );
    }
  }
  
  Future<void> _placeBid() async {
    final amount = double.tryParse(_bidController.text);
    if (amount == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Please enter a valid amount')),
      );
      return;
    }
    
    try {
      await _bidService.placeBid(widget.itemId, amount);
      _bidController.clear();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Bid placed successfully!')),
      );
      _loadItem(); // Reload item data
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to place bid: $e')),
      );
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Item Details')),
      body: _isLoading
          ? Center(child: CircularProgressIndicator())
          : Consumer<WebSocketProvider>(
              builder: (context, wsProvider, child) {
                // Check for real-time updates
                final update = wsProvider.getItemUpdate(widget.itemId);
                
                if (update != null && update['type'] == 'bid_update') {
                  // Update current price from WebSocket
                  WidgetsBinding.instance.addPostFrameCallback((_) {
                    setState(() {
                      _item = Item(
                        itemId: _item!.itemId,
                        title: _item!.title,
                        description: _item!.description,
                        startingPrice: _item!.startingPrice,
                        currentPrice: update['bidAmount'].toDouble(),
                        endTime: _item!.endTime,
                        sellerId: _item!.sellerId,
                        sellerName: _item!.sellerName,
                        status: _item!.status,
                        images: _item!.images,
                        isWatching: _item!.isWatching,
                        reserveMet: update['reserveMet'],
                        bidCount: _item!.bidCount,
                      );
                    });
                    wsProvider.clearItemUpdate(widget.itemId);
                  });
                }
                
                return SingleChildScrollView(
                  padding: EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Image carousel
                      if (_item!.images != null && _item!.images!.isNotEmpty)
                        Container(
                          height: 250,
                          child: PageView.builder(
                            itemCount: _item!.images!.length,
                            itemBuilder: (context, index) {
                              return Image.network(
                                _item!.images![index].imageUrl,
                                fit: BoxFit.cover,
                              );
                            },
                          ),
                        ),
                      
                      SizedBox(height: 16),
                      
                      // Title
                      Text(
                        _item!.title,
                        style: Theme.of(context).textTheme.headlineSmall,
                      ),
                      
                      SizedBox(height: 8),
                      
                      // Current price
                      Text(
                        'Current Price: \$${_item!.currentPrice.toStringAsFixed(2)}',
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(
                          color: Colors.green,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      
                      SizedBox(height: 8),
                      
                      // Reserve met indicator
                      if (_item!.reserveMet != null)
                        Chip(
                          label: Text(
                            _item!.reserveMet! ? 'Reserve Met' : 'Reserve Not Met',
                          ),
                          backgroundColor: _item!.reserveMet! 
                              ? Colors.green.shade100 
                              : Colors.orange.shade100,
                        ),
                      
                      SizedBox(height: 16),
                      
                      // Countdown timer
                      CountdownTimer(endTime: DateTime.parse(_item!.endTime)),
                      
                      SizedBox(height: 16),
                      
                      // Description
                      Text(
                        _item!.description,
                        style: Theme.of(context).textTheme.bodyLarge,
                      ),
                      
                      SizedBox(height: 24),
                      
                      // Bid form
                      if (_item!.status == 'active')
                        Column(
                          children: [
                            TextField(
                              controller: _bidController,
                              decoration: InputDecoration(
                                labelText: 'Your Bid Amount',
                                prefixText: '\$',
                                border: OutlineInputBorder(),
                              ),
                              keyboardType: TextInputType.number,
                            ),
                            SizedBox(height: 16),
                            ElevatedButton(
                              onPressed: _placeBid,
                              child: Text('Place Bid'),
                              style: ElevatedButton.styleFrom(
                                minimumSize: Size(double.infinity, 50),
                              ),
                            ),
                          ],
                        ),
                    ],
                  ),
                );
              },
            ),
    );
  }
}
```

### Countdown Timer Widget (`widgets/countdown_timer.dart`)

```dart
import 'package:flutter/material.dart';
import 'dart:async';

class CountdownTimer extends StatefulWidget {
  final DateTime endTime;
  
  CountdownTimer({required this.endTime});
  
  @override
  _CountdownTimerState createState() => _CountdownTimerState();
}

class _CountdownTimerState extends State<CountdownTimer> {
  late Timer _timer;
  Duration _remaining = Duration.zero;
  
  @override
  void initState() {
    super.initState();
    _updateRemaining();
    _timer = Timer.periodic(Duration(seconds: 1), (_) => _updateRemaining());
  }
  
  void _updateRemaining() {
    setState(() {
      _remaining = widget.endTime.difference(DateTime.now());
      if (_remaining.isNegative) {
        _remaining = Duration.zero;
        _timer.cancel();
      }
    });
  }
  
  @override
  void dispose() {
    _timer.cancel();
    super.dispose();
  }
  
  String _formatDuration(Duration duration) {
    String twoDigits(int n) => n.toString().padLeft(2, '0');
    final hours = twoDigits(duration.inHours);
    final minutes = twoDigits(duration.inMinutes.remainder(60));
    final seconds = twoDigits(duration.inSeconds.remainder(60));
    return '$hours:$minutes:$seconds';
  }
  
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: _remaining.inHours < 1 ? Colors.red.shade100 : Colors.blue.shade100,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.timer, size: 20),
          SizedBox(width: 8),
          Text(
            _remaining.inSeconds > 0 
                ? 'Ends in: ${_formatDuration(_remaining)}'
                : 'Auction Ended',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }
}
```


### Item Card Widget (`widgets/item_card.dart`)

```dart
import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../models/item.dart';

class ItemCard extends StatelessWidget {
  final Item item;
  
  ItemCard({required this.item});
  
  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: InkWell(
        onTap: () {
          Navigator.pushNamed(
            context,
            '/item-detail',
            arguments: item.itemId,
          );
        },
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Image
            if (item.images != null && item.images!.isNotEmpty)
              CachedNetworkImage(
                imageUrl: item.images!.first.thumbnailUrl,
                height: 200,
                width: double.infinity,
                fit: BoxFit.cover,
                placeholder: (context, url) => Container(
                  height: 200,
                  color: Colors.grey.shade200,
                  child: Center(child: CircularProgressIndicator()),
                ),
                errorWidget: (context, url, error) => Container(
                  height: 200,
                  color: Colors.grey.shade300,
                  child: Icon(Icons.error),
                ),
              )
            else
              Container(
                height: 200,
                color: Colors.grey.shade300,
                child: Icon(Icons.image, size: 50),
              ),
            
            Padding(
              padding: EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Title
                  Text(
                    item.title,
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  
                  SizedBox(height: 8),
                  
                  // Current price
                  Text(
                    '\$${item.currentPrice.toStringAsFixed(2)}',
                    style: TextStyle(
                      fontSize: 20,
                      color: Colors.green,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  
                  SizedBox(height: 4),
                  
                  // Seller
                  Text(
                    'Seller: ${item.sellerName}',
                    style: TextStyle(color: Colors.grey.shade600),
                  ),
                  
                  SizedBox(height: 4),
                  
                  // Status badges
                  Row(
                    children: [
                      if (item.reserveMet != null)
                        Chip(
                          label: Text(
                            item.reserveMet! ? 'Reserve Met' : 'Reserve',
                            style: TextStyle(fontSize: 12),
                          ),
                          backgroundColor: item.reserveMet! 
                              ? Colors.green.shade100 
                              : Colors.orange.shade100,
                          padding: EdgeInsets.zero,
                        ),
                      
                      if (item.isWatching == true)
                        Padding(
                          padding: EdgeInsets.only(left: 8),
                          child: Icon(Icons.favorite, color: Colors.red, size: 20),
                        ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
```

---

## Main App Setup

### Main Entry Point (`main.dart`)

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'providers/auth_provider.dart';
import 'providers/websocket_provider.dart';
import 'screens/auth/login_screen.dart';
import 'screens/auth/register_screen.dart';
import 'screens/home/home_screen.dart';
import 'screens/items/item_list_screen.dart';
import 'screens/items/item_detail_screen.dart';
import 'screens/items/create_item_screen.dart';
import 'screens/watchlist/watchlist_screen.dart';
import 'screens/profile/profile_screen.dart';

void main() {
  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => WebSocketProvider()),
      ],
      child: MyApp(),
    ),
  );
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Auction Portal',
      theme: ThemeData(
        primarySwatch: Colors.blue,
        visualDensity: VisualDensity.adaptivePlatformDensity,
      ),
      home: AuthWrapper(),
      routes: {
        '/login': (context) => LoginScreen(),
        '/register': (context) => RegisterScreen(),
        '/home': (context) => HomeScreen(),
        '/items': (context) => ItemListScreen(),
        '/watchlist': (context) => WatchlistScreen(),
        '/profile': (context) => ProfileScreen(),
      },
      onGenerateRoute: (settings) {
        if (settings.name == '/item-detail') {
          final itemId = settings.arguments as int;
          return MaterialPageRoute(
            builder: (context) => ItemDetailScreen(itemId: itemId),
          );
        }
        return null;
      },
    );
  }
}

class AuthWrapper extends StatefulWidget {
  @override
  _AuthWrapperState createState() => _AuthWrapperState();
}

class _AuthWrapperState extends State<AuthWrapper> {
  @override
  void initState() {
    super.initState();
    _checkAuth();
  }
  
  Future<void> _checkAuth() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    await authProvider.checkAuthStatus();
    
    if (authProvider.isAuthenticated) {
      // Connect to WebSocket
      final wsProvider = Provider.of<WebSocketProvider>(context, listen: false);
      final token = await authProvider._authService.getToken();
      if (token != null) {
        wsProvider.connect(token);
      }
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Consumer<AuthProvider>(
      builder: (context, authProvider, child) {
        if (authProvider.isLoading) {
          return Scaffold(
            body: Center(child: CircularProgressIndicator()),
          );
        }
        
        if (authProvider.isAuthenticated) {
          return HomeScreen();
        }
        
        return LoginScreen();
      },
    );
  }
}
```

---

## Testing the Integration

### 1. Test REST API Connection

```dart
// Add this test button to your home screen
ElevatedButton(
  onPressed: () async {
    try {
      final items = await ItemService().getItems();
      print('Loaded ${items.length} items');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('API Connected! Found ${items.length} items')),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('API Error: $e')),
      );
    }
  },
  child: Text('Test API Connection'),
)
```

### 2. Test WebSocket Connection

```dart
// Add this to your item detail screen
Consumer<WebSocketProvider>(
  builder: (context, wsProvider, child) {
    final update = wsProvider.getItemUpdate(widget.itemId);
    if (update != null) {
      return Text('WebSocket Active: ${update['type']}');
    }
    return Text('WebSocket: Waiting for updates...');
  },
)
```

---


## Common Issues & Solutions

### Issue 1: Cannot Connect to Localhost

**Problem:** Flutter app can't reach `http://localhost`

**Solutions:**
- **Android Emulator:** Use `http://10.0.2.2` instead of `localhost`
- **iOS Simulator:** Use `http://localhost` or `http://127.0.0.1`
- **Physical Device:** Use your computer's local IP (e.g., `http://192.168.1.100`)

Find your local IP:
```bash
# Windows
ipconfig

# Mac/Linux
ifconfig
```

### Issue 2: CORS Errors

**Problem:** Browser/app blocked by CORS policy

**Solution:** Ensure backend has proper CORS headers in `public/index.php`:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

### Issue 3: WebSocket Connection Failed

**Problem:** WebSocket won't connect

**Solutions:**
1. Ensure WebSocket server is running: `php bin/websocket-server.php`
2. Check firewall allows port 8080
3. Use correct WebSocket URL format: `ws://` (not `http://`)
4. For Android emulator: `ws://10.0.2.2:8080`

### Issue 4: Image Upload Fails

**Problem:** Images won't upload

**Solutions:**
1. Check `uploads/` directory exists and has write permissions
2. Verify `MAX_FILE_SIZE` in `.env` (default 5MB)
3. Ensure GD library is installed: `php -m | grep gd`
4. Check file MIME type is allowed (JPG, PNG, WEBP)

### Issue 5: Authentication Token Expired

**Problem:** API returns 401 Unauthorized

**Solution:** Implement token refresh or re-login:
```dart
// In your API service
if (response.statusCode == 401) {
  await deleteToken();
  // Navigate to login screen
  Navigator.pushReplacementNamed(context, '/login');
}
```

---

## Performance Optimization

### 1. Image Caching

Use `cached_network_image` for better performance:
```dart
CachedNetworkImage(
  imageUrl: item.images!.first.thumbnailUrl,
  placeholder: (context, url) => CircularProgressIndicator(),
  errorWidget: (context, url, error) => Icon(Icons.error),
)
```

### 2. Pagination

Implement pagination for large item lists:
```dart
class ItemListScreen extends StatefulWidget {
  // ... existing code
  
  int _page = 1;
  bool _hasMore = true;
  
  Future<void> _loadMore() async {
    if (!_hasMore) return;
    
    _page++;
    final newItems = await _itemService.getItems(page: _page);
    
    setState(() {
      _items.addAll(newItems);
      _hasMore = newItems.isNotEmpty;
    });
  }
}
```

### 3. WebSocket Reconnection

Implement automatic reconnection:
```dart
class WebSocketService {
  Timer? _reconnectTimer;
  
  void connect() {
    try {
      // ... existing connection code
      
      _channel!.stream.listen(
        (message) => _handleMessage(message),
        onDone: () => _reconnect(),
        onError: (error) => _reconnect(),
      );
    } catch (e) {
      _reconnect();
    }
  }
  
  void _reconnect() {
    _reconnectTimer?.cancel();
    _reconnectTimer = Timer(Duration(seconds: 5), () {
      print('Reconnecting WebSocket...');
      connect();
    });
  }
}
```

---

## Security Best Practices

### 1. Secure Token Storage

Always use `flutter_secure_storage` for tokens:
```dart
final storage = FlutterSecureStorage();
await storage.write(key: 'auth_token', value: token);
```

### 2. HTTPS in Production

Update `ApiConfig` for production:
```dart
static const String baseUrl = 'https://your-domain.com';
static const String wsUrl = 'wss://your-domain.com:8080';
```

### 3. Input Validation

Validate all user inputs before sending to API:
```dart
String? validateBidAmount(String? value) {
  if (value == null || value.isEmpty) {
    return 'Please enter bid amount';
  }
  
  final amount = double.tryParse(value);
  if (amount == null || amount <= 0) {
    return 'Please enter a valid amount';
  }
  
  return null;
}
```

### 4. Error Handling

Implement comprehensive error handling:
```dart
try {
  final result = await apiCall();
  // Handle success
} on SocketException {
  // No internet connection
  showError('No internet connection');
} on HttpException {
  // Server error
  showError('Server error occurred');
} on FormatException {
  // Invalid response format
  showError('Invalid response from server');
} catch (e) {
  // Unknown error
  showError('An error occurred: $e');
}
```

---

## Deployment Checklist

### Backend
- [ ] Update `.env` with production database credentials
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Configure HTTPS with SSL certificate
- [ ] Set up WebSocket server with process manager (systemd/supervisor)
- [ ] Configure firewall to allow ports 80, 443, 8080
- [ ] Enable error logging
- [ ] Set up database backups
- [ ] Test all API endpoints

### Flutter App
- [ ] Update `ApiConfig` with production URLs
- [ ] Enable HTTPS/WSS for production
- [ ] Test on physical devices (Android & iOS)
- [ ] Implement proper error handling
- [ ] Add loading states for all async operations
- [ ] Test offline functionality
- [ ] Optimize images and assets
- [ ] Run `flutter build apk --release` (Android)
- [ ] Run `flutter build ios --release` (iOS)

---

## Additional Resources

### Backend Documentation
- [API Endpoints](./API_ENDPOINTS.md)
- [WebSocket Deployment Guide](./WEBSOCKET_DEPLOYMENT_GUIDE.md)
- [Project Summary](./PROJECT_SUMMARY.md)

### Flutter Resources
- [Flutter Documentation](https://flutter.dev/docs)
- [Provider State Management](https://pub.dev/packages/provider)
- [HTTP Package](https://pub.dev/packages/http)
- [WebSocket Channel](https://pub.dev/packages/web_socket_channel)

### Example Projects
- Check the `examples/` directory for complete Flutter app samples
- Review `tests/` for API integration test examples

---

## Support

For issues or questions:
1. Check the [Common Issues](#common-issues--solutions) section
2. Review backend logs in `error.log`
3. Test API endpoints with Postman/curl
4. Verify WebSocket connection with browser console

---

**Last Updated:** February 2026
**Backend Version:** 1.0.0
**Compatible Flutter SDK:** 3.0+
