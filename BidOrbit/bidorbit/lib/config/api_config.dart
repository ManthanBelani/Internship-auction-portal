import 'env_config.dart';

class ApiConfig {
  // Use environment-based configuration
  static String get baseUrl => EnvConfig.apiBaseUrl;
  static String get wsUrl => EnvConfig.wsBaseUrl;

  // API Endpoints
  static const String register = '/users/register';
  static const String login = '/users/login';
  static const String refreshToken = '/users/refresh';
  static const String profile = '/users/profile';

  // Items
  static const String items = '/items';

  // Bids
  static const String bids = '/bids';

  // Watchlist
  static const String watchlist = '/watchlist';

  // Buyer Endpoints
  static const String myBids = '/my/bids';
  static const String myNotifications = '/my/notifications';
  static const String myPayments = '/my/payments';

  // Payment Endpoints
  static const String payments = '/payments';
  static const String paymentMethods = '/payments/methods';
  static const String createPaymentIntent = '/payments/create-intent';
  static const String confirmPayment = '/payments/confirm';

  // Shipping Endpoints
  static const String shippingAddresses = '/shipping/addresses';
  static const String calculateShipping = '/shipping/calculate';

  // Order Endpoints
  static const String orders = '/orders';
  static const String wonItems = '/orders/won-items';

  // Seller Endpoints
  static const String sellerStats = '/seller/stats';
  static const String sellerListings = '/seller/listings';
  static const String sellerMessages = '/seller/messages';
  static const String sellerShipping = '/seller/shipping/track';
  static const String sellerPayouts = '/seller/payouts';
  static const String sellerSales = '/seller/sales';
  static const String sellerAnalytics = '/seller/analytics';
  static const String sellerBalance = '/seller/balance';

  // Review Endpoints
  static const String reviews = '/reviews';

  // Timeouts - use environment-based configuration
  static Duration get connectionTimeout => EnvConfig.connectionTimeout;
  static Duration get receiveTimeout => EnvConfig.receiveTimeout;

  // Storage Keys
  static const String tokenKey = 'auth_token';
  static const String refreshTokenKey = 'refresh_token';
  static const String userKey = 'user_data';

  // Helper method to get full URL for an endpoint
  static String getFullUrl(String endpoint) => '$baseUrl$endpoint';

  // Helper method to get item images URL
  static String getItemImageUrl(int itemId, int imageId) {
    return '$baseUrl/items/$itemId/images/$imageId';
  }
}
