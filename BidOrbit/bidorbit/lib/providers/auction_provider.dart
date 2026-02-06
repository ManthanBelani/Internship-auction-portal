import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../config/app_config.dart';

class AuctionProvider with ChangeNotifier {
  List<dynamic> _items = [];
  bool _isLoading = false;
  
  List<dynamic> get items => _items;
  bool get isLoading => _isLoading;

  Future<void> fetchItems() async {
    _isLoading = true;
    notifyListeners();
    
    try {
      final url = Uri.parse('${AppConfig.apiUrl}/items');
      final response = await http.get(url);
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        _items = data['items'];
      } else {
        throw Exception('Failed to load items');
      }
    } catch (e) {
      print('Error fetching items: $e');
      // In a real app, handle error state properly
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> getItemDetails(int itemId) async {
    try {
      final url = Uri.parse('${AppConfig.apiUrl}/items/$itemId');
      final response = await http.get(url);
      
      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        throw Exception('Failed to load item details');
      }
    } catch (e) {
      rethrow;
    }
  }
}
