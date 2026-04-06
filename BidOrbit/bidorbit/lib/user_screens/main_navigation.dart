import 'package:bidorbit/user_screens/notification_screen.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/items_provider.dart';
import '../providers/watchlist_provider.dart';
import '../providers/notification_provider.dart';
import '../providers/auth_provider.dart';
import '../providers/bid_provider.dart';
import '../providers/theme_provider.dart';
import '../models/item.dart';
import '../widgets/countdown_timer.dart';
import '../theme/app_theme.dart';
import 'item_deatils_screen.dart';
import 'favourite_screen.dart';
import 'bids_screen.dart';
import 'edit_profile_screen.dart';
import 'orders_screen.dart';
import 'won_items_screen.dart';
import 'shipping_address_screen.dart';
import 'payment_method_screen.dart';
import 'settings_screen.dart';
import 'package:intl/intl.dart';

class MainNavigation extends StatefulWidget {
  const MainNavigation({super.key});

  @override
  State<MainNavigation> createState() => _MainNavigationState();
}

class _MainNavigationState extends State<MainNavigation> {
  int _selectedIndex = 0;

  final List<Widget> _screens = [
    const HomeScreenContent(),
    const FavouriteScreen(),
    const BidsScreen(),
    const ProfileScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _screens[_selectedIndex],
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _selectedIndex,
        onTap: (index) {
          setState(() {
            _selectedIndex = index;
          });
        },
        type: BottomNavigationBarType.fixed,
        selectedItemColor: AppColors.primary,
        unselectedItemColor: AppColors.textMuted,
        selectedLabelStyle: const TextStyle(fontWeight: FontWeight.w600),
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.home_outlined),
            activeIcon: Icon(Icons.home),
            label: 'Home',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.favorite_border),
            activeIcon: Icon(Icons.favorite),
            label: 'Favourite',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.list_alt_outlined),
            activeIcon: Icon(Icons.list_alt),
            label: 'Bids',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.person_outline),
            activeIcon: Icon(Icons.person),
            label: 'Profile',
          ),
        ],
      ),
    );
  }
}

