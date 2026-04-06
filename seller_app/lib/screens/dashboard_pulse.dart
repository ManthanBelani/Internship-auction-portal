import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';

import '../providers/seller_provider.dart';

class DashboardPulseScreen extends StatefulWidget {
  const DashboardPulseScreen({super.key});

  @override
  State<DashboardPulseScreen> createState() => _DashboardPulseScreenState();
}

class _DashboardPulseScreenState extends State<DashboardPulseScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<SellerProvider>(context, listen: false).fetchStats();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0A0E17),
      body: Consumer<SellerProvider>(
        builder: (context, seller, _) {
          final stats = seller.stats;
          return Stack(
            children: [
              SingleChildScrollView(
                padding: const EdgeInsets.only(bottom: 100),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const _Header(),
                    const SizedBox(height: 16),
                    _KPISection(stats: stats),
                    const SizedBox(height: 24),
                    _PerformanceInsights(stats: stats),
                    const SizedBox(height: 24),
                    _RecentActivity(stats: stats),
                  ],
                ),
              ),
              const _CustomFab(),
              const _BottomNavBar(),
            ],
          );
        },
      ),
    );
  }
}

class _Header extends StatelessWidget {
  const _Header();

  @override
  Widget build(BuildContext context) {
    return Consumer<AuthProvider>(
      builder: (context, auth, _) {
        final user = auth.user;
        return Container(
          padding: const EdgeInsets.fromLTRB(24, 60, 24, 16),
          decoration: BoxDecoration(
            color: const Color(0xFF0A0E17).withOpacity(0.5),
            boxShadow: [
              BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 10),
            ],
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    width: 48,
                    height: 48,
                    padding: const EdgeInsets.all(2),
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: const Color(0xFF2977F5).withOpacity(0.5),
                        width: 2,
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFF2977F5).withOpacity(0.2),
                          blurRadius: 10,
                        ),
                      ],
                    ),
                    child: CircleAvatar(
                      backgroundImage:
                          user?.profileImage != null &&
                              user!.profileImage!.startsWith('http')
                          ? NetworkImage(user.profileImage!)
                          : const NetworkImage(
                              'https://via.placeholder.com/150',
                            ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Welcome back,',
                        style: GoogleFonts.inter(
                          fontSize: 14,
                          color: Colors.white54,
                        ),
                      ),
                      Text(
                        user?.name ?? 'Seller',
                        style: GoogleFonts.inter(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              GestureDetector(
                onTap: () => auth.logout(),
                child: Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.05),
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white.withOpacity(0.1)),
                  ),
                  child: const Icon(
                    Icons.logout,
                    color: Colors.white,
                    size: 20,
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

class _KPISection extends StatelessWidget {
  final Map<String, dynamic>? stats;
  const _KPISection({this.stats});

  @override
  Widget build(BuildContext context) {
    final earnings = stats?['totalEarnings'] ?? 0.0;
    final growth = stats?['growthPercentage'] ?? 0.0;
    final activeBids = stats?['totalBidsReceived'] ?? 0;
    final soldItems = stats?['soldItems'] ?? 0;

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.symmetric(horizontal: 24),
      child: Row(
        children: [
          _GlassCard(
            width: 280,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Total Earnings',
                      style: GoogleFonts.inter(
                        color: Colors.white60,
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const Icon(
                      Icons.payments_outlined,
                      color: Colors.white24,
                      size: 32,
                    ),
                  ],
                ),
                Text(
                  '\$${earnings.toStringAsFixed(2)}',
                  style: GoogleFonts.inter(
                    color: Colors.white,
                    fontSize: 30,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFF0BDA5E).withOpacity(0.1),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Row(
                        children: [
                          const Icon(
                            Icons.trending_up,
                            color: Color(0xFF0BDA5E),
                            size: 16,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            '+$growth%',
                            style: GoogleFonts.inter(
                              color: const Color(0xFF0BDA5E),
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const _Sparkline(),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 16),
          _GlassCard(
            width: 180,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Active Bids',
                  style: GoogleFonts.inter(
                    color: Colors.white60,
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  '$activeBids',
                  style: GoogleFonts.inter(
                    fontSize: 30,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                Text(
                  'Across listings',
                  style: GoogleFonts.inter(
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                    color: const Color(0xFF0BDA5E),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 16),
          _GlassCard(
            width: 180,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Sold Items',
                  style: GoogleFonts.inter(
                    color: Colors.white60,
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  '$soldItems',
                  style: GoogleFonts.inter(
                    fontSize: 30,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                Text(
                  'Total completed',
                  style: GoogleFonts.inter(
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                    color: Colors.white38,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Sparkline extends StatelessWidget {
  const _Sparkline();
  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        _bar(12, 0.3),
        _bar(20, 0.4),
        _bar(16, 0.5),
        _bar(28, 0.7),
        _bar(32, 1.0),
      ],
    );
  }

  Widget _bar(double height, double opacity) {
    return Container(
      width: 6,
      height: height,
      margin: const EdgeInsets.only(left: 4),
      decoration: BoxDecoration(
        color: const Color(0xFF2977F5).withOpacity(opacity),
        borderRadius: BorderRadius.circular(10),
      ),
    );
  }
}

class _PerformanceInsights extends StatelessWidget {
  final Map<String, dynamic>? stats;
  const _PerformanceInsights({this.stats});

  @override
  Widget build(BuildContext context) {
    final earnings = stats?['totalEarnings'] ?? 0.0;
    final List<dynamic> categories = stats?['salesByCategory'] ?? [];

    // Fallback data if no real sales by category
    final sections = categories.isEmpty
        ? [
            PieChartSectionData(
              color: const Color(0xFF2977F5),
              value: 100,
              radius: 20,
              showTitle: false,
            ),
          ]
        : categories.map((cat) {
            final double val = (cat['total_sales'] ?? 0.0).toDouble();
            return PieChartSectionData(
              color: Color(
                (val * 0xFFFFFF).toInt(),
              ).withOpacity(1.0), // Simplified color gen
              value: val > 0 ? val : 1,
              radius: 20,
              showTitle: false,
            );
          }).toList();

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Performance Insights',
            style: GoogleFonts.inter(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 16),
          _GlassCard(
            child: Column(
              children: [
                SizedBox(
                  height: 200,
                  child: Stack(
                    children: [
                      PieChart(
                        PieChartData(
                          sectionsSpace: 0,
                          centerSpaceRadius: 70,
                          sections: sections,
                          startDegreeOffset: -90,
                        ),
                      ),
                      Center(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              'SALES',
                              style: GoogleFonts.inter(
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                                color: Colors.white54,
                              ),
                            ),
                            Text(
                              '\$${(earnings / 1000).toStringAsFixed(1)}k',
                              style: GoogleFonts.inter(
                                fontSize: 24,
                                fontWeight: FontWeight.w900,
                                color: Colors.white,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 24),
                if (categories.isNotEmpty)
                  Wrap(
                    spacing: 16,
                    runSpacing: 8,
                    alignment: WrapAlignment.center,
                    children: categories
                        .map(
                          (cat) => _LegendItem(
                            color: const Color(
                              0xFF2977F5,
                            ), // Fixed for simplicity
                            label: '${cat['category']} (${cat['count']})',
                          ),
                        )
                        .toList(),
                  )
                else
                  const Text(
                    'No category data available',
                    style: TextStyle(color: Colors.white38),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _LegendItem extends StatelessWidget {
  final Color color;
  final String label;
  const _LegendItem({required this.color, required this.label});
  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 6),
        Text(
          label,
          style: GoogleFonts.inter(fontSize: 12, color: Colors.white70),
        ),
      ],
    );
  }
}

class _RecentActivity extends StatelessWidget {
  final Map<String, dynamic>? stats;
  const _RecentActivity({this.stats});

  @override
  Widget build(BuildContext context) {
    final List<dynamic> activities = stats?['recentActivity'] ?? [];

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Recent Activity',
                style: GoogleFonts.inter(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
              TextButton(
                onPressed: () {},
                child: Text(
                  'View All',
                  style: GoogleFonts.inter(
                    color: const Color(0xFF2977F5),
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (activities.isEmpty)
            const Center(
              child: Text(
                'No recent activity',
                style: TextStyle(color: Colors.white38),
              ),
            )
          else
            ...activities
                .map(
                  (act) => Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: _ActivityItem(
                      image:
                          'https://via.placeholder.com/50', // Would need real mapping
                      titleParts: [
                        act['type'] == 'bid' ? 'New bid on ' : 'Item Sold: ',
                        act['item_title'] ?? 'Item',
                      ],
                      subtitle: 'by ${act['actor_name']} • Just now',
                      amount: act['type'] == 'bid'
                          ? '+\$${act['amount']}'
                          : '\$${act['amount']}',
                      isPositive: act['type'] == 'bid',
                    ),
                  ),
                )
                .toList(),
        ],
      ),
    );
  }
}

class _ActivityItem extends StatelessWidget {
  final String image;
  final List<String> titleParts;
  final String subtitle;
  final String amount;
  final bool isPositive;

  const _ActivityItem({
    required this.image,
    required this.titleParts,
    required this.subtitle,
    required this.amount,
    required this.isPositive,
  });

  @override
  Widget build(BuildContext context) {
    return _GlassCard(
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              CircleAvatar(radius: 20, backgroundImage: NetworkImage(image)),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  RichText(
                    text: TextSpan(
                      style: GoogleFonts.inter(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                      children: [
                        TextSpan(text: titleParts[0]),
                        TextSpan(
                          text: titleParts[1],
                          style: const TextStyle(color: Color(0xFF2977F5)),
                        ),
                      ],
                    ),
                  ),
                  Text(
                    subtitle,
                    style: GoogleFonts.inter(
                      fontSize: 12,
                      color: Colors.white38,
                    ),
                  ),
                ],
              ),
            ],
          ),
          Text(
            amount,
            style: GoogleFonts.inter(
              fontSize: 14,
              fontWeight: FontWeight.bold,
              color: isPositive ? const Color(0xFF0BDA5E) : Colors.white,
            ),
          ),
        ],
      ),
    );
  }
}

class _CustomFab extends StatelessWidget {
  const _CustomFab();
  @override
  Widget build(BuildContext context) {
    return Positioned(
      bottom: 100,
      right: 24,
      child: Container(
        width: 64,
        height: 64,
        decoration: BoxDecoration(
          color: const Color(0xFF2977F5),
          shape: BoxShape.circle,
          boxShadow: [
            BoxShadow(
              color: const Color(0xFF2977F5).withOpacity(0.4),
              blurRadius: 20,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: const Icon(Icons.add, color: Colors.white, size: 32),
      ),
    );
  }
}

class _BottomNavBar extends StatelessWidget {
  const _BottomNavBar();
  @override
  Widget build(BuildContext context) {
    return Positioned(
      bottom: 0,
      left: 0,
      right: 0,
      child: Container(
        height: 80,
        padding: const EdgeInsets.symmetric(horizontal: 24),
        decoration: BoxDecoration(
          color: const Color(0xFF0A0E17).withOpacity(0.8),
          border: Border(top: BorderSide(color: Colors.white.withOpacity(0.1))),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            _navItem(Icons.dashboard, 'Pulse', true),
            _navItem(Icons.inventory_2, 'Inventory', false),
            _navItem(Icons.chat_bubble, 'Messages', false),
            _navItem(Icons.settings, 'Settings', false),
          ],
        ),
      ),
    );
  }

  Widget _navItem(IconData icon, String label, bool isActive) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(icon, color: isActive ? const Color(0xFF2977F5) : Colors.white24),
        const SizedBox(height: 4),
        Text(
          label,
          style: GoogleFonts.inter(
            fontSize: 10,
            fontWeight: FontWeight.bold,
            letterSpacing: 0.5,
            color: isActive ? const Color(0xFF2977F5) : Colors.white24,
          ),
        ),
      ],
    );
  }
}

class _GlassCard extends StatelessWidget {
  final Widget child;
  final double? width;
  const _GlassCard({required this.child, this.width});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: width,
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.05),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Colors.white.withOpacity(0.1)),
      ),
      child: child,
    );
  }
}
