import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../models/bid.dart';
import '../../services/api_service.dart';
import '../../config/api_config.dart';
import '../property/property_details_screen.dart';

class MyBidsScreen extends StatefulWidget {
  const MyBidsScreen({Key? key}) : super(key: key);

  @override
  State<MyBidsScreen> createState() => _MyBidsScreenState();
}

class _MyBidsScreenState extends State<MyBidsScreen> {
  final ApiService _apiService = ApiService();
  List<Bid> _bids = [];
  bool _isLoading = false;
  String _filter = 'all';

  @override
  void initState() {
    super.initState();
    _fetchMyBids();
  }

  Future<void> _fetchMyBids() async {
    setState(() => _isLoading = true);

    try {
      final response = await _apiService.get('${ApiConfig.bids}/my-bids');
      final List<dynamic> bidsJson = response['bids'] ?? response['data'] ?? [];
      setState(() {
        _bids = bidsJson.map((json) => Bid.fromJson(json)).toList();
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Failed to load bids: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      setState(() => _isLoading = false);
    }
  }

  List<Bid> get _filteredBids {
    if (_filter == 'all') return _bids;
    if (_filter == 'active') {
      return _bids.where((bid) => bid.status == 'active' || bid.status == 'winning').toList();
    }
    if (_filter == 'won') {
      return _bids.where((bid) => bid.status == 'won').toList();
    }
    if (_filter == 'lost') {
      return _bids.where((bid) => bid.status == 'lost' || bid.status == 'outbid').toList();
    }
    return _bids;
  }

  @override
  Widget build(BuildContext context) {
    final currencyFormat = NumberFormat.currency(symbol: '\$', decimalDigits: 0);

    return Scaffold(
      body: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  _buildFilterChip('All', 'all'),
                  const SizedBox(width: 8),
                  _buildFilterChip('Active', 'active'),
                  const SizedBox(width: 8),
                  _buildFilterChip('Won', 'won'),
                  const SizedBox(width: 8),
                  _buildFilterChip('Lost', 'lost'),
                ],
              ),
            ),
          ),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _filteredBids.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.gavel, size: 64, color: Colors.grey[400]),
                            const SizedBox(height: 16),
                            Text('No bids found',
                                style: TextStyle(fontSize: 18, color: Colors.grey[600])),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _fetchMyBids,
                        child: ListView.builder(
                          itemCount: _filteredBids.length,
                          itemBuilder: (context, index) {
                            final bid = _filteredBids[index];
                            return _buildBidCard(bid, currencyFormat);
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String label, String value) {
    final isSelected = _filter == value;
    return InkWell(
      onTap: () => setState(() => _filter = value),
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF2094F3) : Colors.grey[200],
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected ? Colors.white : Colors.grey[700],
            fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
          ),
        ),
      ),
    );
  }

  Widget _buildBidCard(Bid bid, NumberFormat currencyFormat) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: InkWell(
        onTap: () {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (_) => PropertyDetailsScreen(itemId: bid.itemId),
            ),
          );
        },
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: bid.itemImage != null
                    ? CachedNetworkImage(
                        imageUrl: bid.itemImage!,
                        width: 80,
                        height: 80,
                        fit: BoxFit.cover,
                        placeholder: (context, url) => Container(
                          width: 80,
                          height: 80,
                          color: Colors.grey[300],
                        ),
                        errorWidget: (context, url, error) => Container(
                          width: 80,
                          height: 80,
                          color: Colors.grey[300],
                          child: const Icon(Icons.home, color: Colors.grey),
                        ),
                      )
                    : Container(
                        width: 80,
                        height: 80,
                        color: Colors.grey[300],
                        child: const Icon(Icons.home, color: Colors.grey),
                      ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      bid.itemTitle ?? 'Property',
                      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Your bid: ${currencyFormat.format(bid.amount)}',
                      style: const TextStyle(
                        fontSize: 14,
                        color: Color(0xFF2094F3),
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      DateFormat('MMM dd, yyyy - hh:mm a').format(bid.timestamp),
                      style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: _getStatusColor(bid),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Text(
                  _getStatusText(bid),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Color _getStatusColor(Bid bid) {
    if (bid.isWon) return Colors.green;
    if (bid.isWinning) return Colors.blue;
    if (bid.isOutbid || bid.isLost) return Colors.red;
    return Colors.grey;
  }

  String _getStatusText(Bid bid) {
    if (bid.isWon) return 'WON';
    if (bid.isWinning) return 'WINNING';
    if (bid.isOutbid) return 'OUTBID';
    if (bid.isLost) return 'LOST';
    return bid.status.toUpperCase();
  }
}
