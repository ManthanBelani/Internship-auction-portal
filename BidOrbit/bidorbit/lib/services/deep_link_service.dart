import 'dart:async';
import 'package:app_links/app_links.dart';
import 'package:flutter/material.dart';
// import '../user_screens/item_deatils_screen.dart';

class DeepLinkService {
  static final DeepLinkService _instance = DeepLinkService._internal();
  factory DeepLinkService() => _instance;
  DeepLinkService._internal();

  late AppLinks _appLinks;
  StreamSubscription<Uri>? _linkSubscription;
  final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

  void initialize() {
    _appLinks = AppLinks();

    // Handle links when app is already running
    _linkSubscription = _appLinks.uriLinkStream.listen((uri) {
      _handleDeepLink(uri);
    });

    // Handle links when app is started from a link
    _appLinks.getInitialAppLink().then((uri) {
      if (uri != null) {
        _handleDeepLink(uri);
      }
    });
  }

  void _handleDeepLink(Uri uri) {
    print('Deep Link received: $uri');

    // Example: bidorbit://auction/items/123
    if (uri.pathSegments.contains('items')) {
      final index = uri.pathSegments.indexOf('items');
      if (index + 1 < uri.pathSegments.length) {
        final itemId = uri.pathSegments[index + 1];
        _navigateToItem(itemId);
      }
    } else if (uri.queryParameters.containsKey('itemId')) {
      final itemId = uri.queryParameters['itemId'];
      if (itemId != null) {
        _navigateToItem(itemId);
      }
    }
  }

  void _navigateToItem(String itemId) {
    print('Navigating to item: $itemId');

    // We use the navigatorKey to get the context since we might be outside any widget
    final context = navigatorKey.currentContext;
    final intId = int.tryParse(itemId);
    if (context != null && intId != null) {
      // Navigator.push(
      //   context,
      //   MaterialPageRoute(
      //     builder: (context) => PropertyDetailsScreen(itemId: intId),
      //   ),
      // );
      print(
        'Navigation to item details via deep link not yet implemented for ID: $intId',
      );
    }
  }

  void dispose() {
    _linkSubscription?.cancel();
  }
}
