import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'theme/app_theme.dart';

class ThemeConfig {
  static const Color primaryBlue = AppColors.primary;
  static const Color accentGreen = AppColors.success;
  static const Color backgroundDark = Color(0xFF0A0C10);
  static const Color cardDark = Color(0xFF1C1F26);
  static const Color textGray = AppColors.textMuted;

  static ThemeData get darkTheme {
    return ThemeData(
      brightness: Brightness.dark,
      primaryColor: AppColors.primary,
      scaffoldBackgroundColor: backgroundDark,
      cardColor: cardDark,
      useMaterial3: true,
      textTheme: GoogleFonts.outfitTextTheme(ThemeData.dark().textTheme),
      appBarTheme: AppBarTheme(
        backgroundColor: backgroundDark.withValues(alpha: 0.8),
        elevation: 0,
        centerTitle: false,
        titleTextStyle: GoogleFonts.outfit(
          fontSize: 24,
          fontWeight: FontWeight.bold,
          color: AppColors.surface,
        ),
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: Colors.transparent,
        selectedItemColor: AppColors.primary,
        unselectedItemColor: AppColors.textMuted,
        elevation: 0,
      ),
    );
  }
}
