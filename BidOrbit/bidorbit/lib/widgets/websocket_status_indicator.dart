import 'package:flutter/material.dart';
import '../services/websocket_service.dart';
import '../theme/app_theme.dart';

class WebSocketStatusIndicator extends StatelessWidget {
  const WebSocketStatusIndicator({super.key});

  @override
  Widget build(BuildContext context) {
    final wsService = WebSocketService();

    return StreamBuilder<WebSocketStatus>(
      stream: wsService.statusStream,
      initialData: wsService.status,
      builder: (context, snapshot) {
        final status = snapshot.data ?? WebSocketStatus.disconnected;

        // Don't show anything if connected (normal state)
        if (status == WebSocketStatus.connected) {
          return const SizedBox.shrink();
        }

        Color color;
        IconData icon;
        String message;

        switch (status) {
          case WebSocketStatus.connecting:
            color = AppColors.warning;
            icon = Icons.sync;
            message = 'Connecting...';
            break;
          case WebSocketStatus.disconnected:
            color = AppColors.textMuted;
            icon = Icons.cloud_off;
            message = 'Offline';
            break;
          case WebSocketStatus.error:
            color = AppColors.error;
            icon = Icons.error_outline;
            message = 'Connection Error';
            break;
          default:
            return const SizedBox.shrink();
        }

        return Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: color.withValues(alpha: 0.3)),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, size: 14, color: color),
              const SizedBox(width: 6),
              Text(
                message,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: color,
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

class WebSocketStatusBanner extends StatelessWidget {
  const WebSocketStatusBanner({super.key});

  @override
  Widget build(BuildContext context) {
    final wsService = WebSocketService();

    return StreamBuilder<WebSocketStatus>(
      stream: wsService.statusStream,
      initialData: wsService.status,
      builder: (context, snapshot) {
        final status = snapshot.data ?? WebSocketStatus.disconnected;

        // Only show banner for error or disconnected states
        if (status == WebSocketStatus.connected || 
            status == WebSocketStatus.connecting) {
          return const SizedBox.shrink();
        }

        return Material(
          color: status == WebSocketStatus.error 
              ? AppColors.errorLight 
              : Colors.grey[200],
          child: Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: Row(
              children: [
                Icon(
                  status == WebSocketStatus.error 
                      ? Icons.error_outline 
                      : Icons.cloud_off,
                  size: 20,
                  color: status == WebSocketStatus.error 
                      ? AppColors.error 
                      : AppColors.textSecondary,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    status == WebSocketStatus.error
                        ? 'Connection lost. Real-time updates unavailable.'
                        : 'You\'re offline. Some features may be limited.',
                    style: TextStyle(
                      fontSize: 13,
                      color: status == WebSocketStatus.error 
                          ? AppColors.error 
                          : AppColors.textPrimary,
                    ),
                  ),
                ),
                TextButton(
                  onPressed: () {
                    wsService.connect();
                  },
                  child: Text(
                    'Retry',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: status == WebSocketStatus.error 
                          ? AppColors.error 
                          : AppColors.textSecondary,
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
