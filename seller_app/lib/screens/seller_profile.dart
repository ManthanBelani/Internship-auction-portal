import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../widgets/glass_card.dart';
import '../providers/auth_provider.dart';

import '../providers/seller_provider.dart';

class SellerProfileScreen extends StatefulWidget {
  const SellerProfileScreen({super.key});

  @override
  State<SellerProfileScreen> createState() => _SellerProfileScreenState();
}

class _SellerProfileScreenState extends State<SellerProfileScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final seller = Provider.of<SellerProvider>(context, listen: false);
      if (auth.user != null) {
        seller.fetchStats();
        seller.fetchReviews(auth.user!.id);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Consumer2<AuthProvider, SellerProvider>(
      builder: (context, auth, seller, _) {
        final user = auth.user;
        final stats = seller.stats;

        return Scaffold(
          backgroundColor: const Color(0xFF0A0C10),
          body: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 48),
            child: Column(
              children: [
                _buildAppBar(context),
                const SizedBox(height: 24),
                _buildHero(
                  user?.name ?? 'Seller',
                  user?.role ?? 'Premium Seller',
                  user?.profileImage,
                  stats?['avgRating'] ?? 0.0,
                ),
                const SizedBox(height: 32),
                _buildStatsRow(stats),
                const SizedBox(height: 32),
                _buildRatingOverview(
                  stats?['avgRating'] ?? 0.0,
                  stats?['reviewCount'] ?? 0,
                ),
                const SizedBox(height: 32),
                _buildReviewsList(seller.reviews),
                const SizedBox(height: 32),
                _buildSettings(),
                const SizedBox(height: 32),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () => auth.logout().then(
                      (_) => Navigator.pushReplacementNamed(context, '/login'),
                    ),
                    icon: const Icon(Icons.logout),
                    label: const Text('Logout'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.redAccent.withOpacity(0.8),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 100),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildAppBar(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        GestureDetector(
          onTap: () => Navigator.pop(context),
          child: Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.05),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.arrow_back_ios_new,
              color: Colors.white,
              size: 20,
            ),
          ),
        ),
        Text(
          'Seller Profile',
          style: GoogleFonts.inter(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.05),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.share, color: Colors.white, size: 20),
            ),
            const SizedBox(width: 8),
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.05),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.more_horiz,
                color: Colors.white,
                size: 20,
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildHero(
    String name,
    String role,
    String? imageUrl,
    dynamic rating,
  ) {
    return Column(
      children: [
        Stack(
          alignment: Alignment.center,
          children: [
            Container(
              width: 150,
              height: 150,
              decoration: BoxDecoration(
                color: const Color(0xFF2977F5).withOpacity(0.2),
                shape: BoxShape.circle,
                boxShadow: const [
                  BoxShadow(
                    color: Color(0xFF2977F5),
                    blurRadius: 60,
                    spreadRadius: -20,
                  ),
                ],
              ),
            ),
            Container(
              width: 128,
              height: 128,
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(
                  color: const Color(0xFF2977F5).withOpacity(0.3),
                  width: 4,
                ),
              ),
              child: CircleAvatar(
                backgroundImage: imageUrl != null && imageUrl.startsWith('http')
                    ? NetworkImage(imageUrl)
                    : const NetworkImage('https://via.placeholder.com/150'),
              ),
            ),
            Positioned(
              bottom: 0,
              right: 0,
              child: Container(
                padding: const EdgeInsets.all(6),
                decoration: const BoxDecoration(
                  color: Color(0xFF2977F5),
                  shape: BoxShape.circle,
                  border: Border.fromBorderSide(
                    BorderSide(color: Color(0xFF0A0C10), width: 4),
                  ),
                ),
                child: const Icon(
                  Icons.verified,
                  color: Colors.white,
                  size: 14,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        Text(
          name,
          style: GoogleFonts.inter(
            fontSize: 24,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        Text(
          role,
          style: GoogleFonts.inter(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: Colors.white54,
          ),
        ),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
          decoration: BoxDecoration(
            color: const Color(0xFF2977F5).withOpacity(0.1),
            borderRadius: BorderRadius.circular(20),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.star, color: Color(0xFF2977F5), size: 16),
              const SizedBox(width: 4),
              Text(
                '${rating.toStringAsFixed(1)} Rating',
                style: GoogleFonts.inter(
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                  color: const Color(0xFF2977F5),
                ),
              ),
              Text(
                ' • Seller Since 2021',
                style: GoogleFonts.inter(fontSize: 12, color: Colors.white54),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildStatsRow(Map<String, dynamic>? stats) {
    final earnings = stats?['totalEarnings'] ?? 0.0;
    final bids = stats?['totalBidsReceived'] ?? 0;
    final items = stats?['soldItems'] ?? 0;

    return Row(
      children: [
        _statBox('Sales', '\$${(earnings / 1000).toStringAsFixed(1)}k'),
        const SizedBox(width: 12),
        _statBox('Sold', '$items'),
        const SizedBox(width: 12),
        _statBox('Bids', '$bids'),
      ],
    );
  }

  Widget _statBox(String label, String value) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 16),
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.05),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.white.withOpacity(0.05)),
        ),
        child: Column(
          children: [
            Text(
              label.toUpperCase(),
              style: GoogleFonts.inter(
                fontSize: 10,
                fontWeight: FontWeight.bold,
                color: Colors.white38,
                letterSpacing: 1.0,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              value,
              style: GoogleFonts.inter(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRatingOverview(dynamic avgRating, int reviewCount) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Customer Reviews',
          style: GoogleFonts.inter(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        const SizedBox(height: 16),
        GlassCard(
          child: Row(
            children: [
              Column(
                children: [
                  Text(
                    avgRating.toStringAsFixed(1),
                    style: GoogleFonts.inter(
                      fontSize: 48,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                      height: 1.0,
                    ),
                  ),
                  Row(
                    children: List.generate(
                      5,
                      (i) => Icon(
                        Icons.star,
                        color: i < avgRating.floor()
                            ? const Color(0xFF2977F5)
                            : Colors.white10,
                        size: 16,
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    '${reviewCount.toString().toUpperCase()} REVIEWS',
                    style: GoogleFonts.inter(
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                      color: Colors.white38,
                    ),
                  ),
                ],
              ),
              const SizedBox(width: 24),
              Expanded(
                child: Column(
                  children: [
                    _progressBar(5, 0.92),
                    _progressBar(4, 0.07),
                    _progressBar(3, 0.01),
                    _progressBar(2, 0.0),
                    _progressBar(1, 0.0),
                  ],
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _progressBar(int star, double value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        children: [
          Text(
            '$star',
            style: GoogleFonts.inter(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: Colors.white38,
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: ClipRRect(
              borderRadius: BorderRadius.circular(2),
              child: LinearProgressIndicator(
                value: value,
                backgroundColor: Colors.white.withOpacity(0.1),
                valueColor: AlwaysStoppedAnimation<Color>(
                  value > 0.5
                      ? const Color(0xFF2977F5)
                      : const Color(0xFF2977F5).withOpacity(0.5),
                ),
                minHeight: 6,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildReviewsList(List<dynamic> reviews) {
    if (reviews.isEmpty) {
      return const Center(
        child: Text('No reviews yet', style: TextStyle(color: Colors.white38)),
      );
    }
    return Column(
      children: reviews.take(5).map((review) {
        return Padding(
          padding: const EdgeInsets.only(bottom: 16),
          child: _reviewCard(
            name: review['reviewerName'] ?? 'Anonymous',
            text: review['reviewText'] ?? '',
            image: 'https://via.placeholder.com/150',
            rating: review['rating'] ?? 5,
          ),
        );
      }).toList(),
    );
  }

  Widget _reviewCard({
    required String name,
    required String text,
    required String image,
    required int rating,
  }) {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    radius: 16,
                    backgroundImage: NetworkImage(image),
                  ),
                  const SizedBox(width: 12),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        name,
                        style: GoogleFonts.inter(
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                      Row(
                        children: List.generate(
                          5,
                          (i) => Icon(
                            Icons.star,
                            color: i < rating
                                ? const Color(0xFF2977F5)
                                : Colors.white10,
                            size: 12,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: const Color(0xFF10B981).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  children: [
                    const Icon(
                      Icons.check_circle,
                      color: Color(0xFF10B981),
                      size: 12,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      'Verified',
                      style: GoogleFonts.inter(
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                        color: const Color(0xFF10B981),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            text,
            style: GoogleFonts.inter(
              fontSize: 14,
              height: 1.5,
              color: Colors.white70,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSettings() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Seller Settings',
          style: GoogleFonts.inter(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        const SizedBox(height: 16),
        Container(
          decoration: BoxDecoration(
            color: Colors.white.withOpacity(0.05),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Colors.white.withOpacity(0.1)),
          ),
          child: Column(
            children: [
              _settingItem(
                Icons.notifications,
                'Auction Alerts',
                'Real-time bid notifications',
                true,
              ),
              Divider(color: Colors.white.withOpacity(0.05), height: 1),
              _settingItem(
                Icons.analytics,
                'Weekly Performance',
                'Automated sales summary',
                false,
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _settingItem(
    IconData icon,
    String title,
    String subtitle,
    bool isActive,
  ) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: const Color(0xFF2977F5).withOpacity(0.2),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: const Color(0xFF2977F5), size: 20),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: GoogleFonts.inter(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                Text(
                  subtitle,
                  style: GoogleFonts.inter(fontSize: 12, color: Colors.white54),
                ),
              ],
            ),
          ),
          Switch(
            value: isActive,
            onChanged: (val) {},
            activeColor: const Color(0xFF2977F5),
            activeTrackColor: const Color(0xFF2977F5).withOpacity(0.4),
          ),
        ],
      ),
    );
  }
}
