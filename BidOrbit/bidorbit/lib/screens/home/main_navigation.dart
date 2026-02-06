import 'package:flutter/material.dart';
import 'home_screen.dart';
import '../favorites/favorites_screen.dart';
import '../bids/my_bids_screen.dart';

class MainNavigation extends StatefulWidget {
  const MainNavigation({super.key});

  @override
  State<MainNavigation> createState() => _MainNavigationState();
}

class _MainNavigationState extends State<MainNavigation> {
  int _currentIndex = 0;

  final List<Widget> _screens = [
    const HomeScreen(),
    const FavoritesScreen(),
    const MyBidsScreen(),
    const Center(child: Text('Profile (Coming Soon)')),
  ];

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Scaffold(
      body: IndexedStack(
        index: _currentIndex,
        children: _screens,
      ),
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          border: Border(
            top: BorderSide(
              color: isDark ? Colors.grey[800]! : Colors.grey[200]!,
            ),
          ),
        ),
        child: NavigationBar(
          selectedIndex: _currentIndex,
          onDestinationSelected: (index) {
            setState(() {
              _currentIndex = index;
            });
          },
          backgroundColor: theme.scaffoldBackgroundColor,
          elevation: 0,
          indicatorColor: Colors.transparent,
          destinations: [
            _buildNavItem(Icons.home_rounded, 'Home', 0),
            _buildNavItem(Icons.favorite_outline, 'Favorites', 1),
            _buildNavItem(Icons.gavel_outlined, 'My Bids', 2),
            _buildNavItem(Icons.person_outline, 'Profile', 3),
          ],
        ),
      ),
    );
  }

  NavigationDestination _buildNavItem(IconData icon, String label, int index) {
    final isSelected = _currentIndex == index;
    final theme = Theme.of(context);
    
    return NavigationDestination(
      icon: Icon(
        icon,
        color: isSelected ? theme.primaryColor : Colors.grey,
      ),
      label: label,
      selectedIcon: Icon(
        icon, // Use filled icon if available
        color: theme.primaryColor,
      ),
    );
  }
}
