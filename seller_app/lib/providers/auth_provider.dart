import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user_model.dart';
import '../services/api_service.dart';

class AuthProvider with ChangeNotifier {
  UserModel? _user;
  String? _token;
  bool _isLoading = false;
  String? _errorMessage;

  UserModel? get user => _user;
  String? get token => _token;
  bool get isLoading => _isLoading;
  bool get isAuthenticated => _token != null;
  String? get errorMessage => _errorMessage;

  AuthProvider() {
    _loadUser();
  }

  Future<void> _loadUser() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('token');
    final userStr = prefs.getString('user');
    if (userStr != null) {
      _user = UserModel.fromJson(jsonDecode(userStr));
    }
    notifyListeners();
  }

  Future<bool> login(String email, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await ApiService.post('/users/login', {
        'email': email,
        'password': password,
      });

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        _token = data['token'];
        if (data['user'] != null) {
          _user = UserModel.fromJson(data['user']);
        }

        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('token', _token!);
        if (_user != null) {
          await prefs.setString('user', jsonEncode(_user!.toJson()));
        }

        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        final data = jsonDecode(response.body);
        _errorMessage = data['message'] ?? 'Login failed';
      }
    } catch (e) {
      print('Login error: $e');
      _errorMessage = 'An error occurred during login';
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  Future<bool> register({
    required String name,
    required String email,
    required String password,
    String role = 'seller',
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await ApiService.post('/users/register', {
        'name': name,
        'email': email,
        'password': password,
        'role': role,
      });

      final data = jsonDecode(response.body);

      if (response.statusCode == 201 || response.statusCode == 200) {
        if (data['token'] != null) {
          _token = data['token'];
          if (data['user'] != null) {
            _user = UserModel.fromJson(data['user']);
          }

          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('token', _token!);
          if (_user != null) {
            await prefs.setString('user', jsonEncode(_user!.toJson()));
          }
        }
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _errorMessage = data['message'] ?? 'Registration failed';
      }
    } catch (e) {
      print('Registration error: $e');
      _errorMessage = 'An error occurred during registration';
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  Future<void> logout() async {
    _token = null;
    _user = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('token');
    await prefs.remove('user');
    notifyListeners();
  }
}
