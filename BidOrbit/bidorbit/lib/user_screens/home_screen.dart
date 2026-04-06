import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/items_provider.dart';
import '../models/item.dart';
import '../theme/app_theme.dart';
import 'item_deatils_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _selectedCategory = 0;
  final TextEditingController _searchController = TextEditingController();

  final List<String> categories = ['All', 'Art', 'Electronics', 'Watches', 'Vehicles'];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ItemsProvider>().fetchItems(refresh: true);
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _onSearch(String query) {
    context.read<ItemsProvider>().setSearchQuery(query.isEmpty ? null : query);
  }

  @override
  Widget build(BuildContext context) {
    final itemsProvider = context.watch<ItemsProvider>();
    
    return Scaffold(
      backgroundColor: AppColors.surface,
      body: SafeArea(
        child: Column(
          children: [
            _buildHeader(),
            _buildSearchBar(),
            _buildCategoryTabs(),
            Expanded(
              child: itemsProvider.isLoading && itemsProvider.items.isEmpty
                  ? const Center(child: CircularProgressIndicator())
                  : itemsProvider.error != null && itemsProvider.items.isEmpty
                      ? Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.wifi_off, size: 48, color: AppColors.textMuted),
                              const SizedBox(height: 16),
                              Text(
                                'Unable to load auctions',
                                style: TextStyle(fontSize: 16, color: AppColors.textSecondary),
                              ),
                              const SizedBox(height: 24),
                              ElevatedButton(
                                onPressed: () => itemsProvider.fetchItems(refresh: true),
                                child: const Text('Retry'),
                              ),
                            ],
                          ),
                        )
                      : _buildAuctionList(itemsProvider),
            ),
          ],
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
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
          ),
          Row(
            children: [
              IconButton(
                icon: const Icon(Icons.notifications_outlined),
                onPressed: () {},
              ),
              IconButton(
                icon: const Icon(Icons.menu),
                onPressed: () {},
              ),
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
        onSubmitted: _onSearch,
        decoration: InputDecoration(
          hintText: 'Search art, watches, or cars',
          prefixIcon: const Icon(Icons.search, color: AppColors.textMuted),
          suffixIcon: _searchController.text.isNotEmpty
              ? IconButton(
                  icon: const Icon(Icons.clear),
                  onPressed: () {
                    _searchController.clear();
                    _onSearch('');
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
            onTap: () {
              setState(() {
                _selectedCategory = index;
              });
            },
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
                    fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildAuctionList(ItemsProvider itemsProvider) {
    final items = itemsProvider.items;
    
    if (items.isEmpty) {
      return const Center(
        child: Text('No items available'),
      );
    }

    return RefreshIndicator(
      onRefresh: () => itemsProvider.fetchItems(refresh: true),
      child: ListView.builder(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: items.length + 1,
        itemBuilder: (context, index) {
          if (index == items.length) {
            if (itemsProvider.hasMore) {
              if (!itemsProvider.isLoading) {
                itemsProvider.fetchItems();
              }
              return const Center(
                child: Padding(
                  padding: EdgeInsets.all(16.0),
                  child: CircularProgressIndicator(),
                ),
              );
            }
            return const SizedBox(height: 16);
          }

          final item = items[index];
          return Padding(
            padding: const EdgeInsets.only(bottom: 16),
            child: _buildAuctionCard(item),
          );
        },
      ),
    );
  }

  Widget _buildAuctionCard(Item item) {
    final timeRemaining = item.timeRemaining;
    final hours = timeRemaining.inHours;
    final days = hours ~/ 24;
    final remainingHours = hours % 24;
    final timeText = days > 0 ? '${days}d ${remainingHours}h' : '${hours}h';

    return GestureDetector(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => ItemDetailsScreen(itemId: item.id),
          ),
        );
      },
      child: Container(
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
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(AppRadius.md)),
                  child: item.images.isNotEmpty
                      ? Image.network(
                          item.images.first,
                          height: 200,
                          width: double.infinity,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) {
                            return Container(
                              height: 200,
                              color: AppColors.border,
                              child: const Icon(Icons.image, size: 80, color: AppColors.textMuted),
                            );
                          },
                        )
                      : Container(
                          height: 200,
                          width: double.infinity,
                          color: AppColors.border,
                          child: const Icon(Icons.image, size: 80, color: AppColors.textMuted),
                        ),
                ),
                Positioned(
                  top: 12,
                  right: 12,
                  child: Container(
                    decoration: const BoxDecoration(
                      color: AppColors.surface,
                      shape: BoxShape.circle,
                    ),
                    child: IconButton(
                      icon: Icon(
                        item.isFavorite ? Icons.favorite : Icons.favorite_border,
                        color: item.isFavorite ? AppColors.error : AppColors.textPrimary,
                      ),
                      onPressed: () {
                        // Toggle watchlist
                      },
                    ),
                  ),
                ),
                if (item.isActive)
                  Positioned(
                    top: 12,
                    left: 12,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppColors.error,
                        borderRadius: BorderRadius.circular(AppRadius.md),
                      ),
                      child: Text(
                        timeText,
                        style: const TextStyle(
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
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '\$${item.currentPrice.toStringAsFixed(2)}',
                    style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    item.title,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    '${item.bidCount} bids',
                    style: TextStyle(
                      fontSize: 12,
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
