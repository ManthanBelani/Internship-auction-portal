import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config/api_config.dart';
import 'logger_service.dart';

/// Custom exception for API errors
class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final dynamic data;

  ApiException(this.message, {this.statusCode, this.data});

  @override
  String toString() => message;
}

/// Network exception for connectivity issues
class NetworkException extends ApiException {
  NetworkException(String message) : super(message, statusCode: 0);
}

/// Unauthorized exception for auth errors
class UnauthorizedException extends ApiException {
  UnauthorizedException(String message) : super(message, statusCode: 401);
}

/// Main API service for making HTTP requests to the backend
class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  /// Get headers for requests
  Future<Map<String, String>> _getHeaders({bool includeAuth = true}) async {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'ngrok-skip-browser-warning': 'true',
    };

    if (includeAuth) {
      const storage = FlutterSecureStorage();
      final token = await storage.read(key: ApiConfig.tokenKey);
      if (token != null) {
        headers['Authorization'] = 'Bearer $token';
      }
    }

    return headers;
  }

  /// GET request
  Future<dynamic> get(String endpoint, {bool includeAuth = true}) async {
    try {
      return await _makeRequestWithRetry(() async {
        final url = Uri.parse('${ApiConfig.baseUrl}$endpoint');
        final headers = await _getHeaders(includeAuth: includeAuth);

        LoggerService.logRequest(
          method: 'GET',
          endpoint: endpoint,
          headers: headers,
        );

        final stopwatch = Stopwatch()..start();
        final response = await http
            .get(url, headers: headers)
            .timeout(ApiConfig.connectionTimeout);
        stopwatch.stop();

        LoggerService.logResponse(
          method: 'GET',
          endpoint: endpoint,
          statusCode: response.statusCode,
          response: _truncateResponse(response.body),
          duration: stopwatch.elapsed,
        );

        return response;
      }, includeAuth: includeAuth);
    } on SocketException {
      throw NetworkException(
        'No internet connection. Please check your network.',
      );
    } on HttpException {
      throw NetworkException(
        'Could not connect to server. Please try again later.',
      );
    } on FormatException {
      throw ApiException('Invalid response format from server.');
    } on ApiException {
      rethrow;
    } catch (e) {
      LoggerService.error('GET request failed', tag: 'API', error: e);
      throw ApiException('Request failed: $e');
    }
  }

  /// POST request
  Future<dynamic> post(
    String endpoint,
    Map<String, dynamic> data, {
    bool includeAuth = true,
  }) async {
    try {
      return await _makeRequestWithRetry(() async {
        final url = Uri.parse('${ApiConfig.baseUrl}$endpoint');
        final headers = await _getHeaders(includeAuth: includeAuth);

        LoggerService.logRequest(
          method: 'POST',
          endpoint: endpoint,
          headers: headers,
          body: data,
        );

        final stopwatch = Stopwatch()..start();
        final response = await http
            .post(url, headers: headers, body: jsonEncode(data))
            .timeout(ApiConfig.connectionTimeout);
        stopwatch.stop();

        LoggerService.logResponse(
          method: 'POST',
          endpoint: endpoint,
          statusCode: response.statusCode,
          response: _truncateResponse(response.body),
          duration: stopwatch.elapsed,
        );

        return response;
      }, includeAuth: includeAuth);
    } on SocketException {
      throw NetworkException(
        'No internet connection. Please check your network.',
      );
    } on HttpException {
      throw NetworkException(
        'Could not connect to server. Please try again later.',
      );
    } on FormatException {
      throw ApiException('Invalid response format from server.');
    } on ApiException {
      rethrow;
    } catch (e) {
      LoggerService.error('POST request failed', tag: 'API', error: e);
      throw ApiException('Request failed: $e');
    }
  }

  /// PUT request
  Future<dynamic> put(
    String endpoint,
    Map<String, dynamic> data, {
    bool includeAuth = true,
  }) async {
    try {
      return await _makeRequestWithRetry(() async {
        final url = Uri.parse('${ApiConfig.baseUrl}$endpoint');
        final headers = await _getHeaders(includeAuth: includeAuth);

        LoggerService.logRequest(
          method: 'PUT',
          endpoint: endpoint,
          headers: headers,
          body: data,
        );

        final stopwatch = Stopwatch()..start();
        final response = await http
            .put(url, headers: headers, body: jsonEncode(data))
            .timeout(ApiConfig.connectionTimeout);
        stopwatch.stop();

        LoggerService.logResponse(
          method: 'PUT',
          endpoint: endpoint,
          statusCode: response.statusCode,
          response: _truncateResponse(response.body),
          duration: stopwatch.elapsed,
        );

        return response;
      }, includeAuth: includeAuth);
    } on SocketException {
      throw NetworkException(
        'No internet connection. Please check your network.',
      );
    } on HttpException {
      throw NetworkException(
        'Could not connect to server. Please try again later.',
      );
    } on FormatException {
      throw ApiException('Invalid response format from server.');
    } on ApiException {
      rethrow;
    } catch (e) {
      LoggerService.error('PUT request failed', tag: 'API', error: e);
      throw ApiException('Request failed: $e');
    }
  }

  /// DELETE request
  Future<dynamic> delete(String endpoint, {bool includeAuth = true}) async {
    try {
      return await _makeRequestWithRetry(() async {
        final url = Uri.parse('${ApiConfig.baseUrl}$endpoint');
        final headers = await _getHeaders(includeAuth: includeAuth);

        LoggerService.logRequest(
          method: 'DELETE',
          endpoint: endpoint,
          headers: headers,
        );

        final stopwatch = Stopwatch()..start();
        final response = await http
            .delete(url, headers: headers)
            .timeout(ApiConfig.connectionTimeout);
        stopwatch.stop();

        LoggerService.logResponse(
          method: 'DELETE',
          endpoint: endpoint,
          statusCode: response.statusCode,
          response: _truncateResponse(response.body),
          duration: stopwatch.elapsed,
        );

        return response;
      }, includeAuth: includeAuth);
    } on SocketException {
      throw NetworkException(
        'No internet connection. Please check your network.',
      );
    } on HttpException {
      throw NetworkException(
        'Could not connect to server. Please try again later.',
      );
    } on FormatException {
      throw ApiException('Invalid response format from server.');
    } on ApiException {
      rethrow;
    } catch (e) {
      LoggerService.error('DELETE request failed', tag: 'API', error: e);
      throw ApiException('Request failed: $e');
    }
  }

  /// Multipart POST request for file uploads
  Future<dynamic> postMultipart(
    String endpoint,
    Map<String, String> fields,
    List<File> files, {
    String fileField = 'images',
    bool includeAuth = true,
  }) async {
    try {
      final uri = Uri.parse('${ApiConfig.baseUrl}$endpoint');
      final request = http.MultipartRequest('POST', uri);

      request.headers['ngrok-skip-browser-warning'] = 'true';

      if (includeAuth) {
        const storage = FlutterSecureStorage();
        final token = await storage.read(key: ApiConfig.tokenKey);
        if (token != null) {
          request.headers['Authorization'] = 'Bearer $token';
        }
      }

      request.fields.addAll(fields);

      // Add multiple files with the same field name (PHP expects this for arrays)
      for (var i = 0; i < files.length; i++) {
        final file = files[i];
        final stream = http.ByteStream(file.openRead());
        final length = await file.length();
        final multipartFile = http.MultipartFile(
          '$fileField[]', // Add [] for PHP array handling
          stream,
          length,
          filename: file.path.split('/').last,
        );
        request.files.add(multipartFile);
      }

      LoggerService.logRequest(
        method: 'POST (multipart)',
        endpoint: endpoint,
        headers: {'Authorization': includeAuth ? 'Bearer ***' : 'none'},
        body: {...fields, 'files': '${files.length} files'},
      );

      final stopwatch = Stopwatch()..start();
      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);
      stopwatch.stop();

      LoggerService.logResponse(
        method: 'POST (multipart)',
        endpoint: endpoint,
        statusCode: response.statusCode,
        response: _truncateResponse(response.body),
        duration: stopwatch.elapsed,
      );

      return _handleResponse(response);
    } on SocketException {
      throw NetworkException(
        'No internet connection. Please check your network.',
      );
    } catch (e) {
      LoggerService.error(
        'Multipart POST request failed',
        tag: 'API',
        error: e,
      );
      throw ApiException('Upload failed: $e');
    }
  }

  /// Handle HTTP response
  dynamic _handleResponse(http.Response response) {
    if (response.statusCode >= 200 && response.statusCode < 300) {
      if (response.body.isEmpty) {
        return {'success': true};
      }
      try {
        return jsonDecode(response.body);
      } on FormatException {
        // Check if the response is HTML (common when server URL is wrong or ngrok expired)
        final body = response.body.trim().toLowerCase();
        if (body.startsWith('<!doctype') || body.startsWith('<html') || body.contains('<head>')) {
          throw ApiException(
            'Server returned HTML instead of JSON. Please check that the backend server is running and the API URL is correct.',
          );
        }
        throw ApiException('Invalid JSON response from server.');
      }
    } else if (response.statusCode == 401) {
      throw UnauthorizedException(
        'Your session has expired. Please login again.',
      );
    } else if (response.statusCode == 403) {
      throw ApiException(
        'Access forbidden. You do not have permission for this action.',
      );
    } else if (response.statusCode == 404) {
      throw ApiException('Resource not found.');
    } else if (response.statusCode == 422) {
      try {
        final error = jsonDecode(response.body);
        throw ApiException(
          error['message'] ?? 'Validation error',
          statusCode: 422,
          data: error,
        );
      } on FormatException {
        throw ApiException('Validation error');
      }
    } else if (response.statusCode >= 500) {
      throw ApiException('Server error. Please try again later.');
    } else {
      try {
        final error = jsonDecode(response.body);
        throw ApiException(
          error['message'] ?? 'Request failed',
          statusCode: response.statusCode,
          data: error,
        );
      } on FormatException {
        throw ApiException('Request failed with status ${response.statusCode}');
      }
    }
  }

  /// Make request with automatic token refresh on 401
  Future<dynamic> _makeRequestWithRetry(
    Future<http.Response> Function() request, {
    bool includeAuth = true,
  }) async {
    try {
      final response = await request();
      return _handleResponse(response);
    } catch (e) {
      // If unauthorized and we have auth enabled, try to refresh token
      if (e is UnauthorizedException && includeAuth) {
        final refreshed = await _tryRefreshToken();
        if (refreshed) {
          // Retry the request with new token
          final response = await request();
          return _handleResponse(response);
        }
      }
      rethrow;
    }
  }

  /// Try to refresh the access token
  Future<bool> _tryRefreshToken() async {
    try {
      const storage = FlutterSecureStorage();
      final refreshToken = await storage.read(key: ApiConfig.refreshTokenKey);

      if (refreshToken == null) {
        LoggerService.debug('No refresh token available', tag: 'Auth');
        return false;
      }

      LoggerService.debug('Attempting to refresh token', tag: 'Auth');

      final url = Uri.parse('${ApiConfig.baseUrl}${ApiConfig.refreshToken}');
      final response = await http
          .post(
            url,
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'ngrok-skip-browser-warning': 'true',
            },
            body: jsonEncode({'refreshToken': refreshToken}),
          )
          .timeout(ApiConfig.connectionTimeout);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['token'] != null) {
          await storage.write(key: ApiConfig.tokenKey, value: data['token']);
          LoggerService.info('Token refreshed successfully', tag: 'Auth');
        }
        if (data['refreshToken'] != null) {
          await storage.write(
            key: ApiConfig.refreshTokenKey,
            value: data['refreshToken'],
          );
        }
        return true;
      }

      LoggerService.warning(
        'Token refresh failed: ${response.statusCode}',
        tag: 'Auth',
      );
      return false;
    } catch (e) {
      LoggerService.error('Token refresh error', tag: 'Auth', error: e);
      return false;
    }
  }

  /// Truncate response for logging
  String _truncateResponse(String body) {
    if (body.length > 500) {
      return '${body.substring(0, 500)}... (${body.length} bytes total)';
    }
    return body;
  }
}
