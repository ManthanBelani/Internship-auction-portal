import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../theme/app_theme.dart';
import '../providers/seller_provider.dart';
import '../models/item.dart';

class InventoryScreen extends StatefulWidget {
  const InventoryScreen({super.key});

  @override
  State<InventoryScreen> createState() => _InventoryScreenState();
}

class _InventoryScreenState extends State<InventoryScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _tabController.addListener(_handleTabSelection);

    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<SellerProvider>().fetchInventory();
    });
  }

  void _handleTabSelection() {
    if (!_tabController.indexIsChanging) {
      setState(() {});
    }
  }

  @override
  void dispose() {
    _tabController.removeListener(_handleTabSelection);
    _tabController.dispose();
    super.dispose();
  }

  List<Item> _filterItems(List<Item> allItems, int index) {
    switch (index) {
      case 0:
        return allItems;
      case 1:
        return allItems.where((i) => i.status.toUpperCase() == 'LIVE').toList();
      case 2:
        return allItems.where((i) => i.status.toUpperCase() == 'SOLD').toList();
      case 3:
        return allItems
            .where((i) => i.status.toUpperCase() == 'SCHEDULED')
            .toList();
      default:
        return allItems;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<SellerProvider>(
      builder: (context, provider, child) {
        final allItems = provider.inventory;
        final liveCount = allItems
            .where((i) => i.status.toUpperCase() == 'LIVE')
            .length;
        final soldCount = allItems
            .where((i) => i.status.toUpperCase() == 'SOLD')
            .length;
        final scheduledCount = allItems
            .where((i) => i.status.toUpperCase() == 'SCHEDULED')
            .length;

        final displayedItems = _filterItems(allItems, _tabController.index);

        return Scaffold(
          backgroundColor: AppColors.surface,
          appBar: AppBar(
            backgroundColor: AppColors.surface,
            elevation: 0,
            leading: IconButton(
              icon: const Icon(Icons.arrow_back, color: AppColors.textPrimary),
              onPressed: () => Navigator.pop(context),
            ),
            title: const Text(
              'My Inventory',
              style: TextStyle(
                color: AppColors.textPrimary,
                fontSize: 20,
                fontWeight: FontWeight.bold,
              ),
            ),
            centerTitle: true,
          ),
          body: Column(
            children: [
              Container(
                decoration: const BoxDecoration(
                  border: Border(bottom: BorderSide(color: AppColors.surfaceVariant)),
                ),
                child: TabBar(
                  controller: _tabController,
                  isScrollable: true,
                  labelColor: AppColors.primaryLight,
                  unselectedLabelColor: AppColors.textSecondary,
                  indicatorColor: AppColors.primaryLight,
                  indicatorWeight: 3,
                  labelStyle: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                  ),
                  tabs: [
                    Tab(text: 'All Items (${allItems.length})'),
                    Tab(text: 'Live ($liveCount)'),
                    Tab(text: 'Sold ($soldCount)'),
                    Tab(text: 'Scheduled ($scheduledCount)'),
                  ],
                ),
              ),
              _buildSearchBar(),
              Expanded(
                child: provider.isLoading
                    ? const Center(child: CircularProgressIndicator())
                    : RefreshIndicator(
                        onRefresh: () => provider.fetchInventory(),
                        child: displayedItems.isEmpty
                            ? const Center(child: Text('No items found'))
                            : ListView.separated(
                                padding: const EdgeInsets.all(20),
                                itemCount: displayedItems.length,
                                separatorBuilder: (context, index) =>
                                    const SizedBox(height: 20),
                                itemBuilder: (context, index) {
                                  final item = displayedItems[index];
                                  return _buildInventoryCard(item);
                                },
                              ),
                      ),
              ),
            ],
          ),
          floatingActionButton: FloatingActionButton.extended(
            onPressed: () {},
            backgroundColor: AppColors.primary,
            icon: const Icon(Icons.add),
            label: const Text('Add Item'),
          ),
        );
      },
    );
  }

  Widget _buildSearchBar() {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(color: AppColors.border),
        ),
        child: const TextField(
          decoration: InputDecoration(
            icon: Icon(Icons.search, color: AppColors.textMuted),
            hintText: 'Search your items...',
            hintStyle: TextStyle(color: AppColors.textMuted, fontSize: 15),
            border: InputBorder.none,
            contentPadding: EdgeInsets.symmetric(vertical: 14),
          ),
        ),
      ),
    );
  }

  Widget _buildInventoryCard(Item item) {
    final formatCurrency = NumberFormat.simpleCurrency();
    String status = item.status.toUpperCase();
    Color statusColor;
    Color statusTextColor;
    String bidLabel;
    String bidValue;
    String timeLabel;
    String timeValue;
    Color? timeValueColor;

    if (status == 'LIVE') {
      statusColor = const Color(0xFFDCFCE7);
      statusTextColor = const Color(0xFF15803D);
      bidLabel = 'CURRENT PRICE';
      bidValue = formatCurrency.format(item.currentPrice);
      timeLabel = 'ENDS IN';
      final duration = item.endTime.difference(DateTime.now());
      timeValue = '${duration.inHours}h ${duration.inMinutes % 60}m';
      timeValueColor = AppColors.error;
    } else if (status == 'SOLD') {
      statusColor = AppColors.surfaceVariant;
      statusTextColor = AppColors.textSecondary;
      bidLabel = 'FINAL PRICE';
      bidValue = formatCurrency.format(item.currentPrice);
      timeLabel = 'WINNER';
      timeValue = 'Winner';
      timeValueColor = AppColors.primaryLight;
    } else {
      statusColor = const Color(0xFFFEF3C7);
      statusTextColor = const Color(0xFFB45309);
      bidLabel = 'STARTING PRICE';
      bidValue = formatCurrency.format(item.startingPrice);
      timeLabel = 'STARTS';
      timeValue = DateFormat('MMM dd, hh a').format(item.startTime);
    }

    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppRadius.xl),
        border: Border.all(color: AppColors.surfaceVariant),
        boxShadow: AppShadows.sm,
      ),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  child: item.images.isNotEmpty
                      ? Image.network(
                          item.images[0],
                          width: 80,
                          height: 80,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => Container(
                            width: 80,
                            height: 80,
                            color: Colors.grey[200],
                            child: const Icon(Icons.image_not_supported),
                          ),
                        )
                      : Container(
                          width: 80,
                          height: 80,
                          color: Colors.grey[200],
                          child: const Icon(Icons.image, color: AppColors.textMuted),
                        ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              item.title,
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: AppColors.textPrimary,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 4,
                            ),
                            decoration: BoxDecoration(
                              color: statusColor,
                              borderRadius: BorderRadius.circular(AppRadius.sm),
                            ),
                            child: Text(
                              status,
                              style: TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                                color: statusTextColor,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        item.description,
                        style: const TextStyle(
                          fontSize: 13,
                          color: AppColors.textSecondary,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 12),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                bidLabel,
                                style: const TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.textMuted,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                bidValue,
                                style: const TextStyle(
                                  fontSize: 15,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.primaryLight,
                                ),
                              ),
                            ],
                          ),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                timeLabel,
                                style: const TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.textMuted,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                timeValue,
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.bold,
                                  color:
                                      timeValueColor ?? AppColors.textPrimary,
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
          ),
          Container(height: 1, color: AppColors.surfaceVariant),
          IntrinsicHeight(
            child: Row(
              children: [
                Expanded(child: _buildCardAction(Icons.edit_outlined, 'Edit')),
                Container(width: 1, color: AppColors.surfaceVariant),
                Expanded(
                  child: _buildCardAction(
                    Icons.rocket_launch_outlined,
                    'Promote',
                    isPrimary: true,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCardAction(
    IconData icon,
    String label, {
    bool isPrimary = false,
  }) {
    return InkWell(
      onTap: () {},
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 14),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              icon,
              size: 20,
              color: isPrimary
                  ? AppColors.primaryLight
                  : AppColors.textSecondary,
            ),
            const SizedBox(width: 8),
            Text(
              label,
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: isPrimary
                    ? AppColors.primaryLight
                    : AppColors.textSecondary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
