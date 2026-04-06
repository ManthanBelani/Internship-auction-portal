import 'package:flutter/material.dart';
import 'dashboard_pulse.dart';
import 'create_auction.dart';
import 'fulfillment_shipping.dart';
import 'inventory_listings.dart';
import 'seller_profile.dart';
import 'wallet_payouts.dart';

class NavigationHub extends StatelessWidget {
  const NavigationHub({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Dev Navigation Hub')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildNavItem(
            context,
            'Dashboard Pulse',
            const DashboardPulseScreen(),
          ),
          _buildNavItem(
            context,
            'Create New Auction',
            const CreateAuctionScreen(),
          ),
          _buildNavItem(
            context,
            'Inventory & Listings',
            const InventoryListingsScreen(),
          ),
          _buildNavItem(
            context,
            'Fulfillment & Shipping',
            const FulfillmentShippingScreen(),
          ),
          _buildNavItem(
            context,
            'Seller Profile & Reviews',
            const SellerProfileScreen(),
          ),
          _buildNavItem(
            context,
            'Wallet & Payouts',
            const WalletPayoutsScreen(),
          ),
        ],
      ),
    );
  }

  Widget _buildNavItem(BuildContext context, String title, Widget screen) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold)),
        trailing: const Icon(Icons.arrow_forward_ios),
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => screen),
          );
        },
      ),
    );
  }
}
