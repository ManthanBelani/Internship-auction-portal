import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../widgets/glass_card.dart';
import '../providers/seller_provider.dart';

class FulfillmentShippingScreen extends StatefulWidget {
  const FulfillmentShippingScreen({super.key});

  @override
  State<FulfillmentShippingScreen> createState() =>
      _FulfillmentShippingScreenState();
}

class _FulfillmentShippingScreenState extends State<FulfillmentShippingScreen> {
  int _selectedTab = 0;
  final List<String> _tabs = ['All Items', 'Pending', 'Delivered'];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<SellerProvider>(context, listen: false).fetchTransactions();
    });
  }

  void _showTrackingDialog(int transactionId) {
    final controller = TextEditingController();
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: const Color(0xFF1A1D23),
        title: const Text(
          'Update Tracking',
          style: TextStyle(color: Colors.white),
        ),
        content: TextField(
          controller: controller,
          style: const TextStyle(color: Colors.white),
          decoration: const InputDecoration(
            hintText: 'Enter Tracking Number',
            hintStyle: TextStyle(color: Colors.white38),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              if (controller.text.isEmpty) return;
              final success = await Provider.of<SellerProvider>(
                context,
                listen: false,
              ).updateShippingStatus(transactionId, controller.text);
              if (success && mounted) {
                Navigator.pop(context);
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Tracking updated!')),
                );
                Provider.of<SellerProvider>(
                  context,
                  listen: false,
                ).fetchTransactions();
              }
            },
            child: const Text('Save'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0A0C10),
      body: Consumer<SellerProvider>(
        builder: (context, seller, _) {
          final transactions = seller.transactions;

          return Stack(
            children: [
              Column(
                children: [
                  _buildHeader(context),
                  Expanded(
                    child: seller.isLoading
                        ? const Center(child: CircularProgressIndicator())
                        : RefreshIndicator(
                            onRefresh: () async =>
                                await seller.fetchTransactions(),
                            child: SingleChildScrollView(
                              padding: const EdgeInsets.all(16),
                              child: Column(
                                children: [
                                  _buildStatsRow(transactions),
                                  const SizedBox(height: 24),
                                  _buildTabs(),
                                  const SizedBox(height: 16),
                                  if (transactions.isEmpty)
                                    const Center(
                                      heightFactor: 10,
                                      child: Text(
                                        'No transactions found',
                                        style: TextStyle(color: Colors.white38),
                                      ),
                                    )
                                  else
                                    ...transactions.map((tx) {
                                      final isShipped =
                                          tx['shipping_status'] == 'shipped';
                                      return Padding(
                                        padding: const EdgeInsets.only(
                                          bottom: 16,
                                        ),
                                        child: _buildOrderCard(
                                          transactionId: tx['id'],
                                          status: tx['payment_status'] == 'paid'
                                              ? 'Paid'
                                              : 'Pending',
                                          statusColor:
                                              tx['payment_status'] == 'paid'
                                              ? const Color(0xFF10B981)
                                              : const Color(0xFFF59E0B),
                                          title:
                                              tx['item_title'] ??
                                              'Item #${tx['item_id']}',
                                          detail:
                                              'Order #${tx['id']} • Sold for \$${tx['amount']}',
                                          image:
                                              'https://via.placeholder.com/150',
                                          actionLabel: isShipped
                                              ? 'In Transit'
                                              : 'Update Tracking',
                                          actionIcon: isShipped
                                              ? Icons.local_shipping
                                              : Icons.edit,
                                          actionColor: isShipped
                                              ? Colors.white.withOpacity(0.1)
                                              : const Color(0xFF2977F5),
                                          onAction: () {
                                            if (!isShipped) {
                                              _showTrackingDialog(tx['id']);
                                            }
                                          },
                                        ),
                                      );
                                    }).toList(),
                                  const SizedBox(height: 100),
                                ],
                              ),
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

  Widget _buildHeader(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 60, 16, 16),
      decoration: BoxDecoration(
        color: const Color(0xFF0A0C10).withOpacity(0.8),
        border: Border(
          bottom: BorderSide(color: Colors.white.withOpacity(0.05)),
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          GestureDetector(
            onTap: () => Navigator.pop(context),
            child: const Icon(Icons.arrow_back_ios_new, color: Colors.white),
          ),
          Text(
            'Fulfillment Hub',
            style: GoogleFonts.inter(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.tune, color: Colors.white, size: 20),
          ),
        ],
      ),
    );
  }

  Widget _buildStatsRow(List<dynamic> transactions) {
    double totalSales = 0;
    for (var tx in transactions) {
      totalSales += (tx['amount'] ?? 0.0);
    }
    int awaiting = transactions
        .where((tx) => tx['shipping_status'] != 'shipped')
        .length;

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          _statCard(
            Icons.payments,
            'Total Sales',
            '\$${totalSales.toStringAsFixed(0)}',
            'Across all time',
            const Color(0xFF10B981),
          ),
          const SizedBox(width: 16),
          _statCard(
            Icons.pending_actions,
            'Awaiting',
            '$awaiting',
            'shipments pending',
            Colors.grey,
          ),
        ],
      ),
    );
  }

  Widget _statCard(
    IconData icon,
    String label,
    String value,
    String subValue,
    Color trendColor,
  ) {
    return GlassCard(
      width: 180,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: const Color(0xFF2977F5), size: 16),
              const SizedBox(width: 8),
              Text(
                label.toUpperCase(),
                style: GoogleFonts.inter(
                  fontSize: 10,
                  fontWeight: FontWeight.bold,
                  color: Colors.white38,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            value,
            style: GoogleFonts.inter(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            subValue,
            style: GoogleFonts.inter(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: trendColor,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTabs() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceAround,
      children: List.generate(_tabs.length, (index) {
        final isSelected = _selectedTab == index;
        return GestureDetector(
          onTap: () => setState(() => _selectedTab = index),
          child: Container(
            padding: const EdgeInsets.only(bottom: 8),
            decoration: BoxDecoration(
              border: Border(
                bottom: BorderSide(
                  color: isSelected
                      ? const Color(0xFF2977F5)
                      : Colors.transparent,
                  width: 2,
                ),
              ),
            ),
            child: Text(
              _tabs[index],
              style: GoogleFonts.inter(
                fontSize: 14,
                fontWeight: FontWeight.bold,
                color: isSelected ? Colors.white : Colors.white38,
              ),
            ),
          ),
        );
      }),
    );
  }

  Widget _buildOrderCard({
    required int transactionId,
    required String status,
    required Color statusColor,
    required String title,
    required String detail,
    required String image,
    required String actionLabel,
    required IconData actionIcon,
    required Color actionColor,
    required VoidCallback onAction,
  }) {
    return GlassCard(
      child: Column(
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: statusColor.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Container(
                            width: 6,
                            height: 6,
                            decoration: BoxDecoration(
                              color: statusColor,
                              shape: BoxShape.circle,
                            ),
                          ),
                          const SizedBox(width: 6),
                          Text(
                            status.toUpperCase(),
                            style: GoogleFonts.inter(
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                              color: statusColor,
                              letterSpacing: 0.5,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      title,
                      style: GoogleFonts.inter(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      detail,
                      style: GoogleFonts.inter(
                        fontSize: 12,
                        color: Colors.white54,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 16),
              Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(12),
                  image: DecorationImage(
                    image: NetworkImage(image),
                    fit: BoxFit.cover,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: onAction,
              icon: Icon(actionIcon, size: 18),
              label: Text(actionLabel),
              style: ElevatedButton.styleFrom(
                backgroundColor: actionColor,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(30),
                ),
                elevation: 0,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
