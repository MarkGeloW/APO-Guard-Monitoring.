import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/theme/app_theme.dart';
import '../../attendance/screens/attendance_screen.dart';
import '../../auth/screens/login_screen.dart';
import '../../profile/screens/alerts_screen.dart';
import '../../../data/services/api_client.dart';
import '../../../data/services/duty_watch_service.dart';
import 'dashboard_screen.dart';

class GuardShell extends StatefulWidget {
  const GuardShell({super.key});

  @override
  State<GuardShell> createState() => _GuardShellState();
}

class _GuardShellState extends State<GuardShell> with WidgetsBindingObserver {
  int _index = 0;
  int _unreadAlerts = 0;
  final _dashboardKey = GlobalKey<DashboardScreenState>();
  late final List<Widget> _pages;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    ApiClient.onSessionExpired = _handleSessionExpired;
    DutyWatchService.instance.start();
    _pages = [
      DashboardScreen(
        key: _dashboardKey,
        onSignOut: _confirmSignOut,
        onOpenAttendance: () => _switchTab(1),
      ),
      AttendanceScreen(onChanged: _afterAttendanceChange),
      AlertsScreen(onDataLoaded: _refreshUnreadBadge),
    ];
    _refreshUnreadBadge();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    DutyWatchService.instance.stop();
    if (ApiClient.onSessionExpired == _handleSessionExpired) {
      ApiClient.onSessionExpired = null;
    }
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      DutyWatchService.instance.tick();
    }
  }

  void _handleSessionExpired(String message) {
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => LoginScreen(initialMessage: message)),
      (_) => false,
    );
  }

  void _refreshDashboard() {
    _dashboardKey.currentState?.reload();
  }

  void _afterAttendanceChange() {
    _refreshDashboard();
    DutyWatchService.instance.tick(force: true);
  }

  void _switchTab(int index) {
    if (_index == index) {
      if (index == 0) _refreshDashboard();
      return;
    }
    HapticFeedback.selectionClick();
    setState(() => _index = index);
    if (index == 0) {
      _refreshDashboard();
      DutyWatchService.instance.tick();
    } else if (index != 2) {
      _refreshUnreadBadge();
    }
  }

  Future<void> _refreshUnreadBadge() async {
    try {
      final data = await ApiClient.instance.getAlerts();
      final alerts = (data['alerts'] as List?) ?? [];
      final unread = alerts.where((raw) {
        final item = (raw as Map).cast<String, dynamic>();
        if (item.containsKey('unread')) return item['unread'] == true;
        if (item.containsKey('read')) return item['read'] != true;
        return false;
      }).length;
      if (mounted) setState(() => _unreadAlerts = unread);
    } catch (_) {}
  }

  Future<void> _confirmSignOut() async {
    final shouldLogout = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
          icon: Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: const Color(0xFFFFEBEE),
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(Icons.logout_rounded, color: AppColors.red),
          ),
          iconPadding: const EdgeInsets.only(top: 20),
          title: const Text(
            'Sign Out',
            style: TextStyle(fontWeight: FontWeight.w800),
          ),
          content: const Text(
            'Are you sure you want to sign out of the guard app?',
            style: TextStyle(color: AppColors.muted, height: 1.45),
          ),
          actionsPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text(
                'Cancel',
                style: TextStyle(fontWeight: FontWeight.w700, color: AppColors.muted),
              ),
            ),
            FilledButton(
              onPressed: () => Navigator.of(context).pop(true),
              style: FilledButton.styleFrom(backgroundColor: AppColors.red),
              child: const Text('Sign Out'),
            ),
          ],
        );
      },
    );

    if (shouldLogout == true && mounted) {
      DutyWatchService.instance.stop();
      await ApiClient.instance.logout();
      if (!mounted) return;
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (_) => const LoginScreen()),
        (_) => false,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(index: _index, children: _pages),
      bottomNavigationBar: Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: AppColors.border)),
          boxShadow: [
            BoxShadow(
              color: Color(0x0D000000),
              blurRadius: 18,
              offset: Offset(0, -4),
            ),
          ],
        ),
        child: SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(10, 8, 10, 8),
            child: Row(
              children: [
                _NavItem(
                  icon: Icons.grid_view_rounded,
                  label: 'Dashboard',
                  active: _index == 0,
                  onTap: () => _switchTab(0),
                ),
                _NavItem(
                  icon: Icons.fingerprint_rounded,
                  label: 'Attendance',
                  active: _index == 1,
                  onTap: () => _switchTab(1),
                ),
                _NavItem(
                  icon: Icons.notifications_none_rounded,
                  label: 'Alerts',
                  active: _index == 2,
                  badge: _unreadAlerts,
                  onTap: () => _switchTab(2),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _NavItem extends StatelessWidget {
  const _NavItem({
    required this.icon,
    required this.label,
    required this.active,
    required this.onTap,
    this.badge = 0,
  });

  final IconData icon;
  final String label;
  final bool active;
  final VoidCallback onTap;
  final int badge;

  @override
  Widget build(BuildContext context) {
    final color = active ? AppColors.blue : AppColors.muted;

    return Expanded(
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(AppRadius.md),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            curve: Curves.easeOutCubic,
            padding: const EdgeInsets.symmetric(vertical: 10),
            decoration: BoxDecoration(
              color: active ? AppColors.blueSoft : Colors.transparent,
              borderRadius: BorderRadius.circular(AppRadius.md),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Stack(
                  clipBehavior: Clip.none,
                  children: [
                    Icon(icon, size: 24, color: color),
                    if (badge > 0)
                      Positioned(
                        right: -10,
                        top: -6,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                          constraints: const BoxConstraints(minWidth: 18),
                          decoration: BoxDecoration(
                            color: AppColors.red,
                            borderRadius: BorderRadius.circular(999),
                            border: Border.all(color: Colors.white, width: 1.5),
                          ),
                          child: Text(
                            badge > 99 ? '99+' : '$badge',
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 9,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 5),
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 0.2,
                    color: color,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
