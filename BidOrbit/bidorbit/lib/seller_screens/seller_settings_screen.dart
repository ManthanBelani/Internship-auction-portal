import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../theme/app_theme.dart';
import '../providers/auth_provider.dart';

class SellerSettingsScreen extends StatelessWidget {
  const SellerSettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Settings', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: AppColors.surface,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildSection(
              'ACCOUNT',
              [
                _buildSettingTile(
                  context,
                  'Business Profile',
                  Icons.business,
                  () {},
                ),
                _buildSettingTile(
                  context,
                  'Bank Account',
                  Icons.account_balance,
                  () {},
                ),
                _buildSettingTile(
                  context,
                  'Verification',
                  Icons.verified_user,
                  () {},
                ),
              ],
            ),
            const SizedBox(height: 24),
            _buildSection(
              'PREFERENCES',
              [
                _buildSettingTile(
                  context,
                  'Notifications',
                  Icons.notifications_outlined,
                  () {},
                ),
                _buildSettingTile(
                  context,
                  'Payout Settings',
                  Icons.payment,
                  () {},
                ),
                _buildSettingTile(
                  context,
                  'Shipping Preferences',
                  Icons.local_shipping_outlined,
                  () {},
                ),
              ],
            ),
            const SizedBox(height: 24),
            _buildSection(
              'SUPPORT',
              [
                _buildSettingTile(
                  context,
                  'Help Center',
                  Icons.help_outline,
                  () {},
                ),
                _buildSettingTile(
                  context,
                  'Contact Support',
                  Icons.support_agent,
                  () {},
                ),
                _buildSettingTile(
                  context,
                  'Seller Guidelines',
                  Icons.description_outlined,
                  () {},
                ),
              ],
            ),
            const SizedBox(height: 24),
            _buildSection(
              'LEGAL',
              [
                _buildSettingTile(
                  context,
                  'Terms of Service',
                  Icons.article_outlined,
                  () {},
                ),
                _buildSettingTile(
                  context,
                  'Privacy Policy',
                  Icons.privacy_tip_outlined,
                  () {},
                ),
              ],
            ),
            const SizedBox(height: 24),
            _buildLogoutButton(context),
          ],
        ),
      ),
    );
  }

  Widget _buildSection(String title, List<Widget> children) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 4, bottom: 12),
          child: Text(
            title,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: AppColors.textMuted,
              letterSpacing: 1,
            ),
          ),
        ),
        Container(
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(AppRadius.lg),
          ),
          child: Column(children: children),
        ),
      ],
    );
  }

  Widget _buildSettingTile(
    BuildContext context,
    String title,
    IconData icon,
    VoidCallback onTap,
  ) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: AppColors.surfaceVariant,
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
                child: Icon(icon, color: AppColors.textSecondary, size: 22),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w500,
                    color: AppColors.textPrimary,
                  ),
                ),
              ),
              const Icon(
                Icons.chevron_right,
                color: AppColors.textMuted,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildLogoutButton(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      child: ElevatedButton(
        onPressed: () async {
          final shouldLogout = await showDialog<bool>(
            context: context,
            builder: (context) => AlertDialog(
              title: const Text('Logout'),
              content: const Text('Are you sure you want to logout?'),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context, false),
                  child: const Text('Cancel'),
                ),
                TextButton(
                  onPressed: () => Navigator.pop(context, true),
                  child: const Text(
                    'Logout',
                    style: TextStyle(color: AppColors.error),
                  ),
                ),
              ],
            ),
          );

          if (shouldLogout == true && context.mounted) {
            await context.read<AuthProvider>().logout();
            if (context.mounted) {
              Navigator.of(context).pushNamedAndRemoveUntil(
                '/login',
                (route) => false,
              );
            }
          }
        },
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.errorLight,
          foregroundColor: AppColors.error,
          elevation: 0,
          padding: const EdgeInsets.symmetric(vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppRadius.md),
          ),
        ),
        child: const Text(
          'Logout',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: AppColors.error,
          ),
        ),
      ),
    );
  }
}
