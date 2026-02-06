import 'dart:async';
import 'package:flutter/material.dart';

class CountdownTimer extends StatefulWidget {
  final DateTime endTime;
  final TextStyle? textStyle;
  final bool showIcon;

  const CountdownTimer({
    Key? key,
    required this.endTime,
    this.textStyle,
    this.showIcon = false,
  }) : super(key: key);

  @override
  State<CountdownTimer> createState() => _CountdownTimerState();
}

class _CountdownTimerState extends State<CountdownTimer> {
  Timer? _timer;
  Duration _remaining = Duration.zero;

  @override
  void initState() {
    super.initState();
    _updateRemaining();
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      _updateRemaining();
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  void _updateRemaining() {
    setState(() {
      _remaining = widget.endTime.difference(DateTime.now());
      if (_remaining.isNegative) {
        _remaining = Duration.zero;
        _timer?.cancel();
      }
    });
  }

  String _formatDuration(Duration duration) {
    if (duration.isNegative || duration == Duration.zero) {
      return 'Ended';
    }

    final days = duration.inDays;
    final hours = duration.inHours.remainder(24);
    final minutes = duration.inMinutes.remainder(60);
    final seconds = duration.inSeconds.remainder(60);

    if (days > 0) {
      return '${days}d ${hours}h ${minutes}m';
    } else if (hours > 0) {
      return '${hours}h ${minutes}m ${seconds}s';
    } else if (minutes > 0) {
      return '${minutes}m ${seconds}s';
    } else {
      return '${seconds}s';
    }
  }

  Color _getColor() {
    if (_remaining.isNegative || _remaining == Duration.zero) {
      return Colors.grey;
    } else if (_remaining.inHours < 1) {
      return Colors.red;
    } else if (_remaining.inHours < 24) {
      return Colors.orange;
    }
    return Colors.green;
  }

  @override
  Widget build(BuildContext context) {
    final color = _getColor();
    final text = _formatDuration(_remaining);

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        if (widget.showIcon)
          Icon(
            Icons.access_time,
            size: widget.textStyle?.fontSize ?? 14,
            color: color,
          ),
        if (widget.showIcon) const SizedBox(width: 4),
        Text(
          text,
          style: widget.textStyle?.copyWith(color: color) ??
              TextStyle(
                color: color,
                fontWeight: FontWeight.bold,
              ),
        ),
      ],
    );
  }
}
