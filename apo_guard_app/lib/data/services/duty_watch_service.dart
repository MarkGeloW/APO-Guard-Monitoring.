import 'dart:async';
import 'dart:convert';
import 'dart:math';

import 'package:flutter/foundation.dart';

import 'api_client.dart';
import 'gps_service.dart';
import 'session_store.dart';

class DutyWatchSnapshot {
  const DutyWatchSnapshot({
    required this.label,
    required this.detail,
    required this.tone,
    required this.pending,
    required this.active,
  });

  final String label;
  final String detail;
  final String tone;
  final int pending;
  final bool active;

  static const idle = DutyWatchSnapshot(
    label: 'Duty check off',
    detail: 'Starts automatically after check-in.',
    tone: 'slate',
    pending: 0,
    active: false,
  );
}

/// Sends a GPS duty check every 30 minutes while the guard is checked in.
/// If the network is down, the check is stored and uploaded later.
class DutyWatchService extends ChangeNotifier {
  DutyWatchService._();
  static final DutyWatchService instance = DutyWatchService._();

  static const Duration interval = Duration(minutes: 30);

  Timer? _timer;
  bool _busy = false;
  DutyWatchSnapshot snapshot = DutyWatchSnapshot.idle;

  void start() {
    _timer?.cancel();
    unawaited(tick());
    _timer = Timer.periodic(interval, (_) => tick());
  }

  void stop() {
    _timer?.cancel();
    _timer = null;
  }

  Future<void> tick({bool force = false}) async {
    if (_busy || !ApiClient.instance.isAuthenticated) return;
    _busy = true;
    try {
      await _flushQueue();
      final dashboard = await _loadDashboard();
      final status = dashboard?['status']?.toString();
      final deployment = (dashboard?['deployment'] as Map?)?.cast<String, dynamic>();
      final deploymentId = ApiClient.parseIntOrNull(deployment?['id']);
      final onDuty = status == 'on_duty' || status == 'late';

      if (!onDuty || deploymentId == null) {
        _set(DutyWatchSnapshot.idle);
        return;
      }

      final lastSent = await _lastSentAt();
      final due = force || lastSent == null || DateTime.now().difference(lastSent) >= interval;
      final pending = await _queue();

      if (!due) {
        final mins = interval.inMinutes - DateTime.now().difference(lastSent).inMinutes;
        _set(DutyWatchSnapshot(
          label: 'On duty check active',
          detail: pending.isEmpty
              ? 'Next GPS check in about ${mins.clamp(1, interval.inMinutes)} min.'
              : '${pending.length} check${pending.length == 1 ? '' : 's'} waiting to sync.',
          tone: pending.isEmpty ? 'green' : 'amber',
          pending: pending.length,
          active: true,
        ));
        return;
      }

      try {
        final position = await GpsService.currentPosition();
        pending.add({
          'client_uuid': _uuid(),
          'deployment_id': deploymentId,
          'latitude': position.latitude,
          'longitude': position.longitude,
          'captured_at': DateTime.now().toUtc().toIso8601String(),
        });
        await _saveQueue(pending);
        await _rememberSent(DateTime.now());
      } catch (error) {
        _set(DutyWatchSnapshot(
          label: 'GPS check failed',
          detail: 'Turn on location, then reopen the app. ${pending.length} saved check${pending.length == 1 ? '' : 's'} will sync later.',
          tone: 'red',
          pending: pending.length,
          active: true,
        ));
        return;
      }

      await _flushQueue();
    } finally {
      _busy = false;
    }
  }

  Future<Map<String, dynamic>?> _loadDashboard() async {
    try {
      final data = await ApiClient.instance.getDashboard();
      await SessionStore.instance.writeString(
        SessionStore.dashboardCacheKey,
        jsonEncode(data),
      );
      return data;
    } on ApiException {
      return _cachedDashboard();
    } catch (_) {
      return _cachedDashboard();
    }
  }

  Future<Map<String, dynamic>?> _cachedDashboard() async {
    final raw = await SessionStore.instance.readString(SessionStore.dashboardCacheKey);
    if (raw == null || raw.isEmpty) return null;
    try {
      return jsonDecode(raw) as Map<String, dynamic>;
    } catch (_) {
      return null;
    }
  }

  Future<void> _flushQueue() async {
    final pending = await _queue();
    if (pending.isEmpty) return;

    try {
      final response = await ApiClient.instance.sendDutyPings(pending);
      final done = <String>{
        ...((response['saved'] as List?) ?? []).map((item) => (item as Map)['client_uuid'].toString()),
        ...((response['skipped'] as List?) ?? []).map((item) => (item as Map)['client_uuid'].toString()),
      };
      final remaining = pending.where((item) => !done.contains(item['client_uuid'])).toList();
      await _saveQueue(remaining);
      _set(DutyWatchSnapshot(
        label: remaining.isEmpty ? 'On duty check sent' : 'Waiting to sync',
        detail: remaining.isEmpty
            ? 'GPS check reached the office. Next one in 30 minutes.'
            : '${remaining.length} check${remaining.length == 1 ? '' : 's'} still waiting for a connection.',
        tone: remaining.isEmpty ? 'green' : 'amber',
        pending: remaining.length,
        active: true,
      ));
    } on ApiException catch (error) {
      if (ApiClient.isConnectionError(error)) {
        _set(DutyWatchSnapshot(
          label: 'Saved offline',
          detail: '${pending.length} duty check${pending.length == 1 ? '' : 's'} will sync when the signal returns.',
          tone: 'amber',
          pending: pending.length,
          active: true,
        ));
        return;
      }
      debugPrint('Duty ping rejected: $error');
    }
  }

  Future<List<Map<String, dynamic>>> _queue() async {
    final raw = await SessionStore.instance.readString(SessionStore.dutyQueueKey);
    if (raw == null || raw.isEmpty) return [];
    try {
      final decoded = jsonDecode(raw);
      if (decoded is! List) return [];
      return decoded.map((item) => (item as Map).cast<String, dynamic>()).toList();
    } catch (_) {
      return [];
    }
  }

  Future<void> _saveQueue(List<Map<String, dynamic>> items) async {
    await SessionStore.instance.writeString(SessionStore.dutyQueueKey, jsonEncode(items));
  }

  Future<DateTime?> _lastSentAt() async {
    final raw = await SessionStore.instance.readString(SessionStore.dutyLastSentKey);
    if (raw == null || raw.isEmpty) return null;
    return DateTime.tryParse(raw);
  }

  Future<void> _rememberSent(DateTime time) async {
    await SessionStore.instance.writeString(SessionStore.dutyLastSentKey, time.toIso8601String());
  }

  void _set(DutyWatchSnapshot next) {
    snapshot = next;
    notifyListeners();
  }

  String _uuid() {
    final rand = Random.secure();
    final bytes = List<int>.generate(16, (_) => rand.nextInt(256));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    final hex = bytes.map((b) => b.toRadixString(16).padLeft(2, '0')).join();
    return '${hex.substring(0, 8)}-${hex.substring(8, 12)}-${hex.substring(12, 16)}-${hex.substring(16, 20)}-${hex.substring(20)}';
  }
}
