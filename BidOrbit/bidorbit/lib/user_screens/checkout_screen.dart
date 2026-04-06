import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/item.dart';
import '../models/shipping_address.dart';
import '../providers/shipping_provider.dart';
import '../providers/payment_provider.dart';
import '../providers/order_provider.dart';
import '../theme/app_theme.dart';
import 'shipping_address_screen.dart';
import 'payment_method_screen.dart';
import 'order_confirmation_screen.dart';

class CheckoutScreen extends StatefulWidget {
  final Item item;

  const CheckoutScreen({Key? key, required this.item}) : super(key: key);

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  ShippingAddress? _selectedAddress;
  double _shippingCost = 0.0;
  bool _isProcessing = false;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    final shippingProvider = Provider.of<ShippingProvider>(context, listen: false);
    final paymentProvider = Provider.of<PaymentProvider>(context, listen: false);
    
    await Future.wait([
      shippingProvider.fetchAddresses(),
      paymentProvider.fetchPaymentMethods(),
    ]);

    if (shippingProvider.defaultAddress != null) {
      setState(() {
        _selectedAddress = shippingProvider.defaultAddress;
      });
      _calculateShipping();
    }
  }

  Future<void> _calculateShipping() async {
    if (_selectedAddress == null) return;

    final shippingProvider = Provider.of<ShippingProvider>(context, listen: false);
    final cost = await shippingProvider.calculateShipping(
      widget.item.id,
      _selectedAddress!.id,
    );

    if (cost != null) {
      setState(() {
        _shippingCost = cost;
      });
    }
  }

  Future<void> _processCheckout() async {
    if (_selectedAddress == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a shipping address')),
      );
      return;
    }

    setState(() {
      _isProcessing = true;
    });

    try {
      final orderProvider = Provider.of<OrderProvider>(context, listen: false);
      final success = await orderProvider.createOrder(
        widget.item.id,
        _selectedAddress!.id,
      );

      if (success) {
        final paymentProvider = Provider.of<PaymentProvider>(context, listen: false);
        final totalAmount = widget.item.currentPrice + _shippingCost;

        final paymentIntent = await paymentProvider.createPaymentIntent(
          widget.item.id,
          totalAmount,
        );

        if (paymentIntent != null) {
          final confirmed = await paymentProvider.confirmPayment(
            widget.item.id,
            paymentIntent['paymentIntentId'],
            'stripe',
          );

          if (confirmed) {
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(
                builder: (context) => OrderConfirmationScreen(
                  item: widget.item,
                  totalAmount: totalAmount,
                ),
              ),
            );
          } else {
            throw Exception('Payment confirmation failed');
          }
        } else {
          throw Exception('Failed to create payment intent');
        }
      } else {
        throw Exception('Failed to create order');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Checkout failed: ${e.toString()}')),
      );
    } finally {
      setState(() {
        _isProcessing = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final totalAmount = widget.item.currentPrice + _shippingCost;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Checkout'),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Item Summary
            _buildItemSummary(),
            const SizedBox(height: 24),

            // Shipping Address
            _buildShippingSection(),
            const SizedBox(height: 24),

            // Payment Method
            _buildPaymentSection(),
            const SizedBox(height: 24),

            // Order Summary
            _buildOrderSummary(totalAmount),
            const SizedBox(height: 24),

            // Checkout Button
            _buildCheckoutButton(totalAmount),
          ],
        ),
      ),
    );
  }

  Widget _buildItemSummary() {
    final imageUrl = widget.item.images.isNotEmpty ? widget.item.images[0] : null;

    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(AppRadius.md),
              child: imageUrl != null
                  ? Image.network(
                      imageUrl,
                      width: 80,
                      height: 80,
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) =>
                          _buildPlaceholder(),
                    )
                  : _buildPlaceholder(),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    widget.item.title,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    '\$${widget.item.currentPrice.toStringAsFixed(2)}',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: AppColors.primary,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildShippingSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text(
              'Shipping Address',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            TextButton(
              onPressed: () async {
                final result = await Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const ShippingAddressScreen(),
                  ),
                );
                if (result != null && result is ShippingAddress) {
                  setState(() {
                    _selectedAddress = result;
                  });
                  _calculateShipping();
                }
              },
              child: const Text('Change'),
            ),
          ],
        ),
        const SizedBox(height: 8),
        if (_selectedAddress != null)
          Card(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _selectedAddress!.fullName,
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 8),
                  Text(_selectedAddress!.fullAddress),
                  const SizedBox(height: 4),
                  Text('Phone: ${_selectedAddress!.phone}'),
                ],
              ),
            ),
          )
        else
          Card(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
            child: InkWell(
              onTap: () async {
                final result = await Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const ShippingAddressScreen(),
                  ),
                );
                if (result != null && result is ShippingAddress) {
                  setState(() {
                    _selectedAddress = result;
                  });
                  _calculateShipping();
                }
              },
              borderRadius: BorderRadius.circular(AppRadius.md),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    const Icon(Icons.add_location_alt, color: AppColors.textMuted),
                    const SizedBox(width: 12),
                    const Text('Add Shipping Address'),
                  ],
                ),
              ),
            ),
          ),
      ],
    );
  }

  Widget _buildPaymentSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text(
              'Payment Method',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            TextButton(
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const PaymentMethodScreen(),
                  ),
                );
              },
              child: const Text('Manage'),
            ),
          ],
        ),
        const SizedBox(height: 8),
        Consumer<PaymentProvider>(
          builder: (context, paymentProvider, child) {
            final defaultMethod = paymentProvider.defaultMethod;
            if (defaultMethod != null) {
              return Card(
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Icon(_getCardIcon(defaultMethod.brand), size: 32),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              defaultMethod.displayName,
                              style: const TextStyle(fontWeight: FontWeight.bold),
                            ),
                            Text(
                              'Expires ${defaultMethod.expiryDisplay}',
                              style: const TextStyle(color: AppColors.textSecondary, fontSize: 12),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }
            return Card(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
              child: InkWell(
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => const PaymentMethodScreen(),
                    ),
                  );
                },
                borderRadius: BorderRadius.circular(AppRadius.md),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      const Icon(Icons.add_card, color: AppColors.textMuted),
                      const SizedBox(width: 12),
                      const Text('Add Payment Method'),
                    ],
                  ),
                ),
              ),
            );
          },
        ),
      ],
    );
  }

  Widget _buildOrderSummary(double totalAmount) {
    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Order Summary',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            _buildSummaryRow('Item Price', '\$${widget.item.currentPrice.toStringAsFixed(2)}'),
            const SizedBox(height: 8),
            _buildSummaryRow('Shipping', '\$${_shippingCost.toStringAsFixed(2)}'),
            const Divider(height: 24),
            _buildSummaryRow(
              'Total',
              '\$${totalAmount.toStringAsFixed(2)}',
              isTotal: true,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSummaryRow(String label, String value, {bool isTotal = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: isTotal ? 18 : 14,
            fontWeight: isTotal ? FontWeight.bold : FontWeight.normal,
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontSize: isTotal ? 20 : 16,
            fontWeight: FontWeight.bold,
            color: isTotal ? AppColors.primary : null,
          ),
        ),
      ],
    );
  }

  Widget _buildCheckoutButton(double totalAmount) {
    return SizedBox(
      width: double.infinity,
      height: 56,
      child: ElevatedButton(
        onPressed: _isProcessing ? null : _processCheckout,
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
        ),
        child: _isProcessing
            ? const CircularProgressIndicator(color: AppColors.surface)
            : Text(
                'Pay \$${totalAmount.toStringAsFixed(2)}',
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
      ),
    );
  }

  Widget _buildPlaceholder() {
    return Container(
      width: 80,
      height: 80,
      color: AppColors.border,
      child: const Icon(Icons.image, color: AppColors.textMuted),
    );
  }

  IconData _getCardIcon(String brand) {
    switch (brand.toLowerCase()) {
      case 'visa':
        return Icons.credit_card;
      case 'mastercard':
        return Icons.credit_card;
      case 'amex':
        return Icons.credit_card;
      default:
        return Icons.payment;
    }
  }
}
