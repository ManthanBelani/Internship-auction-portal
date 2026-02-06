import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/items_provider.dart';
import '../../widgets/property_card.dart';
import '../property/property_details_screen.dart';

class PropertyListTab extends StatefulWidget {
  const PropertyListTab({Key? key}) : super(key: key);

  @override
  State<PropertyListTab> createState() => _PropertyListTabState();
}

class _PropertyListTabState extends State<PropertyListTab> {
  final _scrollController = ScrollController();
  final _searchController = TextEditingController();
  String? _sortBy;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent * 0.9) {
      final itemsProvider = Provider.of<ItemsProvider>(context, listen: false);
      if (!itemsProvider.isLoading && itemsProvider.hasMore) {
        itemsProvider.fetchItems();
      }
    }
  }

  void _showFilterDialog() {
    showModalBottomSheet(
      context: context,
      builder: (context) => _FilterSheet(
        currentSort: _sortBy,
        onApply: (sortBy) {
          setState(() => _sortBy = sortBy);
          Provider.of<ItemsProvider>(context, listen: false).setSortBy(sortBy);
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        // Search bar
        Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    hintText: 'Search properties...',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: _searchController.text.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () {
                              _searchController.clear();
                              Provider.of<ItemsProvider>(context, listen: false)
                                  .setSearchQuery(null);
                            },
                          )
                        : null,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 12,
                    ),
                  ),
                  onSubmitted: (value) {
                    Provider.of<ItemsProvider>(context, listen: false)
                        .setSearchQuery(value.isEmpty ? null : value);
                  },
                ),
              ),
              const SizedBox(width: 8),
              IconButton(
                icon: const Icon(Icons.filter_list),
                onPressed: _showFilterDialog,
                style: IconButton.styleFrom(
                  backgroundColor: const Color(0xFF2094F3),
                  foregroundColor: Colors.white,
                ),
              ),
            ],
          ),
        ),
        // Property list
        Expanded(
          child: Consumer<ItemsProvider>(
            builder: (context, itemsProvider, _) {
              if (itemsProvider.items.isEmpty && itemsProvider.isLoading) {
                return const Center(child: CircularProgressIndicator());
              }

              if (itemsProvider.items.isEmpty && !itemsProvider.isLoading) {
                return Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.search_off,
                        size: 64,
                        color: Colors.grey[400],
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'No properties found',
                        style: TextStyle(
                          fontSize: 18,
                          color: Colors.grey[600],
                        ),
                      ),
                      const SizedBox(height: 8),
                      TextButton(
                        onPressed: () {
                          _searchController.clear();
                          itemsProvider.clearFilters();
                        },
                        child: const Text('Clear filters'),
                      ),
                    ],
                  ),
                );
              }

              return RefreshIndicator(
                onRefresh: () => itemsProvider.fetchItems(refresh: true),
                child: ListView.builder(
                  controller: _scrollController,
                  itemCount: itemsProvider.items.length +
                      (itemsProvider.hasMore ? 1 : 0),
                  itemBuilder: (context, index) {
                    if (index >= itemsProvider.items.length) {
                      return const Padding(
                        padding: EdgeInsets.all(16),
                        child: Center(child: CircularProgressIndicator()),
                      );
                    }

                    final item = itemsProvider.items[index];
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
          ),
        ),
      ],
    );
  }
}

class _FilterSheet extends StatefulWidget {
  final String? currentSort;
  final Function(String?) onApply;

  const _FilterSheet({
    required this.currentSort,
    required this.onApply,
  });

  @override
  State<_FilterSheet> createState() => _FilterSheetState();
}

class _FilterSheetState extends State<_FilterSheet> {
  String? _selectedSort;

  @override
  void initState() {
    super.initState();
    _selectedSort = widget.currentSort;
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Sort & Filter',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              IconButton(
                icon: const Icon(Icons.close),
                onPressed: () => Navigator.pop(context),
              ),
            ],
          ),
          const SizedBox(height: 24),
          Text(
            'Sort By',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
          const SizedBox(height: 12),
          RadioListTile<String>(
            title: const Text('Ending Soon'),
            value: 'ending_soon',
            groupValue: _selectedSort,
            onChanged: (value) => setState(() => _selectedSort = value),
          ),
          RadioListTile<String>(
            title: const Text('Newest First'),
            value: 'newest',
            groupValue: _selectedSort,
            onChanged: (value) => setState(() => _selectedSort = value),
          ),
          RadioListTile<String>(
            title: const Text('Price: Low to High'),
            value: 'price_asc',
            groupValue: _selectedSort,
            onChanged: (value) => setState(() => _selectedSort = value),
          ),
          RadioListTile<String>(
            title: const Text('Price: High to Low'),
            value: 'price_desc',
            groupValue: _selectedSort,
            onChanged: (value) => setState(() => _selectedSort = value),
          ),
          const SizedBox(height: 24),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () {
                    setState(() => _selectedSort = null);
                  },
                  child: const Text('Clear'),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: ElevatedButton(
                  onPressed: () {
                    widget.onApply(_selectedSort);
                    Navigator.pop(context);
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF2094F3),
                    foregroundColor: Colors.white,
                  ),
                  child: const Text('Apply'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
