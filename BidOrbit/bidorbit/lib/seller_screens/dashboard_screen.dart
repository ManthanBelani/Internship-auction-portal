import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../theme/app_theme.dart';
import 'active_auction.dart';
import 'inventory_screen.dart';
import 'winner_screen.dart';
import 'add_item_screen.dart';
import 'sales_screen.dart';
import 'analytics_screen.dart';
import 'payout_screen.dart';
import 'messages_screen.dart';
import 'seller_settings_screen.dart';
import '../providers/seller_provider.dart';
import '../providers/auth_provider.dart';

class SellerDashboardScreen extends StatefulWidget {
  const SellerDashboardScreen({super.key});

  @override
  State<SellerDashboardScreen> createState() => _SellerDashboardScreenState();
}

class _SellerDashboardScreenState extends State<SellerDashboardScreen> {
  int _selectedIndex = 0;

  final List<Widget> _screens = [
    const DashboardScreenContent(),
    const InventoryScreen(),
    const SellerBidsPlaceholder(),
    const ActiveAuctionScreen(),
    const WinnerScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(index: _selectedIndex, children: _screens),
      bottomNavigationBar: _buildBottomNavigation(),
    );
  }

  Widget _buildBottomNavigation() {
    return Container(
      decoration: BoxDecoration(
        boxShadow: AppShadows.md,
      ),
      child: BottomNavigationBar(
        currentIndex: _selectedIndex,
        onTap: (index) {
          setState(() {
            _selectedIndex = index;
          });
        },
        type: BottomNavigationBarType.fixed,
        backgroundColor: AppColors.surface,
        selectedItemColor: AppColors.primary,
        unselectedItemColor: AppColors.textMuted,
        selectedLabelStyle: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.bold,
        ),
        unselectedLabelStyle: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.bold,
        ),
        items: const [
          BottomNavigationBarItem(
            icon: Padding(
              padding: EdgeInsets.only(bottom: 4),
              child: Icon(Icons.grid_view_rounded),
            ),
            label: 'Dashboard',
          ),
          BottomNavigationBarItem(
            icon: Padding(
              padding: EdgeInsets.only(bottom: 4),
              child: Icon(Icons.inventory_2_outlined),
            ),
            label: 'Inventory',
          ),
          BottomNavigationBarItem(
            icon: Padding(
              padding: EdgeInsets.only(bottom: 4),
              child: Icon(Icons.gavel_outlined),
            ),
            label: 'Bids',
          ),
          BottomNavigationBarItem(
            icon: Padding(
              padding: EdgeInsets.only(bottom: 4),
              child: Icon(Icons.campaign_outlined),
            ),
            label: 'Auctions',
          ),
          BottomNavigationBarItem(
            icon: Padding(
              padding: EdgeInsets.only(bottom: 4),
              child: Icon(Icons.emoji_events_outlined),
            ),
            label: 'Winners',
          ),
        ],
      ),
    );
  }
}

class DashboardScreenContent extends StatefulWidget {
  const DashboardScreenContent({super.key});

  @override
  State<DashboardScreenContent> createState() => _DashboardScreenContentState();
}

