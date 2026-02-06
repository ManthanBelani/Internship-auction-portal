import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config/api_config.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  Future<Map<String, String>> _getHeaders({bool includeAuth = true}) async {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    if (includeAuth) {
      // Get token directly from storage instead of creating AuthService
      const storage = FlutterSecureStorage();
      final token = await storage.read(key: ApiConfig.tokenKey);
      if (token != null) {
        headers['Authorization'] = 'Bearer $token';
      }
    }

    return headers;
  }

  Future<dynamic> get(String endpoint, {bool includeAuth = true}) async {
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}$endpoint');
      final headers = await _getHeaders(includeAuth: includeAuth);

      final response = await http
          .get(url, headers: headers)
          .timeout(ApiConfig.connectionTimeout);

      return _handleResponse(response);
    } on SocketException {
      throw Exception('No internet connection');
    } on HttpException {
      throw Exception('Could not connect to server');
    } on FormatException {
      throw Exception('Invalid response format');
    } catch (e) {
      throw Exception('Request failed: $e');
    }
  }

  Future<dynamic> post(
    String endpoint,
    Map<String, dynamic> data, {
    bool includeAuth = true,
  }) async {
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}$endpoint');
      final headers = await _getHeaders(includeAuth: includeAuth);

      final response = await http
          .post(url, headers: headers, body: jsonEncode(data))
          .timeout(ApiConfig.connectionTimeout);

      return _handleResponse(response);
    } on SocketException {
      throw Exception('No internet connection');
    } on HttpException {
      throw Exception('Could not connect to server');
    } on FormatException {
      throw Exception('Invalid response format');
    } catch (e) {
      throw Exception('Request failed: $e');
    }
  }

  Future<dynamic> put(
    String endpoint,
    Map<String, dynamic> data, {
    bool includeAuth = true,
  }) async {
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}$endpoint');
      final headers = await _getHeaders(includeAuth: includeAuth);

      final response = await http
          .put(url, headers: headers, body: jsonEncode(data))
          .timeout(ApiConfig.connectionTimeout);

      return _handleResponse(response);
    } on SocketException {
      throw Exception('No internet connection');
    } on HttpException {
      throw Exception('Could not connect to server');
    } on FormatException {
      throw Exception('Invalid response format');
    } catch (e) {
      throw Exception('Request failed: $e');
    }
  }

  Future<dynamic> delete(String endpoint, {bool includeAuth = true}) async {
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}$endpoint');
      final headers = await _getHeaders(includeAuth: includeAuth);

      final response = await http
          .delete(url, headers: headers)
          .timeout(ApiConfig.connectionTimeout);

      return _handleResponse(response);
    } on SocketException {
      throw Exception('No internet connection');
    } on HttpException {
      throw Exception('Could not connect to server');
    } on FormatException {
      throw Exception('Invalid response format');
    } catch (e) {
      throw Exception('Request failed: $e');
    }
  }

  dynamic _handleResponse(http.Response response) {
    if (response.statusCode >= 200 && response.statusCode < 300) {
      if (response.body.isEmpty) {
        return {'success': true};
      }
      return jsonDecode(response.body);
    } else if (response.statusCode == 401) {
      throw Exception('Unauthorized. Please login again.');
    } else if (response.statusCode == 403) {
      throw Exception('Access forbidden');
    } else if (response.statusCode == 404) {
      throw Exception('Resource not found');
    } else if (response.statusCode == 422) {
      final error = jsonDecode(response.body);
      throw Exception(error['message'] ?? 'Validation error');
    } else if (response.statusCode >= 500) {
      throw Exception('Server error. Please try again later.');
    } else {
      try {
        final error = jsonDecode(response.body);
        throw Exception(error['message'] ?? 'Request failed');
      } catch (e) {
        throw Exception('Request failed with status ${response.statusCode}');
      }
    }
  }
}
