import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config/api_config.dart';
import '../models/user.dart';
import 'api_service.dart';

class AuthService {
  static final AuthService _instance = AuthService._internal();
  factory AuthService() => _instance;
  AuthService._internal();

  final _storage = const FlutterSecureStorage();
  final _apiService = ApiService();

  Future<void> saveToken(String token) async {
    await _storage.write(key: ApiConfig.tokenKey, value: token);
  }

  Future<String?> getToken() async {
    return await _storage.read(key: ApiConfig.tokenKey);
  }

  Future<void> deleteToken() async {
    await _storage.delete(key: ApiConfig.tokenKey);
  }

  Future<void> saveUser(User user) async {
    await _storage.write(
      key: ApiConfig.userKey,
      value: jsonEncode(user.toJson()),
    );
  }

  Future<User?> getUser() async {
    final userJson = await _storage.read(key: ApiConfig.userKey);
    if (userJson != null) {
      return User.fromJson(jsonDecode(userJson));
    }
    return null;
  }

  Future<void> deleteUser() async {
    await _storage.delete(key: ApiConfig.userKey);
  }

  Future<bool> isLoggedIn() async {
    final token = await getToken();
    return token != null;
  }

  Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await _apiService.post(
        ApiConfig.login,
        {
          'email': email,
          'password': password,
        },
        includeAuth: false,
      );

      if (response['token'] != null) {
        await saveToken(response['token']);
      }

      if (response['user'] != null) {
        final user = User.fromJson(response['user']);
        await saveUser(user);
      }

      return response;
    } catch (e) {
      rethrow;
    }
  }

  Future<Map<String, dynamic>> register({
    required String email,
    required String password,
    required String name,
    required String role,
    String? phone,
  }) async {
    try {
      final data = {
        'email': email,
        'password': password,
        'name': name,
        'role': role,
      };
      
      // Only add phone if it's not null
      if (phone != null && phone.isNotEmpty) {
        data['phone'] = phone;
      }

      final response = await _apiService.post(
        ApiConfig.register,
        data,
        includeAuth: false,
      );

      if (response['token'] != null) {
        await saveToken(response['token']);
      }

      if (response['user'] != null) {
        final user = User.fromJson(response['user']);
        await saveUser(user);
      }

      return response;
    } catch (e) {
      rethrow;
    }
  }

  Future<User?> getProfile() async {
    try {
      final response = await _apiService.get(ApiConfig.profile);
      if (response['user'] != null) {
        final user = User.fromJson(response['user']);
        await saveUser(user);
        return user;
      }
      return null;
    } catch (e) {
      rethrow;
    }
  }

  Future<void> logout() async {
    await deleteToken();
    await deleteUser();
  }
}
