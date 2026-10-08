import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/utils/app_datetime.dart';
import '../../../core/widgets/app_card.dart';
import '../../../core/widgets/async_state_panel.dart';
import '../../../core/widgets/screen_header.dart';
import '../../../core/widgets/status_pill.dart';
import '../../../data/services/api_client.dart';
import '../../../data/services/duty_watch_service.dart';
import '../../../data/services/session_store.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({
    super.key,
    this.onSignOut,
    this.onOpenAttendance,
  });

  final Future<void> Function()? onSignOut;
  final VoidCallback? onOpenAttendance;

  @override
  State<DashboardScreen> createState() => DashboardScreenState();
}

class DashboardScreenState extends State<DashboardScreen> {
  bool _loading = true;
  bool _refreshing = false;
  bool _offline = false;
  String? _error;
  Map<String, dynamic>? _data;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load({bool silent = false}) async {
    if (_data == null) {
      final cached = await _readDashboardCache();
      if (cached != null && mounted) {
        setState(() {
          _data = cached;
          _offline = true;
          _loading = false;
        });
      }
    }

    final hasCachedData = _data != null;

    if (!silent || !hasCachedData) {
      setState(() {
        _loading = !hasCachedData;
        _error = hasCachedData ? _error : null;
      });
    } else {
      setState(() => _refreshing = true);
    }

    try {
      final data = await ApiClient.instance.getDashboard();
      await SessionStore.instance.writeString(SessionStore.dashboardCacheKey, jsonEncode(data));
      if (!mounted) return;
      setState(() {
        _data = data;
        _offline = false;
        _loading = false;
        _refreshing = false;
        _error = null;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      if (await ApiClient.instance.handleUnreachableOnBootstrap(
        e,
        hasCachedData: hasCachedData,
      )) {
        return;
      }
      setState(() {
        _loading = false;
        _refreshing = false;
        _offline = hasCachedData && ApiClient.isConnectionError(e);
        if (!hasCachedData) {
          _error = e.message;
        }
      });
    } catch (_) {
      if (!mounted) return;
      if (await ApiClient.instance.handleUnreachableOnBootstrap(
        ApiException('Unable to load dashboard from server.'),
        hasCachedData: hasCachedData,
      )) {
        return;
      }
      setState(() {
        _loading = false;
        _refreshing = false;
        _offline = hasCachedData;
        if (!hasCachedData) {
          _error = 'Unable to load dashboard from server.';
        }
      });
    }
  }

  Future<Map<String, dynamic>?> _readDashboardCache() async {
    final raw = await SessionStore.instance.readString(SessionStore.dashboardCacheKey);
    if (raw == null || raw.isEmpty) return null;
    try {
      return jsonDecode(raw) as Map<String, dynamic>;
    } catch (_) {
      return null;
    }
  }

  Future<void> reload({bool silent = true}) => _load(silent: silent);

  String _greeting() {
    final hour = AppDateTime.philippinesNow().hour;
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    return 'Good evening';
  }

  @override
  Widget build(BuildContext context) {
    if (_loading && _data == null) {
      return const ColoredBox(
        color: AppColors.bg,
        child: AsyncStatePanel.loading(),
      );
    }

    if (_error != null && _data == null) {
      return ColoredBox(
        color: AppColors.bg,
        child: AsyncStatePanel.error(message: _error!, onRetry: () => _load()),
      );
    }

    final data = _data ?? {};
    final guard = (data['guard'] as Map?)?.cast<String, dynamic>() ?? {};
    final deployment = (data['deployment'] as Map?)?.cast<String, dynamic>();
    final alerts = (data['alerts'] as List?) ?? [];
    final todaySchedule = (data['today_schedule'] as List?) ?? [];
    final scheduleCount = ApiClient.parseIntOrNull(data['schedule_count']) ?? todaySchedule.length;
    final status = data['status']?.toString() ?? 'off_duty';
    final statusLabel = data['status_label']?.toString() ?? 'Off Duty';
    final since = data['since']?.toString();
    final statusMessage = data['status_message']?.toString();
    final name = guard['name']?.toString() ?? ApiClient.instance.user?['name']?.toString() ?? 'Guard';
    final firstName = name.split(' ').first;
    final initials = guard['initials']?.toString() ?? 'G';
    final guardCode = guard['guard_code']?.toString() ?? '—';
    final shift = data['shift']?.toString() ?? '—';
    final shiftLabel = data['shift_label']?.toString() ?? '';
    final dutyStyle = DutyStatusStyle.fromApi(status, statusLabel: statusLabel);

    final hasActiveDeployment = deployment != null;
    final postTitle = hasActiveDeployment
        ? (deployment['post']?.toString() ?? 'Assignment')
        : (status == 'off_duty' ? 'No active assignment' : 'No assignment today');
    final postSubtitle = hasActiveDeployment
        ? _locationSubtitle(deployment)
        : (statusMessage ??
            (status == 'off_duty'
                ? 'Your shift for today is complete.'
                : 'Awaiting deployment from supervisor'));

    final lat = deployment?['lat'];
    final lng = deployment?['lng'];
    final gps = (lat != null && lng != null)
        ? '${_fmt(lat)}°N, ${_fmt(lng)}°E'
        : '—';
    final showDeploymentDetails = hasActiveDeployment;

    final shiftStart = deployment?['shift_start']?.toString();
    final shiftEnd = deployment?['shift_end']?.toString();
    final isPending = status == 'pending';
    final isCheckedIn = status == 'on_duty' || status == 'late';
    final isBetweenShifts = status == 'between_shifts';
    final showQuickAction = (isPending || isCheckedIn || isBetweenShifts) && widget.onOpenAttendance != null;

    return ColoredBox(
      color: AppColors.bg,
      child: RefreshIndicator(
        onRefresh: () => _load(silent: true),
        color: AppColors.blue,
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            if (_refreshing)
              const SliverToBoxAdapter(
                child: LinearProgressIndicator(minHeight: 2, color: AppColors.blue),
              ),
            SliverToBoxAdapter(
              child: ScreenHeader(
                eyebrow: _greeting(),
                title: firstName,
                subtitle: shiftLabel.isNotEmpty ? shiftLabel : 'Your duty overview for today',
                trailing: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    if (widget.onSignOut != null)
                      IconButton(
                        onPressed: () => widget.onSignOut?.call(),
                        tooltip: 'Sign out',
                        style: IconButton.styleFrom(
                          backgroundColor: Colors.white.withValues(alpha: 0.16),
                          foregroundColor: Colors.white,
                        ),
                        icon: const Icon(Icons.logout_rounded, size: 20),
                      ),
                    const SizedBox(width: 4),
                    CircleAvatar(
                      radius: 24,
                      backgroundColor: Colors.white.withValues(alpha: 0.22),
                      child: Text(
                        initials,
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                          fontSize: 15,
                        ),
                      ),
                    ),
                  ],
                ),
                bottom: StatusPill(style: dutyStyle, since: since),
              ),
            ),
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
              sliver: SliverList(
                delegate: SliverChildListDelegate([
                  if (_offline)
                    const Padding(
                      padding: EdgeInsets.only(bottom: 14),
                      child: _OfflineBanner(),
                    ),
                  if (isCheckedIn)
                    const Padding(
                      padding: EdgeInsets.only(bottom: 14),
                      child: _DutyCheckCard(),
                    ),
                  if (showQuickAction)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 14),
                      child: _QuickActionCard(
                        status: status,
                        statusMessage: statusMessage,
                        onTap: () {
                          HapticFeedback.lightImpact();
                          widget.onOpenAttendance?.call();
                        },
                      ),
                    ),
                  if (scheduleCount > 0) ...[
                    Row(
                      children: [
                        Expanded(
                          child: SectionLabel(
                            scheduleCount > 1 ? "Today's Schedule ($scheduleCount)" : "Today's Schedule",
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    ...todaySchedule.map((raw) {
                      final item = (raw as Map).cast<String, dynamic>();
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: _ScheduleCard(item: item),
                      );
                    }),
                    const SizedBox(height: 6),
                  ],
                  Row(
                    children: [
                      Expanded(child: _MetricTile(label: 'Guard ID', value: guardCode, icon: Icons.badge_outlined)),
                      const SizedBox(width: 12),
                      Expanded(child: _MetricTile(label: 'Shift', value: shift, icon: Icons.schedule_rounded)),
                    ],
                  ),
                  if (showDeploymentDetails && shiftStart != null && shiftEnd != null) ...[
                    const SizedBox(height: 12),
                    AppCard(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                      child: Row(
                        children: [
                          Container(
                            width: 40,
                            height: 40,
                            decoration: BoxDecoration(
                              color: AppColors.blueSoft,
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: const Icon(Icons.timelapse_rounded, color: AppColors.blue, size: 22),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const SectionLabel('Duty Window'),
                                const SizedBox(height: 4),
                                Text(
                                  '$shiftStart – $shiftEnd',
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w800,
                                    color: AppColors.text,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                  const SizedBox(height: 12),
                  AppCard(
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: 44,
                          height: 44,
                          decoration: BoxDecoration(
                            color: AppColors.blueSoft,
                            borderRadius: BorderRadius.circular(14),
                          ),
                          child: const Icon(Icons.location_on_rounded, color: AppColors.blue, size: 24),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const SectionLabel('Assigned Post'),
                              const SizedBox(height: 6),
                              Text(
                                postTitle,
                                style: const TextStyle(
                                  fontSize: 17,
                                  fontWeight: FontWeight.w800,
                                  color: AppColors.text,
                                  height: 1.25,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                postSubtitle,
                                style: const TextStyle(
                                  fontSize: 13,
                                  color: AppColors.muted,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  if (showDeploymentDetails) ...[
                    const SizedBox(height: 12),
                    AppCard(
                      color: AppColors.greenSoft,
                      border: Border.all(color: AppColors.greenBorder),
                      child: Row(
                        children: [
                          Container(
                            width: 44,
                            height: 44,
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.7),
                              borderRadius: BorderRadius.circular(14),
                            ),
                            child: const Icon(Icons.near_me_rounded, color: AppColors.green, size: 22),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const SectionLabel('GPS Location'),
                                const SizedBox(height: 6),
                                Text(
                                  gps,
                                  style: const TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w800,
                                    color: AppColors.green,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                  const SizedBox(height: 22),
                  Row(
                    children: [
                      const Expanded(child: SectionLabel('Recent Alerts')),
                      if (alerts.isNotEmpty)
                        Text(
                          '${alerts.length} shown',
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: AppColors.muted,
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  if (alerts.isEmpty)
                    AppCard(
                      child: Row(
                        children: [
                          Container(
                            width: 44,
                            height: 44,
                            decoration: BoxDecoration(
                              color: AppColors.blueSoft,
                              borderRadius: BorderRadius.circular(14),
                            ),
                            child: const Icon(Icons.notifications_none_rounded, color: AppColors.blue, size: 22),
                          ),
                          const SizedBox(width: 12),
                          const Expanded(
                            child: Text(
                              'No recent alerts. You\'re all caught up.',
                              style: TextStyle(
                                color: AppColors.muted,
                                fontWeight: FontWeight.w600,
                                height: 1.35,
                              ),
                            ),
                          ),
                        ],
                      ),
                    )
                  else
                    ...alerts.map((raw) {
                      final alert = (raw as Map).cast<String, dynamic>();
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: _AlertCard(
                          tone: alert['tone']?.toString() ?? 'info',
                          message: alert['message']?.toString() ?? '',
                          time: alert['time']?.toString() ?? '',
                        ),
                      );
                    }),
                ]),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _fmt(dynamic value) {
    final n = double.tryParse(value.toString());
    return n == null ? value.toString() : n.toStringAsFixed(5);
  }

  String _locationSubtitle(Map<String, dynamic> deployment) {
    final subtitle = deployment['location_subtitle']?.toString().trim();
    if (subtitle != null && subtitle.isNotEmpty) {
      return subtitle;
    }

    final area = deployment['area']?.toString().trim();
    final city = deployment['city']?.toString().trim();
    if (area != null && area.isNotEmpty && city != null && city.isNotEmpty) {
      return '$area · $city';
    }

    final address = deployment['address']?.toString().trim();
    if (address != null && address.isNotEmpty) {
      return address;
    }

    return 'Location details unavailable';
  }
}

class _QuickActionCard extends StatelessWidget {
  const _QuickActionCard({
    required this.status,
    required this.onTap,
    this.statusMessage,
  });

  final String status;
  final VoidCallback onTap;
  final String? statusMessage;

  @override
  Widget build(BuildContext context) {
    final isPending = status == 'pending';
    final isCheckedIn = status == 'on_duty' || status == 'late';
    final isBetweenShifts = status == 'between_shifts';
    final color = isBetweenShifts
        ? AppColors.amber
        : isPending
            ? AppColors.green
            : AppColors.blue;
    final label = isBetweenShifts
        ? 'Between shifts'
        : isPending
            ? 'Check in now'
            : isCheckedIn
                ? 'Open attendance'
                : 'Open attendance';
    final subtitle = statusMessage ??
        (isBetweenShifts
            ? 'View your next assignment and check-in window'
            : isPending
                ? 'Tap to verify GPS and start your shift'
                : isCheckedIn
                    ? 'View check-in time or check out when your shift ends'
                    : 'View time-in status or check out when done');
    final icon = isBetweenShifts
        ? Icons.hourglass_bottom_rounded
        : isPending
            ? Icons.login_rounded
            : Icons.fingerprint_rounded;

    return Material(
      color: color,
      borderRadius: BorderRadius.circular(AppRadius.lg),
      elevation: 0,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(18, 16, 14, 16),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.18),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(icon, color: Colors.white, size: 24),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      label,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      subtitle,
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.88),
                        fontSize: 12.5,
                        fontWeight: FontWeight.w500,
                        height: 1.3,
                      ),
                    ),
                  ],
                ),
              ),
              Icon(Icons.arrow_forward_rounded, color: Colors.white.withValues(alpha: 0.9)),
            ],
          ),
        ),
      ),
    );
  }
}

class _MetricTile extends StatelessWidget {
  const _MetricTile({
    required this.label,
    required this.value,
    required this.icon,
  });

  final String label;
  final String value;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 16, color: AppColors.muted),
              const SizedBox(width: 6),
              SectionLabel(label),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            value,
            style: const TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w800,
              color: AppColors.text,
            ),
          ),
        ],
      ),
    );
  }
}

class _AlertCard extends StatelessWidget {
  const _AlertCard({
    required this.tone,
    required this.message,
    required this.time,
  });

  final String tone;
  final String message;
  final String time;

  @override
  Widget build(BuildContext context) {
    final style = _toneStyle(tone);

    return AppCard(
      padding: const EdgeInsets.all(14),
      color: style.bg,
      border: Border.all(color: style.border),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(color: style.accent, borderRadius: BorderRadius.circular(10)),
            child: Icon(style.icon, color: Colors.white, size: 17),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  message,
                  style: const TextStyle(
                    fontSize: 13.5,
                    fontWeight: FontWeight.w600,
                    color: AppColors.text,
                    height: 1.35,
                  ),
                ),
                if (time.isNotEmpty) ...[
                  const SizedBox(height: 6),
                  Text(
                    time,
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppColors.muted,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  _AlertTone _toneStyle(String tone) {
    switch (tone) {
      case 'success':
        return _AlertTone(
          bg: AppColors.greenSoft,
          border: AppColors.greenBorder,
          accent: AppColors.green,
          icon: Icons.check_rounded,
        );
      case 'critical':
        return const _AlertTone(
          bg: Color(0xFFFFEBEE),
          border: Color(0xFFFECACA),
          accent: AppColors.red,
          icon: Icons.close_rounded,
        );
      case 'warning':
        return const _AlertTone(
          bg: AppColors.amberSoft,
          border: Color(0xFFFDE68A),
          accent: AppColors.amber,
          icon: Icons.priority_high_rounded,
        );
      default:
        return const _AlertTone(
          bg: AppColors.blueSoft,
          border: AppColors.blueBorder,
          accent: AppColors.blue,
          icon: Icons.sensors_rounded,
        );
    }
  }
}

class _ScheduleCard extends StatelessWidget {
  const _ScheduleCard({required this.item});

  final Map<String, dynamic> item;

  @override
  Widget build(BuildContext context) {
    final scheduleStatus = item['schedule_status']?.toString() ?? 'pending';
    final isActive = item['is_active'] == true;
    final (badgeColor, badgeTextColor) = switch (scheduleStatus) {
      'completed' => (AppColors.greenSoft, AppColors.green),
      'on_duty' || 'late' => (AppColors.blueSoft, AppColors.blue),
      'ready' => (AppColors.greenSoft, AppColors.green),
      'upcoming' => (AppColors.amberSoft, AppColors.amber),
      _ => (AppColors.bg, AppColors.muted),
    };

    return AppCard(
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
      border: Border.all(
        color: isActive ? AppColors.blue : AppColors.border,
        width: isActive ? 1.5 : 1,
      ),
      color: isActive ? AppColors.blueSoft.withValues(alpha: 0.35) : Colors.white,
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: badgeColor,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              scheduleStatus == 'completed'
                  ? Icons.check_rounded
                  : scheduleStatus == 'on_duty' || scheduleStatus == 'late'
                      ? Icons.verified_user_rounded
                      : Icons.schedule_rounded,
              color: badgeTextColor,
              size: 20,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item['post']?.toString() ?? 'Assignment',
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                    color: AppColors.text,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  item['shift_range']?.toString() ?? '',
                  style: const TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w600,
                    color: AppColors.muted,
                  ),
                ),
                if (item['time_in'] != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    'In ${item['time_in']}${item['time_out'] != null ? ' · Out ${item['time_out']}' : ''}',
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppColors.muted,
                    ),
                  ),
                ] else if (scheduleStatus == 'upcoming' && item['check_in_opens_at'] != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    'Check-in opens ${item['check_in_opens_at']}',
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppColors.amber,
                    ),
                  ),
                ],
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: badgeColor,
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              (item['schedule_label']?.toString() ?? 'Pending').toUpperCase(),
              style: TextStyle(
                fontSize: 9,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.5,
                color: badgeTextColor,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _AlertTone {
  const _AlertTone({
    required this.bg,
    required this.border,
    required this.accent,
    required this.icon,
  });

  final Color bg;
  final Color border;
  final Color accent;
  final IconData icon;
}

class _OfflineBanner extends StatelessWidget {
  const _OfflineBanner();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF7ED),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFFED7AA)),
      ),
      child: const Text(
        'Offline. Showing your last saved duty. New checks will sync when the connection returns.',
        style: TextStyle(color: Color(0xFF9A3412), fontWeight: FontWeight.w700, height: 1.35),
      ),
    );
  }
}

class _DutyCheckCard extends StatelessWidget {
  const _DutyCheckCard();

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: DutyWatchService.instance,
      builder: (context, _) {
        final snap = DutyWatchService.instance.snapshot;
        final color = switch (snap.tone) {
          'green' => AppColors.green,
          'amber' => AppColors.amber,
          'red' => AppColors.red,
          _ => AppColors.muted,
        };

        return Container(
          width: double.infinity,
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.border),
          ),
          child: Row(
            children: [
              Container(
                width: 12,
                height: 12,
                decoration: BoxDecoration(color: color, shape: BoxShape.circle),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      snap.label,
                      style: const TextStyle(fontWeight: FontWeight.w800, color: AppColors.text),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      snap.detail,
                      style: const TextStyle(fontSize: 12.5, height: 1.35, color: AppColors.muted),
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
