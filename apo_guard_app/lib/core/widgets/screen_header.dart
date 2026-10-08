import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// Branded gradient header shared across guard screens.
class ScreenHeader extends StatelessWidget {
  const ScreenHeader({
    super.key,
    required this.eyebrow,
    required this.title,
    this.subtitle,
    this.trailing,
    this.bottom,
  });

  final String eyebrow;
  final String title;
  final String? subtitle;
  final Widget? trailing;
  final Widget? bottom;

  @override
  Widget build(BuildContext context) {
    final top = MediaQuery.paddingOf(context).top;

    return Container(
      width: double.infinity,
      padding: EdgeInsets.fromLTRB(20, top + 16, 20, bottom == null ? 24 : 18),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF0A3A9A), Color(0xFF1B5FE8), Color(0xFF2B78FF)],
        ),
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(AppRadius.xl)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            eyebrow.toUpperCase(),
            style: const TextStyle(
              color: Color(0xFFD7E6FF),
              fontSize: 11,
              fontWeight: FontWeight.w800,
              letterSpacing: 1.4,
            ),
          ),
          const SizedBox(height: 8),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 28,
                    fontWeight: FontWeight.w800,
                    height: 1.12,
                  ),
                ),
              ),
              if (trailing != null) ...[
                const SizedBox(width: 12),
                trailing!,
              ],
            ],
          ),
          if (subtitle != null) ...[
            const SizedBox(height: 8),
            Text(
              subtitle!,
              style: TextStyle(
                color: Colors.white.withValues(alpha: 0.82),
                fontSize: 14,
                fontWeight: FontWeight.w500,
                height: 1.35,
              ),
            ),
          ],
          if (bottom != null) ...[
            const SizedBox(height: 16),
            bottom!,
          ],
        ],
      ),
    );
  }
}