// Profile Screen
class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Consumer<AuthProvider>(
      builder: (context, authProvider, child) {
        final user = authProvider.user;
        final userName = user?.name ?? 'User';
        final userEmail = user?.email ?? 'user@example.com';
        final userInitial = userName.isNotEmpty ? userName[0].toUpperCase() : 'U';

        return Scaffold(
          backgroundColor: AppColors.background,
          body: SafeArea(
            child: SingleChildScrollView(
              child: Column(
                children: [
                  // Header with gradient background
                  Container(
                    width: double.infinity,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: [AppColors.primary, AppColors.primaryDark],
                      ),
                    ),
                    child: Column(
                      children: [
                        const SizedBox(height: 40),
                        // Profile Avatar
                        Container(
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            border: Border.all(color: AppColors.surface, width: 4),
                            boxShadow: AppShadows.md,
                          ),
                          child: CircleAvatar(
                            radius: 50,
                            backgroundColor: AppColors.surface,
                            child: Text(
                              userInitial,
                              style: TextStyle(
                                fontSize: 40,
                                fontWeight: FontWeight.bold,
                                color: AppColors.primaryDark,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),
                        // User Name
                        Text(
                          userName,
                          style: const TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: AppColors.surface,
                          ),
                        ),
                        const SizedBox(height: 8),
                        // Email
                        Text(
                          userEmail,
                          style: TextStyle(
                            fontSize: 16,
                            color: AppColors.surface.withValues(alpha: 0.9),
                          ),
                        ),
                        const SizedBox(height: 24),
                        // Stats Row
                        Consumer3<BidProvider, WatchlistProvider, ItemsProvider>(
                          builder: (context, bidProvider, watchlistProvider, itemsProvider, child) {
                            return Container(
                              margin: const EdgeInsets.symmetric(horizontal: 24),
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: AppColors.surface.withValues(alpha: 0.2),
                                borderRadius: BorderRadius.circular(AppRadius.md),
                              ),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.spaceAround,
                                children: [
                                  _buildStatItem(
                                    bidProvider.myBids.length.toString(),
                                    'Active Bids',
                                    Icons.gavel,
                                  ),
                                  Container(
                                    width: 1,
                                    height: 40,
                                    color: AppColors.surface.withValues(alpha: 0.3),
                                  ),
                                  _buildStatItem(
                                    watchlistProvider.watchlist.length.toString(),
                                    'Watchlist',
                                    Icons.favorite,
                                  ),
                                  Container(
                                    width: 1,
                                    height: 40,
                                    color: AppColors.surface.withValues(alpha: 0.3),
                                  ),
                                  _buildStatItem(
                                    bidProvider.wonBids.length.toString(),
                                    'Won',
                                    Icons.emoji_events,
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                        const SizedBox(height: 24),
                      ],
                    ),
                  ),
              
              const SizedBox(height: 24),
              
              // Menu Items
              _buildMenuSection(
                context,
                'Account',
                [
                  _buildMenuItem(
                    icon: Icons.person_outline,
                    title: 'Edit Profile',
                    subtitle: 'Update your personal information',
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const EditProfileScreen(),
                        ),
                      );
                    },
                  ),
                  _buildMenuItem(
                    icon: Icons.shopping_bag_outlined,
                    title: 'My Orders',
                    subtitle: 'Track your orders',
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const OrdersScreen(),
                        ),
                      );
                    },
                  ),
                  _buildMenuItem(
                    icon: Icons.emoji_events_outlined,
                    title: 'Won Items',
                    subtitle: 'Items you have won',
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const WonItemsScreen(),
                        ),
                      );
                    },
                  ),
                  _buildMenuItem(
                    icon: Icons.location_on_outlined,
                    title: 'Shipping Addresses',
                    subtitle: 'Manage delivery addresses',
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const ShippingAddressScreen(isSelectionMode: false),
                        ),
                      );
                    },
                  ),
                  _buildMenuItem(
                    icon: Icons.payment,
                    title: 'Payment Methods',
                    subtitle: 'Manage your payment options',
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const PaymentMethodScreen(),
                        ),
                      );
                    },
                  ),
                ],
              ),
              
              const SizedBox(height: 16),
              
              _buildMenuSection(
                context,
                'Preferences',
                [
                  _buildMenuItem(
                    icon: Icons.settings_outlined,
                    title: 'Settings',
                    subtitle: 'App settings and preferences',
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const SettingsScreen(),
                        ),
                      );
                    },
                  ),
                  _buildMenuItem(
                    icon: Icons.dark_mode_outlined,
                    title: 'Dark Mode',
                    subtitle: 'Toggle dark mode',
                    trailing: Consumer<ThemeProvider>(
                      builder: (context, themeProvider, child) {
                        return Switch(
                          value: themeProvider.themeMode == ThemeMode.dark,
                          onChanged: (value) {
                            themeProvider.toggleTheme();
                          },
                          activeColor: AppColors.primaryDark,
                        );
                      },
                    ),
                    onTap: () {},
                  ),
                ],
              ),
              
              const SizedBox(height: 16),
              
              _buildMenuSection(
                context,
                'Support',
                [
                  _buildMenuItem(
                    icon: Icons.help_outline,
                    title: 'Help Center',
                    subtitle: 'Get help and support',
                    onTap: () {},
                  ),
                  _buildMenuItem(
                    icon: Icons.info_outline,
                    title: 'About',
                    subtitle: 'App version 1.0.0',
                    onTap: () {},
                  ),
                ],
              ),
              
              const SizedBox(height: 24),
              
              // Logout Button
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                child: ElevatedButton(
                  onPressed: () async {
                    // Show confirmation dialog
                    final shouldLogout = await showDialog<bool>(
                      context: context,
                      builder: (context) => AlertDialog(
                        title: const Text('Logout'),
                        content: const Text('Are you sure you want to logout?'),
                        actions: [
                          TextButton(
                            onPressed: () => Navigator.pop(context, false),
                            child: const Text('Cancel'),
                          ),
                          TextButton(
                            onPressed: () => Navigator.pop(context, true),
                            child: Text(
                              'Logout',
                              style: TextStyle(color: AppColors.error),
                            ),
                          ),
                        ],
                      ),
                    );

                    if (shouldLogout == true && context.mounted) {
                      await context.read<AuthProvider>().logout();
                      if (context.mounted) {
                        Navigator.of(context).pushNamedAndRemoveUntil(
                          '/login',
                          (route) => false,
                        );
                      }
                    }
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.errorLight,
                    foregroundColor: AppColors.error,
                    minimumSize: const Size(double.infinity, 56),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(AppRadius.md),
                    ),
                    elevation: 0,
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.logout, color: AppColors.error),
                      const SizedBox(width: 8),
                      Text(
                        'Logout',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                          color: AppColors.error,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              
              const SizedBox(height: 32),
            ],
          ),
        ),
      ),
    );
      },
    );
  }

  Widget _buildStatItem(String value, String label, IconData icon) {
    return Column(
      children: [
        Icon(icon, color: AppColors.surface, size: 24),
        const SizedBox(height: 8),
        Text(
          value,
          style: const TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.bold,
            color: AppColors.surface,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            color: AppColors.surface.withValues(alpha: 0.9),
          ),
        ),
      ],
    );
  }

  Widget _buildMenuSection(BuildContext context, String title, List<Widget> items) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
          child: Text(
            title,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: AppColors.textSecondary,
              letterSpacing: 0.5,
            ),
          ),
        ),
        Container(
          margin: const EdgeInsets.symmetric(horizontal: 16),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(AppRadius.md),
            boxShadow: AppShadows.sm,
          ),
          child: Column(children: items),
        ),
      ],
    );
  }

  Widget _buildMenuItem({
    required IconData icon,
    required String title,
    required String subtitle,
    Widget? trailing,
    required VoidCallback onTap,
  }) {
    return ListTile(
      onTap: onTap,
      leading: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: AppColors.primary50,
          borderRadius: BorderRadius.circular(AppRadius.sm),
        ),
        child: Icon(icon, color: AppColors.primaryDark, size: 24),
      ),
      title: Text(
        title,
        style: const TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w500,
        ),
      ),
      subtitle: Text(
        subtitle,
        style: TextStyle(
          fontSize: 13,
          color: AppColors.textSecondary,
        ),
      ),
      trailing: trailing ?? Icon(Icons.arrow_forward_ios, size: 16, color: AppColors.textMuted),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
    );
  }
}

