import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_card.dart';
import '../../../core/widgets/async_state_panel.dart';
import '../../../core/widgets/screen_header.dart';
import '../../../data/services/api_client.dart';

class AlertsScreen extends StatefulWidget {
  const AlertsScreen({super.key, this.onDataLoaded});

  final VoidCallback? onDataLoaded;

  @override
  State<AlertsScreen> createState() => _AlertsScreenState();
}

enum _AlertFilter { all, critical, warning, updates, unread }

class _AlertsScreenState extends State<AlertsScreen> {
  bool _loading = true;
  bool _hasLoadedOnce = false;
  String? _error;
  List<Map<String, dynamic>> _alerts = [];
  _AlertFilter _filter = _AlertFilter.all;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final data = await ApiClient.instance.getAlerts();
      final alerts = ((data['alerts'] as List?) ?? [])
          .map((e) => (e as Map).cast<String, dynamic>())
          .toList();
      if (!mounted) return;
      setState(() {
        _alerts = alerts;
        _loading = false;
        _hasLoadedOnce = true;
      });
      widget.onDataLoaded?.call();
    } on ApiException catch (e) {
      if (!mounted) return;
      if (await ApiClient.instance.handleUnreachableOnBootstrap(
        e,
        hasCachedData: _hasLoadedOnce,
      )) {
        return;
      }
      setState(() {
        _error = e.message;
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      if (await ApiClient.instance.handleUnreachableOnBootstrap(
        ApiException('Unable to load alerts from server.'),
        hasCachedData: _hasLoadedOnce,
      )) {
        return;
      }
      setState(() {
        _error = 'Unable to load alerts from server.';
        _loading = false;
      });
    }
  }

  Future<void> _markAllRead() async {
    if (_unreadCount == 0) return;

    try {
      await ApiClient.instance.markAllAlertsRead();
      if (!mounted) return;
      setState(() {
        _alerts = _alerts.map((alert) {
          return {...alert, 'unread': false};
        }).toList();
      });
      widget.onDataLoaded?.call();
    } catch (_) {}
  }

  Future<void> _markRead(Map<String, dynamic> item) async {
    if (!_isUnread(item)) return;

    final id = ApiClient.parseIntOrNull(item['id']);
    if (id == null) return;

    try {
      await ApiClient.instance.markAlertRead(id);
      if (!mounted) return;
      setState(() {
        final index = _alerts.indexWhere((alert) => alert['id'] == id);
        if (index >= 0) {
          _alerts[index] = {..._alerts[index], 'unread': false};
        }
      });
      widget.onDataLoaded?.call();
    } catch (_) {}
  }

  String _tone(Map<String, dynamic> item) => item['tone']?.toString() ?? 'info';

  bool _isUnread(Map<String, dynamic> item) {
    if (item.containsKey('unread')) return item['unread'] == true;
    if (item.containsKey('read')) return item['read'] != true;
    return false;
  }

  int get _unreadCount => _alerts.where(_isUnread).length;

  List<Map<String, dynamic>> get _filtered {
    switch (_filter) {
      case _AlertFilter.critical:
        return _alerts.where((a) => _tone(a) == 'critical').toList();
      case _AlertFilter.warning:
        return _alerts.where((a) => _tone(a) == 'warning').toList();
      case _AlertFilter.updates:
        return _alerts.where((a) {
          final t = _tone(a);
          return t == 'success' || t == 'info';
        }).toList();
      case _AlertFilter.unread:
        return _alerts.where(_isUnread).toList();
      case _AlertFilter.all:
        return _alerts;
    }
  }

  Map<_SectionKey, List<Map<String, dynamic>>> get _grouped {
    final map = <_SectionKey, List<Map<String, dynamic>>>{};
    for (final alert in _filtered) {
      final key = _SectionKey.fromTone(_tone(alert));
      map.putIfAbsent(key, () => []).add(alert);
    }
    final keys = map.keys.toList()..sort((a, b) => a.order.compareTo(b.order));
    return {for (final key in keys) key: map[key]!};
  }

  @override
  Widget build(BuildContext context) {
    final grouped = _grouped;
    final criticalCount = _alerts.where((a) => _tone(a) == 'critical').length;
    final warningCount = _alerts.where((a) => _tone(a) == 'warning').length;

    return ColoredBox(
      color: AppColors.bg,
      child: RefreshIndicator(
        onRefresh: _load,
        color: AppColors.blue,
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: ScreenHeader(
                eyebrow: 'Notifications',
                title: 'Alerts',
                subtitle: _loading
                    ? 'Refreshing your inbox…'
                    : _alerts.isEmpty
                        ? 'Deployment and duty notices appear here'
                        : '${_alerts.length} total · organized by severity',
                trailing: (!_loading && _error == null && _unreadCount > 0)
                    ? TextButton(
                        onPressed: _markAllRead,
                        style: TextButton.styleFrom(
                          foregroundColor: Colors.white,
                          backgroundColor: Colors.white.withValues(alpha: 0.16),
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
                          minimumSize: Size.zero,
                          tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        ),
                        child: Text(
                          'Mark all read ($_unreadCount)',
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      )
                    : null,
                bottom: (!_loading && _error == null && _alerts.isNotEmpty)
                    ? SingleChildScrollView(
                        scrollDirection: Axis.horizontal,
                        child: Row(
                          children: [
                            _FilterChip(
                              label: 'All',
                              selected: _filter == _AlertFilter.all,
                              onTap: () => setState(() => _filter = _AlertFilter.all),
                            ),
                            _FilterChip(
                              label: 'Critical',
                              selected: _filter == _AlertFilter.critical,
                              onTap: () => setState(() => _filter = _AlertFilter.critical),
                            ),
                            _FilterChip(
                              label: 'Warnings',
                              selected: _filter == _AlertFilter.warning,
                              onTap: () => setState(() => _filter = _AlertFilter.warning),
                            ),
                            _FilterChip(
                              label: 'Updates',
                              selected: _filter == _AlertFilter.updates,
                              onTap: () => setState(() => _filter = _AlertFilter.updates),
                            ),
                            _FilterChip(
                              label: 'Unread',
                              selected: _filter == _AlertFilter.unread,
                              onTap: () => setState(() => _filter = _AlertFilter.unread),
                            ),
                          ],
                        ),
                      )
                    : null,
              ),
            ),
            if (_loading)
              const SliverFillRemaining(child: AsyncStatePanel.loading())
            else if (_error != null)
              SliverFillRemaining(
                child: AsyncStatePanel.error(message: _error!, onRetry: _load),
              )
            else if (_alerts.isEmpty)
              const SliverFillRemaining(
                child: AsyncStatePanel.empty(
                  title: 'All clear',
                  message:
                      'No alerts right now. New deployments, check-in reminders, and system notices will show up here.',
                  icon: Icons.notifications_none_rounded,
                ),
              )
            else if (_filtered.isEmpty)
              const SliverFillRemaining(
                child: AsyncStatePanel.empty(
                  title: 'Nothing here',
                  message: 'No alerts match this filter. Try All or another category.',
                  icon: Icons.filter_alt_off_rounded,
                ),
              )
            else
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
                sliver: SliverList(
                  delegate: SliverChildListDelegate([
                    if (_filter == _AlertFilter.all) ...[
                      Row(
                        children: [
                          Expanded(
                            child: _SummaryStat(
                              label: 'Unread',
                              value: '$_unreadCount',
                              color: AppColors.blue,
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: _SummaryStat(
                              label: 'Critical',
                              value: '$criticalCount',
                              color: AppColors.red,
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: _SummaryStat(
                              label: 'Warnings',
                              value: '$warningCount',
                              color: AppColors.amber,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                    ],
                    ...grouped.entries.expand((entry) sync* {
                      yield _SectionHeader(
                        title: entry.key.title,
                        count: entry.value.length,
                        color: entry.key.color,
                      );
                      for (var i = 0; i < entry.value.length; i++) {
                        final item = entry.value[i];
                        yield Padding(
                          padding: EdgeInsets.only(bottom: i == entry.value.length - 1 ? 18 : 10),
                          child: _AlertCard(
                            tone: _tone(item),
                            title: item['title']?.toString(),
                            message: item['message']?.toString() ?? '',
                            time: item['time']?.toString() ?? '',
                            unread: _isUnread(item),
                            onTap: () => _markRead(item),
                          ),
                        );
                      }
                    }),
                  ]),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _SectionKey {
  const _SectionKey({
    required this.title,
    required this.order,
    required this.color,
  });

  final String title;
  final int order;
  final Color color;

  factory _SectionKey.fromTone(String tone) {
    switch (tone) {
      case 'critical':
        return const _SectionKey(title: 'Needs Attention', order: 0, color: AppColors.red);
      case 'warning':
        return const _SectionKey(title: 'Warnings', order: 1, color: Color(0xFFF59E0B));
      case 'success':
        return const _SectionKey(title: 'Updates', order: 2, color: AppColors.green);
      default:
        return const _SectionKey(title: 'Notices', order: 3, color: AppColors.blue);
    }
  }

  @override
  bool operator ==(Object other) =>
      other is _SectionKey && other.title == title && other.order == order;

  @override
  int get hashCode => Object.hash(title, order);
}

class _SummaryStat extends StatelessWidget {
  const _SummaryStat({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label.toUpperCase(),
            style: const TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w800,
              letterSpacing: 0.8,
              color: AppColors.muted,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            value,
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.w800,
              color: color,
              height: 1,
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({
    required this.title,
    required this.count,
    required this.color,
  });

  final String title;
  final int count;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10, top: 4),
      child: Row(
        children: [
          Container(
            width: 8,
            height: 8,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
          ),
          const SizedBox(width: 8),
          Text(
            title.toUpperCase(),
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w800,
              letterSpacing: 1.1,
              color: AppColors.text,
            ),
          ),
          const SizedBox(width: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
            decoration: BoxDecoration(
              color: const Color(0xFFE8EEF7),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              '$count',
              style: const TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w800,
                color: AppColors.muted,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  const _FilterChip({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: Material(
        color: selected ? Colors.white : Colors.white.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(20),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(20),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w800,
                color: selected ? AppColors.blue : Colors.white,
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _AlertCard extends StatelessWidget {
  const _AlertCard({
    required this.tone,
    required this.message,
    required this.time,
    required this.unread,
    this.title,
    this.onTap,
  });

  final String tone;
  final String? title;
  final String message;
  final String time;
  final bool unread;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final style = _toneStyle(tone);

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppRadius.md),
        child: Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: style.border),
        boxShadow: AppShadows.card,
      ),
      child: IntrinsicHeight(
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Container(
              width: 5,
              decoration: BoxDecoration(
                color: style.accent,
                borderRadius: const BorderRadius.horizontal(left: Radius.circular(16)),
              ),
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(12, 14, 14, 14),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: 34,
                      height: 34,
                      decoration: BoxDecoration(
                        color: style.accent.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Icon(style.icon, color: style.accent, size: 18),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: style.accent.withValues(alpha: 0.12),
                                  borderRadius: BorderRadius.circular(20),
                                ),
                                child: Text(
                                  style.label,
                                  style: TextStyle(
                                    fontSize: 10,
                                    fontWeight: FontWeight.w800,
                                    letterSpacing: 0.6,
                                    color: style.accent,
                                  ),
                                ),
                              ),
                              if (unread) ...[
                                const SizedBox(width: 6),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                  decoration: BoxDecoration(
                                    color: AppColors.text,
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: const Text(
                                    'NEW',
                                    style: TextStyle(
                                      fontSize: 9,
                                      fontWeight: FontWeight.w800,
                                      color: Colors.white,
                                      letterSpacing: 0.5,
                                    ),
                                  ),
                                ),
                              ],
                              const Spacer(),
                              Text(
                                time,
                                style: const TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w600,
                                  color: AppColors.muted,
                                ),
                              ),
                            ],
                          ),
                          if (title != null && title!.trim().isNotEmpty) ...[
                            const SizedBox(height: 8),
                            Text(
                              title!,
                              style: const TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.w800,
                                color: AppColors.text,
                              ),
                            ),
                          ],
                          SizedBox(height: title != null && title!.trim().isNotEmpty ? 4 : 8),
                          Text(
                            message,
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                              color: AppColors.text,
                              height: 1.35,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
        ),
      ),
    );
  }

  static _ToneStyle _toneStyle(String tone) {
    switch (tone) {
      case 'critical':
        return const _ToneStyle(
          label: 'CRITICAL',
          border: Color(0xFFFECACA),
          accent: AppColors.red,
          icon: Icons.close_rounded,
        );
      case 'warning':
        return const _ToneStyle(
          label: 'WARNING',
          border: Color(0xFFFDE68A),
          accent: Color(0xFFF59E0B),
          icon: Icons.priority_high_rounded,
        );
      case 'success':
        return const _ToneStyle(
          label: 'UPDATE',
          border: AppColors.greenBorder,
          accent: AppColors.green,
          icon: Icons.check_rounded,
        );
      default:
        return const _ToneStyle(
          label: 'INFO',
          border: AppColors.blueBorder,
          accent: AppColors.blue,
          icon: Icons.sensors_rounded,
        );
    }
  }
}

class _ToneStyle {
  const _ToneStyle({
    required this.label,
    required this.border,
    required this.accent,
    required this.icon,
  });

  final String label;
  final Color border;
  final Color accent;
  final IconData icon;
}