class _DashboardScreenContentState extends State<DashboardScreenContent> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<SellerProvider>().fetchSellerStats();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildHeader(),
              const SizedBox(height: 32),
              _buildStatsGrid(),
              const SizedBox(height: 32),
              const Text(
                'QUICK ACTIONS',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textSecondary,
                  letterSpacing: 1.2,
                ),
              ),
              const SizedBox(height: 16),
              _buildQuickActions(context),
              const SizedBox(height: 40),
              _buildRecentBidsHeader(),
              const SizedBox(height: 20),
              _buildRecentBidsList(),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'WELCOME BACK',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: AppColors.textMuted,
                letterSpacing: 0.5,
              ),
            ),
            const SizedBox(height: 4),
            const Text(
              'Hello, Seller!',
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.bold,
                color: AppColors.textPrimary,
              ),
            ),
          ],
        ),
        Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppColors.surface,
                shape: BoxShape.circle,
                boxShadow: AppShadows.md,
              ),
              child: Stack(
                children: [
                  const Icon(
                    Icons.notifications_outlined,
                    color: AppColors.textPrimary,
                    size: 26,
                  ),
                  Positioned(
                    right: 2,
                    top: 2,
                    child: Container(
                      width: 8,
                      height: 8,
                      decoration: const BoxDecoration(
                        color: AppColors.error,
                        shape: BoxShape.circle,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 12),
            GestureDetector(
              onTap: () async {
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
                        child: const Text(
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
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  shape: BoxShape.circle,
                  boxShadow: AppShadows.md,
                ),
                child: const Icon(
                  Icons.logout,
                  color: AppColors.textPrimary,
                  size: 26,
                ),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildStatsGrid() {
    return Consumer<SellerProvider>(
      builder: (context, provider, child) {
        final stats = provider.stats;
        return LayoutBuilder(
          builder: (context, constraints) {
            double cardWidth = (constraints.maxWidth - 20) / 2;
            return Wrap(
              spacing: 20,
              runSpacing: 20,
              children: [
                _buildStatCard(
                  'Active Auctions',
                  '${stats['activeAuctions'] ?? 0}',
                  Icons.gavel_rounded,
                  AppColors.primary.withValues(alpha: 0.1),
                  AppColors.primary,
                  cardWidth,
                ),
                _buildStatCard(
                  'Total Sales',
                  '\$${stats['totalSales'] ?? 0}',
                  Icons.account_balance_wallet_outlined,
                  AppColors.success.withValues(alpha: 0.1),
                  AppColors.success,
                  cardWidth,
                ),
                _buildStatCard(
                  'Items Sold',
                  '${stats['itemsSold'] ?? 0}',
                  Icons.inventory_2_outlined,
                  AppColors.warning.withValues(alpha: 0.1),
                  AppColors.warning,
                  cardWidth,
                ),
                _buildStatCard(
                  'Pending Shipments',
                  '${stats['pendingShipments'] ?? 0}',
                  Icons.local_shipping_outlined,
                  AppColors.info.withValues(alpha: 0.1),
                  AppColors.info,
                  cardWidth,
                ),
              ],
            );
          },
        );
      },
    );
  }

  Widget _buildStatCard(
    String label,
    String value,
    IconData icon,
    Color bgColor,
    Color iconColor,
    double width,
  ) {
    return Container(
      width: width,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppRadius.xxl),
        boxShadow: AppShadows.sm,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: bgColor,
              borderRadius: BorderRadius.circular(AppRadius.md),
            ),
            child: Icon(icon, color: iconColor, size: 22),
          ),
          const SizedBox(height: 16),
          Text(
            label,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w500,
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.bold,
              color: AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildQuickActions(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      clipBehavior: Clip.none,
      child: Row(
        children: [
          GestureDetector(
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (context) => const AddItemScreen()),
              );
            },
            child: _buildActionItem(
              'Add Item',
              Icons.add_rounded,
              AppColors.primary,
              true,
            ),
          ),
          const SizedBox(width: 16),
          GestureDetector(
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (context) => const SalesScreen()),
              );
            },
            child: _buildActionItem(
              'Sales',
              Icons.shopping_bag_outlined,
              AppColors.surface,
              false,
            ),
          ),
          const SizedBox(width: 16),
          GestureDetector(
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (context) => const MessagesScreen()),
              );
            },
            child: _buildActionItem(
              'Messages',
              Icons.chat_bubble_outline_rounded,
              AppColors.surface,
              false,
            ),
          ),
          const SizedBox(width: 16),
          GestureDetector(
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (context) => const AnalyticsScreen()),
              );
            },
            child: _buildActionItem(
              'Analytics',
              Icons.bar_chart_rounded,
              AppColors.surface,
              false,
            ),
          ),
          const SizedBox(width: 16),
          GestureDetector(
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (context) => const PayoutScreen()),
              );
            },
            child: _buildActionItem(
              'Payouts',
              Icons.account_balance_wallet_outlined,
              AppColors.surface,
              false,
            ),
          ),
          const SizedBox(width: 16),
          GestureDetector(
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (context) => const SellerSettingsScreen()),
              );
            },
            child: _buildActionItem(
              'Settings',
              Icons.settings_outlined,
              AppColors.surface,
              false,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActionItem(
    String label,
    IconData icon,
    Color bgColor,
    bool isPrimary,
  ) {
    return Container(
      width: 110,
      padding: const EdgeInsets.symmetric(vertical: 24),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(AppRadius.xxl),
        border: !isPrimary ? Border.all(color: AppColors.border) : null,
        boxShadow: isPrimary ? AppShadows.primary : null,
      ),
      child: Column(
        children: [
          Icon(
            icon,
            color: isPrimary ? AppColors.surface : AppColors.textSecondary,
            size: 28,
          ),
          const SizedBox(height: 12),
          Text(
            label,
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: isPrimary ? AppColors.surface : AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildRecentBidsHeader() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        const Text(
          'Recent Bids',
          style: TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.bold,
            color: AppColors.textPrimary,
          ),
        ),
        TextButton(
          onPressed: () {},
          child: const Text(
            'View All',
            style: TextStyle(
              color: AppColors.primary,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildRecentBidsList() {
    return Column(
      children: [
        _buildBidItem(
          'Vintage Rolex Submariner',
          '4 bids • 2h left',
          '\$12,400',
          '+ \$200',
          Icons.watch_outlined,
          AppColors.primary,
        ),
        const SizedBox(height: 16),
        _buildBidItem(
          'Abstract Oil on Canvas',
          '12 bids • 45m left',
          '\$2,150',
          '+ \$50',
          Icons.palette_outlined,
          AppColors.warning,
        ),
        const SizedBox(height: 16),
        _buildBidItem(
          'Limited Edition Laptop',
          '8 bids • 5h left',
          '\$3,200',
          'NO CHANGE',
          Icons.laptop_mac_outlined,
          AppColors.textMuted,
        ),
      ],
    );
  }

  Widget _buildBidItem(
    String title,
    String subtitle,
    String price,
    String change,
    IconData icon,
    Color iconColor,
  ) {
    bool isPositive = change.startsWith('+');
    bool isNeutral = change == 'NO CHANGE';

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppRadius.xxl),
        boxShadow: AppShadows.sm,
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppColors.surfaceVariant,
              borderRadius: BorderRadius.circular(AppRadius.lg),
            ),
            child: Icon(icon, color: AppColors.textSecondary, size: 24),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  subtitle,
                  style: const TextStyle(
                    fontSize: 12,
                    color: AppColors.textMuted,
                  ),
                ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                price,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: AppColors.primary,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                change,
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.bold,
                  color: isPositive
                      ? AppColors.success
                      : (isNeutral ? AppColors.textMuted : AppColors.error),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class SellerBidsPlaceholder extends StatelessWidget {
  const SellerBidsPlaceholder({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: Text(
          'Seller Bids Screen Placeholder',
          style: TextStyle(fontSize: 18, color: AppColors.textMuted),
        ),
      ),
    );
  }
}
