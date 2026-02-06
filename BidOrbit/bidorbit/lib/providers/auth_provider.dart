import 'package:flutter/foundation.dart';
import '../models/user.dart';
import '../services/auth_service.dart';

class AuthProvider with ChangeNotifier {
  final AuthService _authService = AuthService();
  
  User? _user;
  bool _isLoading = false;
  String? _error;

  User? get user => _user;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get isAuthenticated => _user != null;

  Future<void> checkAuthStatus() async {
    _isLoading = true;
    notifyListeners();

    try {
      final isLoggedIn = await _authService.isLoggedIn();
      if (isLoggedIn) {
        _user = await _authService.getUser();
        // Only try to fetch from server if we have a stored user
        if (_user != null) {
          try {
            final serverUser = await _authService.getProfile();
            if (serverUser != null) {
              _user = serverUser;
            }
          } catch (e) {
            // If server fetch fails, continue with stored user
            print('Failed to fetch profile from server: $e');
          }
        }
      }
      _error = null;
    } catch (e) {
      print('Check auth status error: $e');
      _error = e.toString();
      _user = null;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> tryAutoLogin() async {
    try {
      final isLoggedIn = await _authService.isLoggedIn();
      if (isLoggedIn) {
        _user = await _authService.getUser();
        // Only try to fetch from server if we have a stored user
        // This prevents hanging on first launch
        if (_user != null) {
          try {
            final serverUser = await _authService.getProfile();
            if (serverUser != null) {
              _user = serverUser;
            }
          } catch (e) {
            // If server fetch fails, continue with stored user
            print('Failed to fetch profile from server: $e');
          }
        }
        notifyListeners();
        return _user != null;
      }
      return false;
    } catch (e) {
      print('Auto-login error: $e');
      _error = e.toString();
      _user = null;
      notifyListeners();
      return false;
    }
  }

  Future<bool> login(String email, String password) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _authService.login(email, password);
      if (response['user'] != null) {
        _user = User.fromJson(response['user']);
      }
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> register({
    required String email,
    required String password,
    required String name,
    required String role,
    String? phone,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _authService.register(
        email: email,
        password: password,
        name: name,
        role: role,
        phone: phone,
      );
      if (response['user'] != null) {
        _user = User.fromJson(response['user']);
      }
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> logout() async {
    await _authService.logout();
    _user = null;
    _error = null;
    notifyListeners();
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
