import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/watchlist_provider.dart';
import '../../widgets/property_card.dart';
import '../property/property_details_screen.dart';

class FavoritesScreen extends StatefulWidget {
  const FavoritesScreen({Key? key}) : super(key: key);

  @override
  State<FavoritesScreen> createState() => _FavoritesScreenState();
}

class _FavoritesScreenState extends State<FavoritesScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<WatchlistProvider>(context, listen: false).fetchWatchlist();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<WatchlistProvider>(
      builder: (context, watchlistProvider, _) {
        if (watchlistProvider.isLoading && watchlistProvider.watchlist.isEmpty) {
          return const Center(child: CircularProgressIndicator());
        }

        if (watchlistProvider.watchlist.isEmpty) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  Icons.favorite_border,
                  size: 64,
                  color: Colors.grey[400],
                ),
                const SizedBox(height: 16),
                Text(
                  'No favorites yet',
                  style: TextStyle(
                    fontSize: 18,
                    color: Colors.grey[600],
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Add properties to your favorites',
                  style: TextStyle(
                    color: Colors.grey[500],
                  ),
                ),
              ],
            ),
          );
        }

        return RefreshIndicator(
          onRefresh: () => watchlistProvider.fetchWatchlist(),
          child: ListView.builder(
            itemCount: watchlistProvider.watchlist.length,
            itemBuilder: (context, index) {
              final item = watchlistProvider.watchlist[index];
              return PropertyCard(
                item: item,
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => PropertyDetailsScreen(itemId: item.id),
                    ),
                  );
                },
              );
            },
          ),
        );
      },
    );
  }
}
