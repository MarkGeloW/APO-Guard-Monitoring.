import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// Branded loading / error / empty panels shared across guard screens.
class AsyncStatePanel extends StatelessWidget {
  const AsyncStatePanel.loading({super.key, this.message = 'Loading…'})
      : onRetry = null,
        _kind = _Kind.loading,
        title = null,
        icon = null;

  const AsyncStatePanel.error({
    super.key,
    required this.message,
    required this.onRetry,
  })  : _kind = _Kind.error,
        title = 'Something went wrong',
        icon = Icons.wifi_off_rounded;

  const AsyncStatePanel.empty({
    super.key,
    required this.title,
    required this.message,
    this.icon = Icons.notifications_none_rounded,
  })  : onRetry = null,
        _kind = _Kind.empty;

  final _Kind _kind;
  final String? title;
  final String message;
  final VoidCallback? onRetry;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    if (_kind == _Kind.loading) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                color: Colors.white,
                shape: BoxShape.circle,
                boxShadow: AppShadows.card,
              ),
              child: const Padding(
                padding: EdgeInsets.all(16),
                child: CircularProgressIndicator(strokeWidth: 3, color: AppColors.blue),
              ),
            ),
            const SizedBox(height: 16),
            Text(
              message,
              style: const TextStyle(
                fontWeight: FontWeight.w700,
                color: AppColors.muted,
              ),
            ),
          ],
        ),
      );
    }

    final isError = _kind == _Kind.error;

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Container(
          width: double.infinity,
          constraints: const BoxConstraints(maxWidth: 340),
          padding: const EdgeInsets.fromLTRB(22, 26, 22, 22),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(AppRadius.lg),
            border: Border.all(
              color: isError ? const Color(0xFFFECACA) : AppColors.border,
            ),
            boxShadow: AppShadows.card,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: isError ? const Color(0xFFFFEBEE) : AppColors.blueSoft,
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  icon ?? Icons.info_outline_rounded,
                  color: isError ? AppColors.red : AppColors.blue,
                  size: 28,
                ),
              ),
              const SizedBox(height: 14),
              Text(
                title ?? '',
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w800,
                  color: AppColors.text,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                message,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 13,
                  height: 1.45,
                  color: AppColors.muted,
                  fontWeight: FontWeight.w500,
                ),
              ),
              if (onRetry != null) ...[
                const SizedBox(height: 18),
                FilledButton.icon(
                  onPressed: onRetry,
                  icon: const Icon(Icons.refresh_rounded, size: 18),
                  label: const Text('Try Again'),
                  style: FilledButton.styleFrom(
                    backgroundColor: AppColors.blue,
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

enum _Kind { loading, error, empty }
