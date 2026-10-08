import 'package:flutter/material.dart';

class DutyStatusStyle {
  const DutyStatusStyle({
    required this.label,
    required this.dot,
    required this.background,
    required this.foreground,
    required this.subtle,
  });

  final String label;
  final Color dot;
  final Color background;
  final Color foreground;
  final Color subtle;

  static DutyStatusStyle fromApi(String? status, {String? statusLabel}) {
    switch (status) {
      case 'on_duty':
        return DutyStatusStyle(
          label: statusLabel ?? 'On Duty',
          dot: const Color(0xFF4ADE80),
          background: const Color(0xFF14532D).withValues(alpha: 0.35),
          foreground: Colors.white,
          subtle: const Color(0xFFD7E6FF),
        );
      case 'late':
        return DutyStatusStyle(
          label: statusLabel ?? 'Late',
          dot: const Color(0xFFFBBF24),
          background: const Color(0xFF78350F).withValues(alpha: 0.4),
          foreground: Colors.white,
          subtle: const Color(0xFFFFE7C2),
        );
      case 'pending':
        return DutyStatusStyle(
          label: statusLabel ?? 'Pending Check-In',
          dot: const Color(0xFF93C5FD),
          background: Colors.white.withValues(alpha: 0.18),
          foreground: Colors.white,
          subtle: const Color(0xFFD7E6FF),
        );
      case 'between_shifts':
        return DutyStatusStyle(
          label: statusLabel ?? 'Between Shifts',
          dot: const Color(0xFFFB923C),
          background: const Color(0xFF7C2D12).withValues(alpha: 0.38),
          foreground: Colors.white,
          subtle: const Color(0xFFFFE7C2),
        );
      default:
        return DutyStatusStyle(
          label: statusLabel ?? 'Off Duty',
          dot: const Color(0xFF94A3B8),
          background: Colors.white.withValues(alpha: 0.14),
          foreground: Colors.white,
          subtle: const Color(0xFFD7E6FF),
        );
    }
  }
}

class StatusPill extends StatelessWidget {
  const StatusPill({
    super.key,
    required this.style,
    this.since,
  });

  final DutyStatusStyle style;
  final String? since;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: style.background,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: Colors.white.withValues(alpha: 0.12)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.circle, size: 8, color: style.dot),
          const SizedBox(width: 8),
          Flexible(
            child: Text.rich(
              TextSpan(
                children: [
                  TextSpan(
                    text: style.label,
                    style: TextStyle(
                      color: style.foreground,
                      fontWeight: FontWeight.w800,
                      fontSize: 13,
                    ),
                  ),
                  if (since != null)
                    TextSpan(
                      text: '  since $since',
                      style: TextStyle(
                        color: style.subtle,
                        fontWeight: FontWeight.w500,
                        fontSize: 12,
                      ),
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
