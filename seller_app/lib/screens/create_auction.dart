import 'dart:io';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import '../widgets/glass_card.dart';
import '../providers/seller_provider.dart';
import '../providers/auth_provider.dart';

class CreateAuctionScreen extends StatefulWidget {
  const CreateAuctionScreen({super.key});

  @override
  State<CreateAuctionScreen> createState() => _CreateAuctionScreenState();
}

class _CreateAuctionScreenState extends State<CreateAuctionScreen> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _priceController = TextEditingController();
  File? _image;
  final _picker = ImagePicker();
  String _category = 'Watches';
  DateTime _endTime = DateTime.now().add(const Duration(days: 7));
  bool _isPickingImage = false;

  Future<void> _pickImage() async {
    if (_isPickingImage) return;
    setState(() => _isPickingImage = true);

    try {
      final pickedFile = await _picker.pickImage(source: ImageSource.gallery);
      if (pickedFile != null) {
        setState(() {
          _image = File(pickedFile.path);
        });
      }
    } catch (e) {
      print('Pick image error: $e');
    } finally {
      if (mounted) setState(() => _isPickingImage = false);
    }
  }

  void _handlePublish() async {
    if (_formKey.currentState!.validate()) {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final sellerProvider = Provider.of<SellerProvider>(
        context,
        listen: false,
      );

      final Map<String, String> fields = {
        'title': _titleController.text.trim(),
        'description': _descriptionController.text.trim(),
        'startingPrice': _priceController.text,
        'endTime': _endTime.toIso8601String(),
        'sellerId': auth.user?.id.toString() ?? '',
        'category': _category,
      };

      bool success;
      if (_image != null) {
        success = await sellerProvider.createAuctionWithImage(
          fields: fields,
          imagePath: _image!.path,
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Please select an image first.')),
        );
        return;
      }

      if (success) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Auction published successfully!')),
          );
          Navigator.pop(context);
        }
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                sellerProvider.error ?? 'Failed to publish auction.',
              ),
              backgroundColor: Colors.redAccent,
            ),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0A0C10),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: GestureDetector(
          onTap: () => Navigator.pop(context),
          child: Container(
            margin: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.05),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.arrow_back_ios_new,
              color: Colors.white,
              size: 18,
            ),
          ),
        ),
        title: Text(
          'Create New Auction',
          style: GoogleFonts.inter(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        centerTitle: true,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildSectionTitle('Item Details'),
              const SizedBox(height: 16),
              GlassCard(
                child: Column(
                  children: [
                    _buildTextField(
                      controller: _titleController,
                      label: 'Item Title',
                      hint: 'e.g. Vintage Rolex Submariner',
                      validator: (v) => v!.isEmpty ? 'Title is required' : null,
                    ),
                    const SizedBox(height: 16),
                    _buildTextField(
                      controller: _descriptionController,
                      label: 'Description',
                      hint: 'Describe the condition, provenance, etc...',
                      maxLines: 4,
                      validator: (v) =>
                          v!.isEmpty ? 'Description is required' : null,
                    ),
                    const SizedBox(height: 16),
                    _buildDropdown(),
                  ],
                ),
              ),
              const SizedBox(height: 32),
              _buildSectionTitle('Pricing & Duration'),
              const SizedBox(height: 16),
              GlassCard(
                child: Column(
                  children: [
                    _buildTextField(
                      controller: _priceController,
                      label: 'Starting Price (\$)',
                      hint: '0.00',
                      keyboardType: TextInputType.number,
                      validator: (v) => v!.isEmpty ? 'Price is required' : null,
                    ),
                    const SizedBox(height: 16),
                    _buildDatePicker(),
                  ],
                ),
              ),
              const SizedBox(height: 32),
              _buildSectionTitle('Media'),
              const SizedBox(height: 16),
              GestureDetector(
                onTap: _pickImage,
                child: GlassCard(
                  child: Container(
                    width: double.infinity,
                    height: 180,
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.05),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                        color: Colors.white.withOpacity(0.1),
                        style: BorderStyle.solid,
                      ),
                    ),
                    child: _image != null
                        ? ClipRRect(
                            borderRadius: BorderRadius.circular(12),
                            child: Image.file(_image!, fit: BoxFit.cover),
                          )
                        : Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                Icons.add_photo_alternate,
                                size: 48,
                                color: const Color(0xFF2977F5).withOpacity(0.5),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                'Tap to upload item image',
                                style: GoogleFonts.inter(color: Colors.white38),
                              ),
                              Text(
                                'PNG, JPG or JPEG',
                                style: GoogleFonts.inter(
                                  color: Colors.white24,
                                  fontSize: 10,
                                ),
                              ),
                            ],
                          ),
                  ),
                ),
              ),
              const SizedBox(height: 48),
              Consumer<SellerProvider>(
                builder: (context, seller, _) {
                  return SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: seller.isLoading ? null : _handlePublish,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF2977F5),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 20),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                      ),
                      child: seller.isLoading
                          ? const CircularProgressIndicator(color: Colors.white)
                          : Text(
                              'Publish Auction',
                              style: GoogleFonts.inter(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                    ),
                  );
                },
              ),
              const SizedBox(height: 48),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Text(
      title,
      style: GoogleFonts.inter(
        fontSize: 16,
        fontWeight: FontWeight.bold,
        color: Colors.white,
      ),
    );
  }

  Widget _buildTextField({
    required String label,
    required String hint,
    TextEditingController? controller,
    int maxLines = 1,
    TextInputType? keyboardType,
    String? Function(String?)? validator,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.inter(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: Colors.white54,
          ),
        ),
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          maxLines: maxLines,
          keyboardType: keyboardType,
          validator: validator,
          style: GoogleFonts.inter(color: Colors.white),
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: GoogleFonts.inter(color: Colors.white24),
            filled: true,
            fillColor: Colors.white.withOpacity(0.05),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide.none,
            ),
            contentPadding: const EdgeInsets.all(16),
          ),
        ),
      ],
    );
  }

  Widget _buildDropdown() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Category',
          style: GoogleFonts.inter(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: Colors.white54,
          ),
        ),
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          decoration: BoxDecoration(
            color: Colors.white.withOpacity(0.05),
            borderRadius: BorderRadius.circular(12),
          ),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<String>(
              value: _category,
              dropdownColor: const Color(0xFF161B22),
              isExpanded: true,
              style: GoogleFonts.inter(color: Colors.white),
              items: ['Watches', 'Art', 'Collectibles', 'Cars', 'Fashion'].map((
                String value,
              ) {
                return DropdownMenuItem<String>(
                  value: value,
                  child: Text(value),
                );
              }).toList(),
              onChanged: (newValue) => setState(() => _category = newValue!),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildDatePicker() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'End Date',
          style: GoogleFonts.inter(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: Colors.white54,
          ),
        ),
        const SizedBox(height: 8),
        GestureDetector(
          onTap: () async {
            final picked = await showDatePicker(
              context: context,
              firstDate: DateTime.now(),
              lastDate: DateTime.now().add(const Duration(days: 365)),
              initialDate: _endTime,
            );
            if (picked != null) setState(() => _endTime = picked);
          },
          child: Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.05),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  '${_endTime.day}/${_endTime.month}/${_endTime.year}',
                  style: GoogleFonts.inter(color: Colors.white),
                ),
                const Icon(
                  Icons.calendar_today,
                  color: Colors.white54,
                  size: 18,
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
