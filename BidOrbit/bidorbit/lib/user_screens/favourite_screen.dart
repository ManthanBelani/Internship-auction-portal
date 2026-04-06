import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/watchlist_provider.dart';
import '../models/item.dart';
import '../theme/app_theme.dart';
import 'item_deatils_screen.dart';

class FavouriteScreen extends StatefulWidget {
  const FavouriteScreen({super.key});

  @override
  State<FavouriteScreen> createState() => _FavouriteScreenState();
}

class _FavouriteScreenState extends State<FavouriteScreen> {
  String _sortBy = 'recent';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<WatchlistProvider>().fetchWatchlist();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        centerTitle: true,
        title: const Text(
          'Watchlist',
          style: TextStyle(
            color: AppColors.textPrimary,
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.tune, color: AppColors.textPrimary),
            onPressed: () => _showFilterDialog(),
          ),
        ],
      ),
      body: Consumer<WatchlistProvider>(
        builder: (context, watchlistProvider, child) {
          if (watchlistProvider.isLoading && watchlistProvider.watchlist.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }

          if (watchlistProvider.error != null && watchlistProvider.watchlist.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.error_outline, size: 64, color: AppColors.textMuted),
                  const SizedBox(height: 16),
                  Text(
                    'Error loading watchlist',
                    style: TextStyle(fontSize: 18, color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    watchlistProvider.error!,
                    style: const TextStyle(fontSize: 14, color: AppColors.textMuted),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 24),
                  ElevatedButton(
                    onPressed: () => watchlistProvider.fetchWatchlist(),
                    child: const Text('Retry'),
                  ),
                ],
              ),
            );
          }

          if (watchlistProvider.watchlist.isEmpty) {
            return _buildEmptyState();
          }

          return RefreshIndicator(
            onRefresh: () => watchlistProvider.fetchWatchlist(),
            child: Column(
              children: [
                _buildHeader(watchlistProvider.watchlist.length),
                Expanded(
                  child: _buildWatchlist(watchlistProvider.watchlist),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildHeader(int itemCount) {
    return Container(
      color: AppColors.surface,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            '$itemCount ${itemCount == 1 ? 'Item' : 'Items'} Watching',
            style: const TextStyle(
              fontSize: 16,
              color: AppColors.textMuted,
            ),
          ),
          TextButton.icon(
            onPressed: () => _showSortDialog(),
            icon: Text(
              'SORT BY ${_sortBy.toUpperCase()}',
              style: const TextStyle(
                color: AppColors.primary,
                fontWeight: FontWeight.w600,
                fontSize: 13,
              ),
            ),
            label: const Icon(
              Icons.arrow_downward,
              color: AppColors.primary,
              size: 16,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildWatchlist(List<Item> items) {
    // Sort items based on selected sort option
    final sortedItems = List<Item>.from(items);
    switch (_sortBy) {
      case 'recent':
        sortedItems.sort((a, b) => b.id.compareTo(a.id));
        break;
      case 'ending':
        sortedItems.sort((a, b) => a.timeRemaining.compareTo(b.timeRemaining));
        break;
      case 'price':
        sortedItems.sort((a, b) => b.currentPrice.compareTo(a.currentPrice));
        break;
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: sortedItems.length,
      itemBuilder: (context, index) {
        return Padding(
          padding: const EdgeInsets.only(bottom: 16),
          child: _buildWatchlistCard(sortedItems[index]),
        );
      },
    );
  }

  Widget _buildWatchlistCard(Item item) {
    final formatCurrency = NumberFormat.simpleCurrency();
    final timeRemaining = item.timeRemaining;
    final isUrgent = timeRemaining.inHours < 24;
    
    String timeRemainingText;
    if (timeRemaining.inDays > 0) {
      timeRemainingText = '${timeRemaining.inDays}d ${timeRemaining.inHours % 24}h';
    } else if (timeRemaining.inHours > 0) {
      timeRemainingText = '${timeRemaining.inHours}h ${timeRemaining.inMinutes % 60}m';
    } else {
      timeRemainingText = '${timeRemaining.inMinutes}m';
    }

    Color categoryColor = _getCategoryColor(item.category);

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
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppRadius.md),
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
                          item.images[0],
                          height: 200,
                          width: double.infinity,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) {
                            return Container(
                              height: 200,
                              width: double.infinity,
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
                if (item.category != null)
                  Positioned(
                    top: 12,
                    left: 12,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: categoryColor,
                        borderRadius: BorderRadius.circular(AppRadius.sm),
                      ),
                      child: Text(
                        item.category!.toUpperCase(),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
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
                    child: Consumer<WatchlistProvider>(
                      builder: (context, watchlistProvider, child) {
                        return IconButton(
                          icon: const Icon(Icons.favorite, color: AppColors.error),
                          onPressed: () async {
                            final success = await watchlistProvider.removeFromWatchlist(item.id);
                            if (mounted && success) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text('Removed from watchlist'),
                                  duration: Duration(seconds: 2),
                                ),
                              );
                            }
                          },
                        );
                      },
                    ),
                  ),
                ),
                Positioned(
                  bottom: 12,
                  left: 12,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        item.reservePrice != null ? 'Reserve Price' : 'Current Bid',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          shadows: [Shadow(blurRadius: 4, color: Colors.black45)],
                        ),
                      ),
                      Text(
                        formatCurrency.format(item.currentPrice),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                          shadows: [Shadow(blurRadius: 4, color: Colors.black45)],
                        ),
                      ),
                    ],
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
                    item.title,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${item.bidCount} bids • ${item.status.toUpperCase()}',
                    style: const TextStyle(
                      fontSize: 13,
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          if (isUrgent)
                            const Icon(
                              Icons.access_time,
                              color: AppColors.warning,
                              size: 16,
                            ),
                          if (isUrgent) const SizedBox(width: 4),
                          const Text(
                            'Ends in',
                            style: TextStyle(
                              fontSize: 13,
                              color: AppColors.textSecondary,
                            ),
                          ),
                          const SizedBox(width: 4),
                          Text(
                            timeRemainingText,
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              color: isUrgent ? AppColors.warning : AppColors.textPrimary,
                            ),
                          ),
                        ],
                      ),
                      ElevatedButton(
                        onPressed: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => ItemDetailsScreen(itemId: item.id),
                            ),
                          );
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(
                            horizontal: 24,
                            vertical: 12,
                          ),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(AppRadius.sm),
                          ),
                        ),
                        child: const Text(
                          'Place Bid',
                          style: TextStyle(
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.favorite_border,
            size: 100,
            color: AppColors.border,
          ),
          const SizedBox(height: 24),
          const Text(
            'No items in watchlist',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Start adding items to your watchlist\nto keep track of auctions',
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 14,
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 32),
          ElevatedButton(
            onPressed: () {
              // Navigate to home tab
              DefaultTabController.of(context).animateTo(0);
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(
                horizontal: 32,
                vertical: 16,
              ),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(AppRadius.sm),
              ),
            ),
            child: const Text(
              'Browse Items',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Color _getCategoryColor(String? category) {
    if (category == null) return AppColors.textSecondary;
    
    switch (category.toLowerCase()) {
      case 'watches':
        return AppColors.textSecondary;
      case 'electronics':
        return AppColors.success;
      case 'art':
        return AppColors.shipped;
      case 'collectibles':
        return AppColors.warning;
      case 'vehicles':
        return AppColors.error;
      default:
        return AppColors.primary;
    }
  }

  void _showSortDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Sort By'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            RadioListTile<String>(
              title: const Text('Most Recent'),
              value: 'recent',
              groupValue: _sortBy,
              onChanged: (value) {
                setState(() => _sortBy = value!);
                Navigator.pop(context);
              },
            ),
            RadioListTile<String>(
              title: const Text('Ending Soon'),
              value: 'ending',
              groupValue: _sortBy,
              onChanged: (value) {
                setState(() => _sortBy = value!);
                Navigator.pop(context);
              },
            ),
            RadioListTile<String>(
              title: const Text('Highest Price'),
              value: 'price',
              groupValue: _sortBy,
              onChanged: (value) {
                setState(() => _sortBy = value!);
                Navigator.pop(context);
              },
            ),
          ],
        ),
      ),
    );
  }

  void _showFilterDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Filter'),
        content: const Text('Filter options coming soon!'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Close'),
          ),
        ],
      ),
    );
  }
}
