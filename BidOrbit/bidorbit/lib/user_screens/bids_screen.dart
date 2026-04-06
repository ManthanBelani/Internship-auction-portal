import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/bid_provider.dart';
import '../theme/app_theme.dart';
import 'item_deatils_screen.dart';

class BidsScreen extends StatefulWidget {
  const BidsScreen({super.key});

  @override
  State<BidsScreen> createState() => _BidsScreenState();
}

class _BidsScreenState extends State<BidsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<BidProvider>().fetchMyBids();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        automaticallyImplyLeading: false,
        centerTitle: true,
        title: const Text(
          'Bidding Activity',
          style: TextStyle(
            color: AppColors.textPrimary,
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: AppColors.textPrimary),
            onPressed: () {
              context.read<BidProvider>().fetchMyBids();
            },
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          labelColor: AppColors.textPrimary,
          unselectedLabelColor: AppColors.textMuted,
          indicatorColor: AppColors.textPrimary,
          indicatorWeight: 3,
          labelStyle: const TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 14,
          ),
          tabs: const [
            Tab(text: 'WINNING'),
            Tab(text: 'OUTBID'),
            Tab(text: 'WON'),
            Tab(text: 'ENDED'),
          ],
        ),
      ),
      body: Consumer<BidProvider>(
        builder: (context, bidProvider, child) {
          if (bidProvider.isLoading && bidProvider.myBids.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }

          if (bidProvider.error != null && bidProvider.myBids.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.error_outline, size: 64, color: AppColors.textMuted),
                  const SizedBox(height: 16),
                  Text(
                    'Error loading bids',
                    style: TextStyle(fontSize: 18, color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    bidProvider.error!,
                    style: const TextStyle(fontSize: 14, color: AppColors.textMuted),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 24),
                  ElevatedButton(
                    onPressed: () => bidProvider.fetchMyBids(),
                    child: const Text('Retry'),
                  ),
                ],
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () => bidProvider.fetchMyBids(),
            child: TabBarView(
              controller: _tabController,
              children: [
                _buildBidList(bidProvider.winningBids, 'winning'),
                _buildBidList(bidProvider.outbidBids, 'outbid'),
                _buildBidList(bidProvider.wonBids, 'won'),
                _buildBidList(bidProvider.endedBids, 'ended'),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildBidList(List<Map<String, dynamic>> bids, String status) {
    if (bids.isEmpty) {
      return _buildEmptyState(status);
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: bids.length,
      itemBuilder: (context, index) {
        return Padding(
          padding: const EdgeInsets.only(bottom: 16),
          child: _buildBidCard(bids[index], status),
        );
      },
    );
  }

  Widget _buildBidCard(Map<String, dynamic> bid, String status) {
    final formatCurrency = NumberFormat.simpleCurrency();
    
    // Extract bid data
    final itemId = bid['itemId'] ?? bid['item_id'] ?? 0;
    final title = bid['itemTitle'] ?? bid['item_title'] ?? 'Unknown Item';
    final category = bid['category'] ?? 'General';
    final amount = (bid['amount'] ?? 0).toDouble();
    final imageUrl = bid['imageUrl'] ?? bid['image_url'];
    final timestamp = bid['timestamp'] ?? bid['created_at'] ?? '';
    
    // Status-specific styling
    Color statusColor;
    String statusText;
    String priceLabel;
    Widget actionButton;

    switch (status) {
      case 'winning':
        statusColor = AppColors.success;
        statusText = 'WINNING';
        priceLabel = 'YOUR BID';
        actionButton = _buildTrackBidButton(itemId);
        break;
      case 'outbid':
        statusColor = AppColors.error;
        statusText = 'OUTBID';
        priceLabel = 'YOUR BID';
        actionButton = _buildBidAgainButton(itemId);
        break;
      case 'won':
        statusColor = AppColors.warning;
        statusText = 'WON';
        priceLabel = 'WINNING BID';
        actionButton = _buildPaymentButton(itemId);
        break;
      case 'ended':
        statusColor = AppColors.textMuted;
        statusText = 'ENDED';
        priceLabel = 'FINAL BID';
        actionButton = _buildDetailsButton(itemId);
        break;
      default:
        statusColor = AppColors.textMuted;
        statusText = 'UNKNOWN';
        priceLabel = 'BID';
        actionButton = _buildDetailsButton(itemId);
    }

    return GestureDetector(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => ItemDetailsScreen(itemId: itemId),
          ),
        );
      },
      child: Container(
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppRadius.md),
          boxShadow: AppShadows.sm,
        ),
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Item Image
              ClipRRect(
                borderRadius: BorderRadius.circular(AppRadius.sm),
                child: imageUrl != null
                    ? Image.network(
                        imageUrl,
                        width: 80,
                        height: 80,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) {
                          return Container(
                            width: 80,
                            height: 80,
                            color: AppColors.border,
                            child: const Icon(Icons.image, color: AppColors.textMuted),
                          );
                        },
                      )
                    : Container(
                        width: 80,
                        height: 80,
                        color: AppColors.border,
                        child: const Icon(Icons.image, color: AppColors.textMuted),
                      ),
              ),
              const SizedBox(width: 16),
              // Bid Details
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Status Badge
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: statusColor.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(AppRadius.xs),
                        border: Border.all(color: statusColor),
                      ),
                      child: Text(
                        statusText,
                        style: TextStyle(
                          color: statusColor,
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    // Item Title
                    Text(
                      title,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.bold,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    // Category
                    Row(
                      children: [
                        Icon(
                          _getCategoryIcon(category),
                          size: 14,
                          color: AppColors.textSecondary,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          category,
                          style: const TextStyle(
                            fontSize: 12,
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    // Price
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              priceLabel,
                              style: const TextStyle(
                                fontSize: 10,
                                color: AppColors.textSecondary,
                              ),
                            ),
                            Text(
                              formatCurrency.format(amount),
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                        actionButton,
                      ],
                    ),
                    if (timestamp.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        _formatTimestamp(timestamp),
                        style: const TextStyle(
                          fontSize: 11,
                          color: AppColors.textMuted,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTrackBidButton(int itemId) {
    return ElevatedButton(
      onPressed: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => ItemDetailsScreen(itemId: itemId),
          ),
        );
      },
      style: ElevatedButton.styleFrom(
        backgroundColor: AppColors.textPrimary,
        foregroundColor: Colors.white,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
        ),
      ),
      child: const Text(
        'Track Bid',
        style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
      ),
    );
  }

  Widget _buildBidAgainButton(int itemId) {
    return ElevatedButton(
      onPressed: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => ItemDetailsScreen(itemId: itemId),
          ),
        );
      },
      style: ElevatedButton.styleFrom(
        backgroundColor: AppColors.warning,
        foregroundColor: Colors.white,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
        ),
      ),
      child: const Text(
        'Bid Again',
        style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
      ),
    );
  }

  Widget _buildPaymentButton(int itemId) {
    return ElevatedButton(
      onPressed: () {
        // TODO: Navigate to payment screen
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Payment feature coming soon!')),
        );
      },
      style: ElevatedButton.styleFrom(
        backgroundColor: AppColors.success,
        foregroundColor: Colors.white,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
        ),
      ),
      child: const Text(
        'Payment',
        style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
      ),
    );
  }

  Widget _buildDetailsButton(int itemId) {
    return OutlinedButton(
      onPressed: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => ItemDetailsScreen(itemId: itemId),
          ),
        );
      },
      style: OutlinedButton.styleFrom(
        foregroundColor: AppColors.textSecondary,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        side: const BorderSide(color: AppColors.border),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
        ),
      ),
      child: const Text(
        'Details',
        style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
      ),
    );
  }

  Widget _buildEmptyState(String status) {
    String message;
    IconData icon;

    switch (status) {
      case 'winning':
        message = 'No winning bids yet.\nStart bidding to see your active bids here!';
        icon = Icons.emoji_events_outlined;
        break;
      case 'outbid':
        message = 'No outbid items.\nYou\'re doing great!';
        icon = Icons.trending_up;
        break;
      case 'won':
        message = 'No won auctions yet.\nKeep bidding to win items!';
        icon = Icons.celebration_outlined;
        break;
      case 'ended':
        message = 'No ended auctions.\nYour bid history will appear here.';
        icon = Icons.history;
        break;
      default:
        message = 'No bids found.';
        icon = Icons.gavel;
    }

    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 80, color: AppColors.border),
          const SizedBox(height: 24),
          Text(
            message,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 16,
              color: AppColors.textSecondary,
              height: 1.5,
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
              'Browse Auctions',
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

  IconData _getCategoryIcon(String category) {
    switch (category.toLowerCase()) {
      case 'watches':
        return Icons.watch;
      case 'electronics':
        return Icons.devices;
      case 'art':
        return Icons.palette;
      case 'vehicles':
        return Icons.directions_car;
      case 'collectibles':
        return Icons.collections;
      default:
        return Icons.category;
    }
  }

  String _formatTimestamp(String timestamp) {
    try {
      final date = DateTime.parse(timestamp);
      final now = DateTime.now();
      final difference = now.difference(date);

      if (difference.inDays > 0) {
        return '${difference.inDays}d ago';
      } else if (difference.inHours > 0) {
        return '${difference.inHours}h ago';
      } else if (difference.inMinutes > 0) {
        return '${difference.inMinutes}m ago';
      } else {
        return 'Just now';
      }
    } catch (e) {
      return timestamp;
    }
  }
}
