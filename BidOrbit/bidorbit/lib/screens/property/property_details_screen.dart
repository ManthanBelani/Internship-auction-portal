import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:intl/intl.dart';
import '../../providers/items_provider.dart';
import '../../providers/watchlist_provider.dart';
import '../../widgets/countdown_timer.dart';
import '../../widgets/bid_dialog.dart';

class PropertyDetailsScreen extends StatefulWidget {
  final String itemId;

  const PropertyDetailsScreen({
    Key? key,
    required this.itemId,
  }) : super(key: key);

  @override
  State<PropertyDetailsScreen> createState() => _PropertyDetailsScreenState();
}

class _PropertyDetailsScreenState extends State<PropertyDetailsScreen> {
  int _currentImageIndex = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final itemsProvider = Provider.of<ItemsProvider>(context, listen: false);
      itemsProvider.fetchItemDetails(widget.itemId);
      itemsProvider.fetchItemBids(widget.itemId);
    });
  }

  void _showBidDialog() {
    final itemsProvider = Provider.of<ItemsProvider>(context, listen: false);
    final item = itemsProvider.selectedItem;
    
    if (item == null) return;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => BidDialog(
        item: item,
        onPlaceBid: (amount) async {
          final success = await itemsProvider.placeBid(item.id, amount);
          if (!success) {
            throw Exception(itemsProvider.error ?? 'Failed to place bid');
          }
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final currencyFormat = NumberFormat.currency(symbol: '\$', decimalDigits: 0);

    return Scaffold(
      body: Consumer<ItemsProvider>(
        builder: (context, itemsProvider, _) {
          if (itemsProvider.selectedItem == null && itemsProvider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          final item = itemsProvider.selectedItem;
          if (item == null) {
            return const Center(child: Text('Property not found'));
          }

          return CustomScrollView(
            slivers: [
              // App bar with image carousel
              SliverAppBar(
                expandedHeight: 300,
                pinned: true,
                flexibleSpace: FlexibleSpaceBar(
                  background: item.images.isNotEmpty
                      ? Stack(
                          children: [
                            PageView.builder(
                              itemCount: item.images.length,
                              onPageChanged: (index) {
                                setState(() => _currentImageIndex = index);
                              },
                              itemBuilder: (context, index) {
                                return CachedNetworkImage(
                                  imageUrl: item.images[index],
                                  fit: BoxFit.cover,
                                  width: double.infinity,
                                  placeholder: (context, url) => Container(
                                    color: Colors.grey[300],
                                    child: const Center(
                                      child: CircularProgressIndicator(),
                                    ),
                                  ),
                                  errorWidget: (context, url, error) =>
                                      Container(
                                    color: Colors.grey[300],
                                    child: const Icon(
                                      Icons.home,
                                      size: 64,
                                      color: Colors.grey,
                                    ),
                                  ),
                                );
                              },
                            ),
                            // Image indicator
                            if (item.images.length > 1)
                              Positioned(
                                bottom: 16,
                                left: 0,
                                right: 0,
                                child: Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: item.images.asMap().entries.map((entry) {
                                    return Container(
                                      width: 8,
                                      height: 8,
                                      margin: const EdgeInsets.symmetric(
                                        horizontal: 4,
                                      ),
                                      decoration: BoxDecoration(
                                        shape: BoxShape.circle,
                                        color: _currentImageIndex == entry.key
                                            ? Colors.white
                                            : Colors.white.withOpacity(0.4),
                                      ),
                                    );
                                  }).toList(),
                                ),
                              ),
                          ],
                        )
                      : Container(
                          color: Colors.grey[300],
                          child: const Icon(
                            Icons.home,
                            size: 64,
                            color: Colors.grey,
                          ),
                        ),
                ),
                actions: [
                  Consumer<WatchlistProvider>(
                    builder: (context, watchlistProvider, _) {
                      final isFavorite =
                          watchlistProvider.isInWatchlist(item.id);
                      return IconButton(
                        icon: Icon(
                          isFavorite ? Icons.favorite : Icons.favorite_border,
                          color: isFavorite ? Colors.red : Colors.white,
                        ),
                        onPressed: () {
                          watchlistProvider.toggleWatchlist(item.id);
                        },
                      );
                    },
                  ),
                  IconButton(
                    icon: const Icon(Icons.share),
                    onPressed: () {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Share feature coming soon')),
                      );
                    },
                  ),
                ],
              ),
              // Content
              SliverToBoxAdapter(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Title and location
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            item.title,
                            style: Theme.of(context)
                                .textTheme
                                .headlineSmall
                                ?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                          ),
                          const SizedBox(height: 8),
                          if (item.location != null)
                            Row(
                              children: [
                                Icon(
                                  Icons.location_on,
                                  size: 16,
                                  color: Colors.grey[600],
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  item.location!,
                                  style: TextStyle(
                                    color: Colors.grey[600],
                                    fontSize: 14,
                                  ),
                                ),
                              ],
                            ),
                        ],
                      ),
                    ),
                    const Divider(),
                    // Price and timer
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Current Bid',
                                style: TextStyle(
                                  color: Colors.grey[600],
                                  fontSize: 14,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                currencyFormat.format(item.currentPrice),
                                style: const TextStyle(
                                  fontSize: 28,
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFF2094F3),
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                '${item.bidCount} bid${item.bidCount != 1 ? 's' : ''}',
                                style: TextStyle(
                                  color: Colors.grey[600],
                                  fontSize: 12,
                                ),
                              ),
                            ],
                          ),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                'Time Left',
                                style: TextStyle(
                                  color: Colors.grey[600],
                                  fontSize: 14,
                                ),
                              ),
                              const SizedBox(height: 4),
                              CountdownTimer(
                                endTime: item.endTime,
                                textStyle: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                                showIcon: true,
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const Divider(),
                    // Description
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Description',
                            style: Theme.of(context)
                                .textTheme
                                .titleMedium
                                ?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            item.description,
                            style: TextStyle(
                              color: Colors.grey[700],
                              height: 1.5,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const Divider(),
                    // Seller info
                    if (item.sellerName != null)
                      Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Seller',
                              style: Theme.of(context)
                                  .textTheme
                                  .titleMedium
                                  ?.copyWith(
                                    fontWeight: FontWeight.bold,
                                  ),
                            ),
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                CircleAvatar(
                                  backgroundColor: const Color(0xFF2094F3),
                                  child: Text(
                                    item.sellerName![0].toUpperCase(),
                                    style: const TextStyle(color: Colors.white),
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Text(
                                  item.sellerName!,
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    const Divider(),
                    // Bid history
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Bid History',
                            style: Theme.of(context)
                                .textTheme
                                .titleMedium
                                ?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                          ),
                          const SizedBox(height: 16),
                          if (itemsProvider.itemBids.isEmpty)
                            Center(
                              child: Padding(
                                padding: const EdgeInsets.all(24),
                                child: Text(
                                  'No bids yet. Be the first to bid!',
                                  style: TextStyle(color: Colors.grey[600]),
                                ),
                              ),
                            )
                          else
                            ListView.separated(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              itemCount: itemsProvider.itemBids.length,
                              separatorBuilder: (context, index) =>
                                  const Divider(),
                              itemBuilder: (context, index) {
                                final bid = itemsProvider.itemBids[index];
                                return ListTile(
                                  leading: CircleAvatar(
                                    backgroundColor: const Color(0xFF2094F3),
                                    child: Text(
                                      bid.bidderName?[0].toUpperCase() ?? 'U',
                                      style:
                                          const TextStyle(color: Colors.white),
                                    ),
                                  ),
                                  title: Text(
                                    bid.bidderName ?? 'Anonymous',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                  subtitle: Text(
                                    DateFormat('MMM dd, yyyy - hh:mm a')
                                        .format(bid.timestamp),
                                  ),
                                  trailing: Text(
                                    currencyFormat.format(bid.amount),
                                    style: const TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.bold,
                                      color: Color(0xFF2094F3),
                                    ),
                                  ),
                                );
                              },
                            ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 80),
                  ],
                ),
              ),
            ],
          );
        },
      ),
      bottomNavigationBar: Consumer<ItemsProvider>(
        builder: (context, itemsProvider, _) {
          final item = itemsProvider.selectedItem;
          if (item == null || item.hasEnded) return const SizedBox.shrink();

          return Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.1),
                  blurRadius: 8,
                  offset: const Offset(0, -2),
                ),
              ],
            ),
            child: SafeArea(
              child: ElevatedButton(
                onPressed: _showBidDialog,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF2094F3),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Text(
                  'Place Bid',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