// Home Screen Content (without bottom nav)
class HomeScreenContent extends StatefulWidget {
  const HomeScreenContent({super.key});

  @override
  State<HomeScreenContent> createState() => _HomeScreenContentState();
}

class _HomeScreenContentState extends State<HomeScreenContent> {
  int _selectedCategory = 0;
  final List<String> categories = ['All', 'Art', 'Electronics', 'Watches', 'Jewelry', 'Collectibles'];
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    // Fetch items when screen initializes
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ItemsProvider>().fetchItems(refresh: true);
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _onCategoryChanged(int index) {
    setState(() {
      _selectedCategory = index;
    });
    
    // Apply category filter
    final itemsProvider = context.read<ItemsProvider>();
    if (index == 0) {
      // "All" category - clear filters
      itemsProvider.clearFilters();
    } else {
      // Apply specific category filter
      itemsProvider.setSearchQuery(null);
      // Note: We'll filter locally since backend might not have category filter
      itemsProvider.fetchItems(refresh: true);
    }
  }

  void _onSearchChanged(String query) {
    setState(() {
      _searchQuery = query.toLowerCase();
    });
  }

  void _onSearchSubmitted(String query) {
    if (query.trim().isNotEmpty) {
      context.read<ItemsProvider>().setSearchQuery(query.trim());
    } else {
      context.read<ItemsProvider>().setSearchQuery(null);
    }
  }

  List<Item> _getFilteredItems(List<Item> items) {
    var filtered = items;
    
    // Apply category filter
    if (_selectedCategory != 0) {
      final selectedCategoryName = categories[_selectedCategory].toLowerCase();
      filtered = filtered.where((item) {
        final itemCategory = (item.category ?? '').toLowerCase();
        return itemCategory == selectedCategoryName;
      }).toList();
    }
    
    // Apply local search filter
    if (_searchQuery.isNotEmpty) {
      filtered = filtered.where((item) {
        final title = item.title.toLowerCase();
        final description = item.description.toLowerCase();
        final category = (item.category ?? '').toLowerCase();
        return title.contains(_searchQuery) ||
               description.contains(_searchQuery) ||
               category.contains(_searchQuery);
      }).toList();
    }
    
    return filtered;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.surface,
      body: SafeArea(
        child: Consumer<ItemsProvider>(
          builder: (context, itemsProvider, child) {
            if (itemsProvider.isLoading && itemsProvider.items.isEmpty) {
              return const Center(child: CircularProgressIndicator());
            }

            if (itemsProvider.error != null && itemsProvider.items.isEmpty) {
              return Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.wifi_off, size: 48, color: AppColors.textMuted),
                    const SizedBox(height: 16),
                    Text(
                      'Unable to load auctions',
                      style: TextStyle(fontSize: 16, color: AppColors.textSecondary),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Please check your connection and try again',
                      style: TextStyle(fontSize: 14, color: AppColors.textMuted),
                    ),
                    const SizedBox(height: 24),
                    ElevatedButton(
                      onPressed: () => itemsProvider.fetchItems(refresh: true),
                      child: const Text('Retry'),
                    ),
                  ],
                ),
              );
            }

