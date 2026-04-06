import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../widgets/glass_card.dart';
import '../providers/seller_provider.dart';

class WalletPayoutsScreen extends StatefulWidget {
  const WalletPayoutsScreen({super.key});

  @override
  State<WalletPayoutsScreen> createState() => _WalletPayoutsScreenState();
}

class _WalletPayoutsScreenState extends State<WalletPayoutsScreen> {
  final _amountController = TextEditingController();
  String _selectedMethod = 'Bank';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final seller = Provider.of<SellerProvider>(context, listen: false);
      seller.fetchStats();
      seller.fetchTransactions();
    });
  }

  void _handlePayoutRequest() async {
    final amount = double.tryParse(_amountController.text);
    if (amount == null || amount <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter a valid amount')),
      );
      return;
    }

    final seller = Provider.of<SellerProvider>(context, listen: false);
    final success = await seller.requestPayout(amount, _selectedMethod);

    if (success && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Payout request submitted successfully!')),
      );
      _amountController.clear();
      seller.fetchStats();
      seller.fetchTransactions();
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to submit payout request')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0A0C10),
      body: Consumer<SellerProvider>(
        builder: (context, seller, _) {
          final stats = seller.stats;
          final balance = stats?['totalEarnings'] ?? 0.0;
          final pending = (stats?['pendingEarnings'] ?? 0.0).toDouble();

          return SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 48),
                _buildHeader(context),
                const SizedBox(height: 24),
                _buildHeroCard(balance, pending),
                const SizedBox(height: 32),
                Text(
                  'Withdraw Funds',
                  style: GoogleFonts.inter(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 16),
                _buildWithdrawForm(),
                const SizedBox(height: 32),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'History',
                      style: GoogleFonts.inter(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                    Text(
                      'View All',
                      style: GoogleFonts.inter(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: const Color(0xFF2977F5),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                if (seller.transactions.isEmpty)
                  const Center(
                    child: Padding(
                      padding: EdgeInsets.all(20.0),
                      child: Text(
                        'No history found',
                        style: TextStyle(color: Colors.white38),
                      ),
                    ),
                  )
                else
                  ...seller.transactions
                      .take(5)
                      .map(
                        (tx) => Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: _buildHistoryItem(
                            icon: tx['type'] == 'sale'
                                ? Icons.add_circle_outline
                                : Icons.call_made,
                            color: tx['type'] == 'sale'
                                ? const Color(0xFF10B981)
                                : const Color(0xFF2977F5),
                            title: tx['type'] == 'sale'
                                ? 'Sale: ${tx['item_title']}'
                                : 'Payout Request',
                            date: tx['updated_at'] ?? 'Just now',
                            amount:
                                '${tx['type'] == 'sale' ? '+' : '-'}\$${tx['amount']}',
                            status: tx['payment_status'] ?? 'Completed',
                          ),
                        ),
                      )
                      .toList(),
                const SizedBox(height: 100),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildHeader(BuildContext context) {
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
          'Wallet & Payouts',
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
            color: const Color(0xFF2977F5).withOpacity(0.2),
            shape: BoxShape.circle,
          ),
          child: const Icon(
            Icons.account_balance_wallet,
            color: Color(0xFF2977F5),
            size: 20,
          ),
        ),
      ],
    );
  }

  Widget _buildHeroCard(dynamic balance, dynamic pending) {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'AVAILABLE FOR PAYOUT',
            style: GoogleFonts.inter(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: Colors.white60,
              letterSpacing: 1.0,
            ),
          ),
          const SizedBox(height: 8),
          Row(
            crossAxisAlignment: CrossAxisAlignment.baseline,
            textBaseline: TextBaseline.alphabetic,
            children: [
              Text(
                r'$',
                style: GoogleFonts.inter(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: const Color(0xFF2977F5),
                ),
              ),
              const SizedBox(width: 4),
              Text(
                balance.toStringAsFixed(2),
                style: GoogleFonts.inter(
                  fontSize: 40,
                  fontWeight: FontWeight.w900,
                  color: Colors.white,
                  letterSpacing: -1.0,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              const Icon(Icons.schedule, color: Colors.white38, size: 14),
              const SizedBox(width: 4),
              Text(
                'Last updated: Just now',
                style: GoogleFonts.inter(fontSize: 12, color: Colors.white38),
              ),
            ],
          ),
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Pending Clearance',
                style: GoogleFonts.inter(
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                  color: Colors.white60,
                ),
              ),
              Text(
                '\$${pending.toStringAsFixed(2)}',
                style: GoogleFonts.inter(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: LinearProgressIndicator(
              value: balance > 0 ? (balance / (balance + pending)) : 0.0,
              backgroundColor: Colors.white.withOpacity(0.1),
              valueColor: const AlwaysStoppedAnimation<Color>(
                Color(0xFF2977F5),
              ),
              minHeight: 6,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildWithdrawForm() {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Enter Amount',
            style: GoogleFonts.inter(
              fontSize: 14,
              fontWeight: FontWeight.w500,
              color: Colors.white60,
            ),
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            decoration: BoxDecoration(
              color: const Color(0xFF0A0C10).withOpacity(0.5),
              borderRadius: BorderRadius.circular(30),
              border: Border.all(color: Colors.white.withOpacity(0.1)),
            ),
            child: TextField(
              controller: _amountController,
              keyboardType: TextInputType.number,
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.bold,
              ),
              decoration: InputDecoration(
                border: InputBorder.none,
                prefixText: r'$ ',
                prefixStyle: GoogleFonts.inter(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Colors.white38,
                ),
                hintText: '0.00',
                hintStyle: const TextStyle(color: Colors.white24),
              ),
            ),
          ),
          const SizedBox(height: 24),
          Text(
            'Payout Method',
            style: GoogleFonts.inter(
              fontSize: 14,
              fontWeight: FontWeight.w500,
              color: Colors.white60,
            ),
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: GestureDetector(
                  onTap: () => setState(() => _selectedMethod = 'Bank'),
                  child: _methodButton(
                    Icons.account_balance,
                    'Bank',
                    _selectedMethod == 'Bank',
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: GestureDetector(
                  onTap: () => setState(() => _selectedMethod = 'PayPal'),
                  child: _methodButton(
                    Icons.payments,
                    'PayPal',
                    _selectedMethod == 'PayPal',
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: GestureDetector(
                  onTap: () => setState(() => _selectedMethod = 'Stripe'),
                  child: _methodButton(
                    Icons.credit_card,
                    'Stripe',
                    _selectedMethod == 'Stripe',
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _handlePayoutRequest,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF2977F5),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 20),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(30),
                ),
                elevation: 8,
                shadowColor: const Color(0xFF2977F5).withOpacity(0.4),
              ),
              child: Text(
                'Request Instant Payout',
                style: GoogleFonts.inter(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _methodButton(IconData icon, String label, bool isSelected) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 16),
      decoration: BoxDecoration(
        color: isSelected
            ? const Color(0xFF2977F5).withOpacity(0.1)
            : Colors.white.withOpacity(0.05),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isSelected ? const Color(0xFF2977F5) : Colors.transparent,
          width: 2,
        ),
      ),
      child: Column(
        children: [
          Icon(
            icon,
            color: isSelected ? const Color(0xFF2977F5) : Colors.white60,
            size: 24,
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: GoogleFonts.inter(
              fontSize: 10,
              fontWeight: FontWeight.bold,
              color: isSelected ? const Color(0xFF2977F5) : Colors.white60,
              letterSpacing: 0.5,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHistoryItem({
    required IconData icon,
    required Color color,
    required String title,
    required String date,
    required String amount,
    required String status,
  }) {
    return GlassCard(
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: color.withOpacity(0.2),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: color, size: 20),
              ),
              const SizedBox(width: 12),
              Column(
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
                  const SizedBox(height: 2),
                  Text(
                    date,
                    style: GoogleFonts.inter(
                      fontSize: 12,
                      color: Colors.white38,
                    ),
                  ),
                ],
              ),
            ],
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                amount,
                style: GoogleFonts.inter(
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  Container(
                    width: 6,
                    height: 6,
                    decoration: BoxDecoration(
                      color: color,
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 4),
                  Text(
                    status.toUpperCase(),
                    style: GoogleFonts.inter(
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                      color: color,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }
}
