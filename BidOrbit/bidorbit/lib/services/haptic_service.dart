import 'package:flutter/services.dart';

/// Haptic feedback service for better touch interactions
class HapticService {
  /// Light impact - for subtle feedback (e.g., toggles, switches)
  static Future<void> light() async {
    await HapticFeedback.lightImpact();
  }

  /// Medium impact - for button presses, card taps
  static Future<void> medium() async {
    await HapticFeedback.mediumImpact();
  }

  /// Heavy impact - for important actions
  static Future<void> heavy() async {
    await HapticFeedback.heavyImpact();
  }

  /// Selection click - for selecting items
  static Future<void> selectionClick() async {
    await HapticFeedback.selectionClick();
  }

  /// Vibrate - for errors or warnings
  static Future<void> vibrate() async {
    await HapticFeedback.vibrate();
  }

  /// Success pattern - light + medium
  static Future<void> success() async {
    await HapticFeedback.lightImpact();
    await Future.delayed(const Duration(milliseconds: 50));
    await HapticFeedback.mediumImpact();
  }

  /// Error pattern - double vibrate
  static Future<void> error() async {
    await HapticFeedback.vibrate();
    await Future.delayed(const Duration(milliseconds: 100));
    await HapticFeedback.vibrate();
  }

  /// Bid placed feedback
  static Future<void> bidPlaced() async {
    await HapticFeedback.mediumImpact();
    await Future.delayed(const Duration(milliseconds: 100));
    await HapticFeedback.selectionClick();
  }

  /// Button tap feedback
  static Future<void> buttonTap() async {
    await HapticFeedback.lightImpact();
  }

  /// Card tap feedback
  static Future<void> cardTap() async {
    await HapticFeedback.selectionClick();
  }

  /// Toggle feedback
  static Future<void> toggle() async {
    await HapticFeedback.lightImpact();
  }

  /// Navigation feedback
  static Future<void> navigation() async {
    await HapticFeedback.selectionClick();
  }

  /// Delete/warning feedback
  static Future<void> warning() async {
    await HapticFeedback.heavyImpact();
  }
}
