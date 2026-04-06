import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../widgets/glass_card.dart';
import '../providers/auth_provider.dart';
import '../providers/seller_provider.dart';
import '../models/auction_item.dart';

class InventoryListingsScreen extends StatefulWidget {
  const InventoryListingsScreen({super.key});

  @override
  State<InventoryListingsScreen> createState() =>
      _InventoryListingsScreenState();
}

class _InventoryListingsScreenState extends State<InventoryListingsScreen> {
  int _selectedTab = 0;
  final List<String> _tabs = ['Active', 'Pending', 'Sold', 'Drafts'];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      if (auth.user != null) {
        Provider.of<SellerProvider>(
          context,
          listen: false,
        ).fetchMyItems(auth.user!.id);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0A0C10),
      body: Consumer<SellerProvider>(
        builder: (context, sellerProvider, _) {
          return Stack(
            children: [
              Column(
                children: [
                  _buildHeader(),
                  Expanded(
                    child: sellerProvider.isLoading
                        ? const Center(child: CircularProgressIndicator())
                        : sellerProvider.error != null
                        ? Center(
                            child: Text(
                              sellerProvider.error!,
                              style: const TextStyle(color: Colors.redAccent),
                            ),
                          )
                        : sellerProvider.items.isEmpty
                        ? const Center(
                            child: Text(
                              'No listings found',
                              style: TextStyle(color: Colors.white54),
                            ),
                          )
                        : RefreshIndicator(
                            onRefresh: () async {
                              final auth = Provider.of<AuthProvider>(
                                context,
                                listen: false,
                              );
                              if (auth.user != null) {
                                await sellerProvider.fetchMyItems(
                                  auth.user!.id,
                                );
                              }
                            },
                            child: ListView.builder(
                              padding: const EdgeInsets.all(16),
                              itemCount: sellerProvider.items.length,
                              itemBuilder: (context, index) {
                                final item = sellerProvider.items[index];
                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 16),
                                  child: _buildListingCardFromItem(item),
                                );
                              },
                            ),
                          ),
                  ),
                ],
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildListingCardFromItem(AuctionItem item) {
    final now = DateTime.now();
    final remaining = item.endTime.difference(now);
    final timeLeft = remaining.isNegative
        ? 'Ended'
        : '${remaining.inDays}d ${remaining.inHours % 24}h';

    // Calculate progress (this is arbitrary without a start time, let's assume 7 days duration for now)
    final progress = remaining.isNegative
        ? 1.0
        : (1.0 - (remaining.inSeconds / (7 * 24 * 3600))).clamp(0.0, 1.0);

    return _buildListingCard(
      image: item.images.isNotEmpty
          ? item.images[0]
          : 'https://via.placeholder.com/150',
      title: item.title,
      category: 'Auction', // Category not in model yet
      price: '\$${(item.currentBid ?? item.startingPrice).toStringAsFixed(2)}',
      timeLeft: timeLeft,
      progress: progress,
      views: '0',
      bids: '${item.bidCount} Bids',
    );
  }

  Widget _buildHeader() {
    return Container(
      padding: const EdgeInsets.fromLTRB(24, 60, 24, 16),
      decoration: BoxDecoration(
        color: const Color(0xFF0A0C10).withOpacity(0.8),
        border: Border(
          bottom: BorderSide(color: Colors.white.withOpacity(0.05)),
        ),
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'My Listings',
                style: GoogleFonts.inter(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
              Row(
                children: [
                  _iconButton(Icons.search),
                  const SizedBox(width: 12),
                  _iconButton(Icons.add),
                ],
              ),
            ],
          ),
          const SizedBox(height: 24),
          Container(
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.05),
              borderRadius: BorderRadius.circular(30),
            ),
            child: Row(
              children: List.generate(_tabs.length, (index) {
                final isSelected = _selectedTab == index;
                return Expanded(
                  child: GestureDetector(
                    onTap: () => setState(() => _selectedTab = index),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      decoration: BoxDecoration(
                        color: isSelected
                            ? const Color(0xFF2977F5)
                            : Colors.transparent,
                        borderRadius: BorderRadius.circular(24),
                        boxShadow: isSelected
                            ? [
                                BoxShadow(
                                  color: const Color(
                                    0xFF2977F5,
                                  ).withOpacity(0.2),
                                  blurRadius: 8,
                                  offset: const Offset(0, 4),
                                ),
                              ]
                            : null,
                      ),
                      child: Center(
                        child: Text(
                          _tabs[index],
                          style: GoogleFonts.inter(
                            fontSize: 14,
                            fontWeight: isSelected
                                ? FontWeight.w600
                                : FontWeight.w500,
                            color: isSelected ? Colors.white : Colors.white54,
                          ),
                        ),
                      ),
                    ),
                  ),
                );
              }),
            ),
          ),
        ],
      ),
    );
  }

  Widget _iconButton(IconData icon) {
    return Container(
      width: 40,
      height: 40,
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.05),
        shape: BoxShape.circle,
      ),
      child: Icon(icon, color: Colors.white, size: 22),
    );
  }

  Widget _buildListingCard({
    required String image,
    required String title,
    required String category,
    required String price,
    required String timeLeft,
    required double progress,
    required String views,
    required String bids,
  }) {
    return GlassCard(
      child: Column(
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 96,
                height: 96,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(12),
                  image: DecorationImage(
                    image: NetworkImage(image),
                    fit: BoxFit.cover,
                  ),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                title,
                                style: GoogleFonts.inter(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w600,
                                  color: Colors.white,
                                ),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 4),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 2,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.05),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  category,
                                  style: GoogleFonts.inter(
                                    fontSize: 10,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.white54,
                                    letterSpacing: 0.5,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Text(
                              'HIGHEST BID',
                              style: GoogleFonts.inter(
                                fontSize: 10,
                                fontWeight: FontWeight.w500,
                                color: Colors.white38,
                                letterSpacing: 0.5,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              price,
                              style: GoogleFonts.inter(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: const Color(0xFF2977F5),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'Time remaining: $timeLeft',
                          style: GoogleFonts.inter(
                            fontSize: 11,
                            fontWeight: FontWeight.w500,
                            color: Colors.white38,
                          ),
                        ),
                        Text(
                          '${(progress * 100).toInt()}%',
                          style: GoogleFonts.inter(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(4),
                      child: LinearProgressIndicator(
                        value: progress,
                        backgroundColor: Colors.white.withOpacity(0.05),
                        valueColor: const AlwaysStoppedAnimation<Color>(
                          Color(0xFF2977F5),
                        ),
                        minHeight: 6,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Container(height: 1, color: Colors.white.withOpacity(0.05)),
          const SizedBox(height: 12),
          Row(
            children: [
              _statItem(Icons.visibility, views),
              const SizedBox(width: 24),
              _statItem(Icons.gavel, bids),
            ],
          ),
        ],
      ),
    );
  }

  Widget _statItem(IconData icon, String value) {
    return Row(
      children: [
        Icon(icon, color: Colors.white38, size: 18),
        const SizedBox(width: 6),
        Text(
          value,
          style: GoogleFonts.inter(
            fontSize: 12,
            fontWeight: FontWeight.bold,
            color: Colors.white38,
          ),
        ),
      ],
    );
  }
}