            return RefreshIndicator(
              onRefresh: () => itemsProvider.fetchItems(refresh: true),
              child: Column(
                children: [
                  _buildHeader(),
                  _buildSearchBar(),
                  _buildCategoryTabs(),
                  Expanded(child: _buildAuctionList(_getFilteredItems(itemsProvider.items))),
                ],
              ),
            );
          },
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Padding(
      padding: const EdgeInsets.all(16.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          const Text(
            'Bid Orbit',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          Row(
            children: [
              Consumer<NotificationProvider>(
                builder: (context, notificationProvider, child) {
                  return Stack(
                    children: [
                      IconButton(
                        icon: const Icon(Icons.notifications_outlined),
                        onPressed: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => const NotificationScreen(),
                            ),
                          );
                        },
                      ),
                      if (notificationProvider.unreadCount > 0)
                        Positioned(
                          right: 8,
                          top: 8,
                          child: Container(
                            padding: const EdgeInsets.all(4),
                            decoration: const BoxDecoration(
                              color: AppColors.error,
                              shape: BoxShape.circle,
                            ),
                            constraints: const BoxConstraints(
                              minWidth: 16,
                              minHeight: 16,
                            ),
                            child: Text(
                              notificationProvider.unreadCount > 9
                                  ? '9+'
                                  : notificationProvider.unreadCount.toString(),
                              style: const TextStyle(
                                color: AppColors.surface,
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                              ),
                              textAlign: TextAlign.center,
                            ),
                          ),
                        ),
                    ],
                  );
                },
              ),
              IconButton(icon: const Icon(Icons.menu), onPressed: () {}),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSearchBar() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16.0),
      child: TextField(
        controller: _searchController,
        onChanged: _onSearchChanged,
        onSubmitted: _onSearchSubmitted,
        decoration: InputDecoration(
          hintText: 'Search art, watches, or cars',
          prefixIcon: const Icon(Icons.search, color: AppColors.textMuted),
          suffixIcon: _searchQuery.isNotEmpty
              ? IconButton(
                  icon: const Icon(Icons.clear, color: AppColors.textMuted),
                  onPressed: () {
                    _searchController.clear();
                    _onSearchChanged('');
                    _onSearchSubmitted('');
                  },
                )
              : null,
          filled: true,
          fillColor: AppColors.background,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppRadius.md),
            borderSide: BorderSide.none,
          ),
        ),
      ),
    );
  }

  Widget _buildCategoryTabs() {
    return Container(
      height: 50,
      margin: const EdgeInsets.symmetric(vertical: 16),
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: categories.length,
        itemBuilder: (context, index) {
          final isSelected = _selectedCategory == index;
          return GestureDetector(
            onTap: () => _onCategoryChanged(index),
            child: Container(
              margin: const EdgeInsets.only(right: 12),
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
              decoration: BoxDecoration(
                color: isSelected ? AppColors.textPrimary : Colors.transparent,
                borderRadius: BorderRadius.circular(AppRadius.pill),
                border: Border.all(
                  color: isSelected ? AppColors.textPrimary : AppColors.border,
                ),
              ),
              child: Center(
                child: Text(
                  categories[index],
                  style: TextStyle(
                    color: isSelected ? AppColors.surface : AppColors.textPrimary,
                    fontWeight: isSelected
                        ? FontWeight.w600
                        : FontWeight.normal,
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildAuctionList(List<Item> items) {
    if (items.isEmpty) {
      return const Center(child: Text('No auctions found'));
    }

    final formatCurrency = NumberFormat.simpleCurrency();

    return ListView(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      children: [
        _buildSectionHeader('Ending Soon'),
        const SizedBox(height: 12),
        _buildHorizontalAuctionList(
          items.where((i) => i.timeRemaining.inHours < 48).toList(),
        ),
        const SizedBox(height: 24),
        _buildSectionHeader('New Listings'),
        const SizedBox(height: 12),
        ...items.map(
          (item) => Padding(
            padding: const EdgeInsets.only(bottom: 16.0),
            child: GestureDetector(
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => ItemDetailsScreen(itemId: item.id),
                  ),
                );
              },
              child: _buildAuctionCard(
                formatCurrency.format(item.currentPrice),
                item.title,
                item.category?.toUpperCase(),
                item.status.toUpperCase(),
                imageUrl: item.images.isNotEmpty ? item.images[0] : null,
                item: item,
                endTime: item.endTime,
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildSectionHeader(String title) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          title,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
        ),
        TextButton(
          onPressed: () {},
          child: const Text('View all', style: TextStyle(color: AppColors.primary)),
        ),
      ],
    );
  }

  Widget _buildHorizontalAuctionList(List<Item> items) {
    if (items.isEmpty) return const SizedBox();

    final formatCurrency = NumberFormat.simpleCurrency();

    return SizedBox(
      height: 200,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: items.length,
        separatorBuilder: (_, _) => const SizedBox(width: 12),
        itemBuilder: (context, index) {
          final item = items[index];

          return GestureDetector(
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => ItemDetailsScreen(itemId: item.id),
                ),
              );
            },
            child: _buildSmallAuctionCard(
              formatCurrency.format(item.currentPrice),
              item.title,
              item.endTime,
              imageUrl: item.images.isNotEmpty ? item.images[0] : null,
            ),
          );
        },
      ),
    );
  }

  Widget _buildSmallAuctionCard(
    String price,
    String title,
    DateTime endTime, {
    String? imageUrl,
  }) {
    return Container(
      width: 180,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(AppRadius.md),
        color: AppColors.surface,
        boxShadow: AppShadows.sm,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Stack(
            children: [
              ClipRRect(
                borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(AppRadius.md),
                ),
                child: Container(
                  height: 120,
                  width: double.infinity,
                  color: AppColors.border,
                  child: imageUrl != null && imageUrl.isNotEmpty
                      ? Image.network(
                          imageUrl,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) =>
                              const Icon(
                                Icons.image,
                                size: 50,
                                color: AppColors.textMuted,
                              ),
                        )
                      : const Icon(Icons.image, size: 50, color: AppColors.textMuted),
                ),
              ),
              Positioned(
                top: 8,
                right: 8,
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: AppColors.error,
                    borderRadius: BorderRadius.circular(AppRadius.md),
                  ),
                  child: CountdownTimer(
                    endTime: endTime,
                    textStyle: const TextStyle(
                      color: AppColors.surface,
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),
            ],
          ),
          Padding(
            padding: const EdgeInsets.all(12.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  price,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  title,
                  style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAuctionCard(
    String price,
    String title,
    String? badge1,
    String? badge2, {
    String? imageUrl,
    Item? item,
    DateTime? endTime,
  }) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(AppRadius.md),
        color: AppColors.surface,
        boxShadow: AppShadows.sm,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Stack(
            children: [
              ClipRRect(
                borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(AppRadius.md),
                ),
                child: Container(
                  height: 200,
                  width: double.infinity,
                  color: AppColors.border,
                  child: imageUrl != null && imageUrl.isNotEmpty
                      ? Image.network(
                          imageUrl,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) =>
                              const Icon(
                                Icons.image,
                                size: 80,
                                color: AppColors.textMuted,
                              ),
                        )
                      : const Icon(Icons.image, size: 80, color: AppColors.textMuted),
                ),
              ),
              if (item != null)
                Positioned(
                  top: 12,
                  right: 12,
                  child: Container(
                    decoration: const BoxDecoration(
                      color: AppColors.surface,
                      shape: BoxShape.circle,
                    ),
                    child: Consumer<WatchlistProvider>(
                      builder: (context, watchlistProvider, child) {
                        final isInWatchlist = watchlistProvider.isInWatchlist(item.id);
                        return IconButton(
                          icon: Icon(
                            isInWatchlist ? Icons.favorite : Icons.favorite_border,
                            color: isInWatchlist ? AppColors.error : AppColors.textPrimary,
                          ),
                          onPressed: () async {
                            await watchlistProvider.toggleWatchlist(item.id);
                          },
                        );
                      },
                    ),
                  ),
                ),
            ],
          ),
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  price,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        if (badge1 != null)
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 4,
                            ),
                            decoration: BoxDecoration(
                              color: AppColors.textPrimary,
                              borderRadius: BorderRadius.circular(AppRadius.xs),
                            ),
                            child: Text(
                              badge1,
                              style: const TextStyle(
                                color: AppColors.surface,
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        if (badge1 != null && badge2 != null)
                          const SizedBox(width: 8),
                        if (badge2 != null)
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 4,
                            ),
                            decoration: BoxDecoration(
                              color: AppColors.primaryDark,
                              borderRadius: BorderRadius.circular(AppRadius.xs),
                            ),
                            child: Text(
                              badge2,
                              style: const TextStyle(
                                color: AppColors.surface,
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                      ],
                    ),
                    if (endTime != null)
                      Row(
                        children: [
                          Icon(Icons.access_time, size: 14, color: AppColors.textSecondary),
                          const SizedBox(width: 4),
                          CountdownTimer(
                            endTime: endTime,
                            textStyle: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              color: AppColors.textSecondary,
                            ),
                          ),
                        ],
                      ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
