import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_card.dart';
import '../../../core/widgets/async_state_panel.dart';
import '../../../core/widgets/attendance_stepper.dart';
import '../../../core/widgets/screen_header.dart';
import '../../../data/services/api_client.dart';
import '../../../data/services/face_signature.dart';
import '../../../data/services/gps_service.dart';
import 'face_scan_screen.dart';

class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key, this.onChanged});

  final VoidCallback? onChanged;

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen> {
  bool _loading = true;
  bool _busy = false;
  String? _error;
  Map<String, dynamic>? _data;
  int? _selectedDeploymentId;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load({bool silent = false, int? deploymentId}) async {
    final targetId = deploymentId ?? _selectedDeploymentId;
    final hasCachedData = _data != null;

    setState(() {
      if (!silent) {
        _loading = true;
      }
      _error = null;
    });

    try {
      final data = await ApiClient.instance.getAttendance(deploymentId: targetId);
      if (!mounted) return;
      setState(() {
        _data = data;
        _selectedDeploymentId = ApiClient.parseIntOrNull(data['deployment_id']);
        _loading = false;
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
        _error = e.message;
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      if (await ApiClient.instance.handleUnreachableOnBootstrap(
        ApiException('Unable to load attendance from server.'),
        hasCachedData: hasCachedData,
      )) {
        return;
      }
      setState(() {
        _error = 'Unable to load attendance from server.';
        _loading = false;
      });
    }
  }

  void _applyCheckInResult(Map<String, dynamic> result) {
    setState(() {
      _data = {
        ...?_data,
        'checked_in': true,
        'can_check_in': false,
        'can_check_out': false,
        'time_in': result['time_in']?.toString() ?? _data?['time_in'],
        'gps_verified': result['gps_verified'] == true,
        'check_in_late_minutes': result['check_in_late_minutes'],
        'check_in_blocked_message': null,
      };
    });
  }

  void _applyCheckOutResult(Map<String, dynamic> result) {
    setState(() {
      _data = {
        ...?_data,
        'checked_in': false,
        'completed': true,
        'can_check_in': false,
        'can_check_out': false,
        'time_out': result['time_out']?.toString() ?? _data?['time_out'],
        'check_out_late_minutes': result['check_out_late_minutes'],
        'checkout_blocked_message': null,
      };
    });
  }

  String _errorMessage(Object error) {
    if (error is ApiException) return error.message;
    final text = error.toString();
    return text.replaceFirst('Exception: ', '');
  }

  Future<void> _selectDeployment(int deploymentId) async {
    if (_selectedDeploymentId == deploymentId || _busy) return;
    HapticFeedback.selectionClick();
    await _load(silent: true, deploymentId: deploymentId);
  }

  Future<FaceCaptureResult?> _captureFace({required FaceScanMode mode}) async {
    return Navigator.of(context).push<FaceCaptureResult>(
      MaterialPageRoute(
        builder: (_) => FaceScanScreen(
          mode: mode,
          title: mode == FaceScanMode.enroll ? 'Enroll Face' : 'Verify Face',
          subtitle: mode == FaceScanMode.enroll
              ? 'Save your face once before attendance.'
              : 'Face + GPS are required to confirm you are on post.',
        ),
      ),
    );
  }

  Future<void> _enrollFace() async {
    if (_busy) return;

    final capture = await _captureFace(mode: FaceScanMode.enroll);
    if (capture == null || !mounted) return;

    setState(() => _busy = true);
    try {
      final result = await ApiClient.instance.enrollFace(
        faceImage: capture.imageFile,
        faceSignature: capture.signature,
      );
      if (!mounted) return;
      setState(() {
        _data = {
          ...?_data,
          'face_enrolled': true,
        };
      });
      _showFeedback(result['message']?.toString() ?? 'Face enrolled', success: true);
      await _load(silent: true, deploymentId: _selectedDeploymentId);
    } on ApiException catch (e) {
      if (!mounted) return;
      _showFeedback(e.message, success: false);
    } catch (e) {
      if (!mounted) return;
      _showFeedback(_errorMessage(e), success: false);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _checkIn() async {
    if (_busy) return;

    final faceEnrolled = _data?['face_enrolled'] == true;
    if (!faceEnrolled) {
      _showFeedback('Enroll your face first before checking in.', success: false);
      await _enrollFace();
      return;
    }

    HapticFeedback.mediumImpact();
    final capture = await _captureFace(mode: FaceScanMode.verify);
    if (capture == null || !mounted) return;

    setState(() => _busy = true);
    try {
      final pos = await GpsService.currentPosition();
      final result = await ApiClient.instance.checkIn(
        latitude: pos.latitude,
        longitude: pos.longitude,
        faceImage: capture.imageFile,
        faceSignature: capture.signature,
        deploymentId: _selectedDeploymentId ?? ApiClient.parseIntOrNull(_data?['deployment_id']),
      );
      if (!mounted) return;
      HapticFeedback.lightImpact();
      _applyCheckInResult(result);
      setState(() => _busy = false);
      _showFeedback(result['message']?.toString() ?? 'Checked in', success: true);
      widget.onChanged?.call();
      await _load(silent: true, deploymentId: _selectedDeploymentId);
    } on ApiException catch (e) {
      if (!mounted) return;
      HapticFeedback.vibrate();
      _showFeedback(e.message, success: false);
    } catch (e) {
      if (!mounted) return;
      HapticFeedback.vibrate();
      _showFeedback(_errorMessage(e), success: false);
    } finally {
      if (mounted && _busy) setState(() => _busy = false);
    }
  }

  Future<void> _checkOut() async {
    if (_busy) return;

    final faceEnrolled = _data?['face_enrolled'] == true;
    if (!faceEnrolled) {
      _showFeedback('Enroll your face first before checking out.', success: false);
      await _enrollFace();
      return;
    }

    HapticFeedback.mediumImpact();
    final capture = await _captureFace(mode: FaceScanMode.verify);
    if (capture == null || !mounted) return;

    setState(() => _busy = true);
    try {
      final pos = await GpsService.currentPosition();
      final result = await ApiClient.instance.checkOut(
        latitude: pos.latitude,
        longitude: pos.longitude,
        faceImage: capture.imageFile,
        faceSignature: capture.signature,
        deploymentId: _selectedDeploymentId ?? ApiClient.parseIntOrNull(_data?['deployment_id']),
      );
      if (!mounted) return;
      HapticFeedback.lightImpact();
      _applyCheckOutResult(result);
      setState(() => _busy = false);
      _showFeedback(result['message']?.toString() ?? 'Checked out', success: true);
      widget.onChanged?.call();
      await _load(silent: true, deploymentId: _selectedDeploymentId);
    } on ApiException catch (e) {
      if (!mounted) return;
      HapticFeedback.vibrate();
      _showFeedback(e.message, success: false);
    } catch (e) {
      if (!mounted) return;
      HapticFeedback.vibrate();
      _showFeedback(_errorMessage(e), success: false);
    } finally {
      if (mounted && _busy) setState(() => _busy = false);
    }
  }

  void _showFeedback(String message, {required bool success}) {
    ScaffoldMessenger.of(context).hideCurrentSnackBar();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        behavior: SnackBarBehavior.floating,
        margin: const EdgeInsets.all(16),
        backgroundColor: success ? AppColors.green : AppColors.red,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.sm)),
        content: Row(
          children: [
            Icon(
              success ? Icons.check_circle_rounded : Icons.error_outline_rounded,
              color: Colors.white,
              size: 20,
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                message.replaceFirst('Exception: ', ''),
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
            ),
          ],
        ),
      ),
    );
  }

  AttendanceStep _stepFor({
    required bool checkedIn,
    required String? timeOut,
  }) {
    if (timeOut != null) return AttendanceStep.completed;
    if (checkedIn) return AttendanceStep.checkedIn;
    return AttendanceStep.pending;
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const ColoredBox(
        color: AppColors.bg,
        child: AsyncStatePanel.loading(message: 'Loading attendance…'),
      );
    }

    if (_error != null) {
      return ColoredBox(
        color: AppColors.bg,
        child: AsyncStatePanel.error(message: _error!, onRetry: _load),
      );
    }

    final timeIn = _data?['time_in']?.toString();
    final timeOut = _data?['time_out']?.toString();
    final verified = _data?['gps_verified'] == true;
    final canCheckIn = _data?['can_check_in'] == true;
    final canCheckOut = _data?['can_check_out'] == true;
    final checkedIn = _data?['checked_in'] == true;
    final shiftStart = _data?['shift_start']?.toString();
    final shiftEnd = _data?['shift_end']?.toString();
    final checkoutBlockedMessage = _data?['checkout_blocked_message']?.toString();
    final checkoutAvailableAt = _data?['checkout_available_at']?.toString();
    final checkInBlockedMessage = _data?['check_in_blocked_message']?.toString();
    final checkInOpensAt = _data?['check_in_opens_at']?.toString();
    final todayDeployments = (_data?['today_deployments'] as List?) ?? [];
    final hasDeployment = _data?['deployment_id'] != null || todayDeployments.isNotEmpty;
    final statusMessage = _data?['status_message']?.toString();
    final checkOutLateMinutes = ApiClient.parseIntOrNull(_data?['check_out_late_minutes']);
    final checkInLateMinutes = ApiClient.parseIntOrNull(_data?['check_in_late_minutes']);
    final dateLabel = _data?['date_label']?.toString() ?? '';
    final post = _data?['post']?.toString();
    final lat = _data?['lat'];
    final lng = _data?['lng'];
    final gps = (lat != null && lng != null)
        ? '${double.tryParse(lat.toString())?.toStringAsFixed(4) ?? lat}°N ${double.tryParse(lng.toString())?.toStringAsFixed(4) ?? lng}°E'
        : '—';
    final faceEnrolled = _data?['face_enrolled'] == true;
    final faceVerified = _data?['face_verified'] == true;
    final history = (_data?['history'] as List?) ?? [];
    final step = _stepFor(checkedIn: checkedIn, timeOut: timeOut);

    return ColoredBox(
      color: AppColors.bg,
      child: RefreshIndicator(
        onRefresh: () => _load(silent: true),
        color: AppColors.blue,
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: ScreenHeader(
                eyebrow: 'Attendance',
                title: 'Check-In / Out',
                subtitle: dateLabel,
              ),
            ),
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
              sliver: SliverList(
                delegate: SliverChildListDelegate([
                  AppCard(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 18),
                    child: AttendanceStepper(step: step),
                  ),
                  if (todayDeployments.length > 1) ...[
                    const SizedBox(height: 14),
                    const SectionLabel('Today\'s Assignments'),
                    const SizedBox(height: 10),
                    ...todayDeployments.map((raw) {
                      final item = (raw as Map).cast<String, dynamic>();
                      final id = ApiClient.parseIntOrNull(item['id']);
                      if (id == null) return const SizedBox.shrink();

                      return Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: _ShiftPickerCard(
                          post: item['post']?.toString() ?? 'Assignment',
                          shiftRange: item['shift_range']?.toString() ?? '',
                          scheduleLabel: item['schedule_label']?.toString() ?? '',
                          scheduleStatus: item['schedule_status']?.toString() ?? 'pending',
                          selected: id == _selectedDeploymentId,
                          onTap: () => _selectDeployment(id),
                        ),
                      );
                    }),
                  ],
                  if (statusMessage != null && statusMessage.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    AppCard(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      color: AppColors.amberSoft,
                      border: Border.all(color: const Color(0xFFFDE68A)),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Icon(Icons.schedule_rounded, size: 18, color: AppColors.amber),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              statusMessage,
                              style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: AppColors.text,
                                height: 1.35,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                  const SizedBox(height: 14),
                  if (post != null && post.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 14),
                      child: AppCard(
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
                              child: const Icon(Icons.location_on_rounded, color: AppColors.blue, size: 22),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const SectionLabel('Today\'s Post'),
                                  const SizedBox(height: 4),
                                  Text(
                                    post,
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
                    ),
                  _StatusCard(
                    timeIn: timeIn,
                    timeOut: timeOut,
                    gps: gps,
                    verified: verified,
                    faceVerified: faceVerified,
                    faceEnrolled: faceEnrolled,
                    shiftStart: shiftStart,
                    shiftEnd: shiftEnd,
                    checkedIn: checkedIn,
                    checkOutLateMinutes: checkOutLateMinutes,
                    checkInLateMinutes: checkInLateMinutes,
                  ),
                  const SizedBox(height: 14),
                  if (!faceEnrolled) ...[
                    AppCard(
                      color: const Color(0xFFFFF7ED),
                      border: Border.all(color: const Color(0xFFFDBA74)),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Face enrollment required',
                            style: TextStyle(
                              fontWeight: FontWeight.w800,
                              color: AppColors.text,
                            ),
                          ),
                          const SizedBox(height: 6),
                          const Text(
                            'Save your face once. Check-in and check-out will require face verification plus GPS.',
                            style: TextStyle(
                              fontSize: 13,
                              color: AppColors.muted,
                              height: 1.35,
                            ),
                          ),
                          const SizedBox(height: 12),
                          _ActionButton(
                            label: 'Enroll Face',
                            icon: Icons.face_retouching_natural_rounded,
                            color: AppColors.amber,
                            onPressed: _busy ? null : _enrollFace,
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 14),
                  ],
                  if (_busy)
                    AppCard(
                      child: Column(
                        children: [
                          const SizedBox(
                            width: 32,
                            height: 32,
                            child: CircularProgressIndicator(strokeWidth: 3, color: AppColors.blue),
                          ),
                          const SizedBox(height: 12),
                          const Text(
                            'Verifying face and GPS…',
                            style: TextStyle(fontWeight: FontWeight.w700, color: AppColors.text),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Stay at your assigned post while we confirm',
                            style: TextStyle(
                              fontSize: 12.5,
                              color: AppColors.muted.withValues(alpha: 0.95),
                            ),
                          ),
                        ],
                      ),
                    )
                  else if (canCheckIn && faceEnrolled)
                    _ActionButton(
                      label: 'Check In with Face + GPS',
                      icon: Icons.login_rounded,
                      color: AppColors.green,
                      onPressed: _checkIn,
                    )
                  else if (hasDeployment && !checkedIn && checkInBlockedMessage != null) ...[
                    _ActionButton(
                      label: checkInOpensAt != null
                          ? 'Check In · Opens $checkInOpensAt'
                          : 'Check In Unavailable',
                      icon: Icons.login_rounded,
                      color: AppColors.green,
                      onPressed: null,
                    ),
                    const SizedBox(height: 10),
                    AppCard(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Icon(Icons.schedule_rounded, size: 18, color: AppColors.amber),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              checkInBlockedMessage,
                              style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: AppColors.muted,
                                height: 1.35,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ]
                  else if (checkedIn) ...[
                    _StateBanner(
                      icon: Icons.check_circle_rounded,
                      label: timeIn != null ? 'Checked in at $timeIn' : 'You\'re checked in',
                      color: AppColors.green,
                      background: AppColors.greenSoft,
                      border: AppColors.greenBorder,
                    ),
                    const SizedBox(height: 12),
                    _ActionButton(
                      label: canCheckOut
                          ? 'Check Out with Face + GPS'
                          : 'Check Out · Opens $checkoutAvailableAt',
                      icon: Icons.logout_rounded,
                      color: AppColors.red,
                      onPressed: canCheckOut ? _checkOut : null,
                    ),
                    if (!canCheckOut && checkoutBlockedMessage != null) ...[
                      const SizedBox(height: 10),
                      AppCard(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Icon(Icons.schedule_rounded, size: 18, color: AppColors.amber),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                checkoutBlockedMessage,
                                style: const TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w600,
                                  color: AppColors.muted,
                                  height: 1.35,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ] else if (timeOut != null)
                    _StateBanner(
                      icon: Icons.flag_rounded,
                      label: 'Shift complete · checked out at $timeOut',
                      color: AppColors.red,
                      background: const Color(0xFFFFEBEE),
                      border: const Color(0xFFFECACA),
                    )
                  else
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
                            child: const Icon(Icons.event_busy_rounded, color: AppColors.blue, size: 22),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              hasDeployment
                                  ? 'Your next shift is not open for check-in yet.'
                                  : 'No deployment assigned for today.',
                              style: TextStyle(
                                color: AppColors.muted,
                                fontWeight: FontWeight.w600,
                                height: 1.35,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  const SizedBox(height: 22),
                  const SectionLabel('Recent History'),
                  const SizedBox(height: 10),
                  if (history.isEmpty)
                    AppCard(
                      child: Text(
                        'No previous attendance records.',
                        style: TextStyle(color: AppColors.muted.withValues(alpha: 0.95)),
                      ),
                    )
                  else
                    ...history.map((raw) {
                      final item = (raw as Map).cast<String, dynamic>();
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: _HistoryCard(
                          date: item['date']?.toString() ?? '',
                          range: item['range']?.toString() ?? '',
                          duration: item['duration']?.toString() ?? '',
                          status: item['status']?.toString() ?? 'ON DUTY',
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
}

class _StatusCard extends StatelessWidget {
  const _StatusCard({
    required this.timeIn,
    required this.timeOut,
    required this.gps,
    required this.verified,
    this.faceVerified = false,
    this.faceEnrolled = false,
    this.shiftStart,
    this.shiftEnd,
    this.checkedIn = false,
    this.checkOutLateMinutes,
    this.checkInLateMinutes,
  });

  final String? timeIn;
  final String? timeOut;
  final String gps;
  final bool verified;
  final bool faceVerified;
  final bool faceEnrolled;
  final String? shiftStart;
  final String? shiftEnd;
  final bool checkedIn;
  final int? checkOutLateMinutes;
  final int? checkInLateMinutes;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Expanded(child: SectionLabel('Time In')),
              if (faceVerified || verified)
                Wrap(
                  spacing: 6,
                  children: [
                    if (faceVerified)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppColors.blueSoft,
                          borderRadius: BorderRadius.circular(999),
                          border: Border.all(color: AppColors.blueBorder),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.face_rounded, size: 12, color: AppColors.blue),
                            SizedBox(width: 4),
                            Text(
                              'FACE',
                              style: TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.w800,
                                color: AppColors.blue,
                              ),
                            ),
                          ],
                        ),
                      ),
                    if (verified)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppColors.greenSoft,
                          borderRadius: BorderRadius.circular(999),
                          border: Border.all(color: AppColors.greenBorder),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.verified_rounded, size: 12, color: AppColors.green),
                            SizedBox(width: 4),
                            Text(
                              'GPS',
                              style: TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.w800,
                                color: AppColors.green,
                              ),
                            ),
                          ],
                        ),
                      ),
                  ],
                )
              else if (faceEnrolled)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppColors.amberSoft,
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: const Text(
                    'FACE READY',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w800,
                      color: AppColors.amber,
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            timeIn ?? '—',
            style: TextStyle(
              fontSize: 48,
              fontWeight: FontWeight.w800,
              color: timeIn == null ? AppColors.muted : AppColors.green,
              height: 1,
              fontFeatures: const [FontFeature.tabularFigures()],
            ),
          ),
          if (timeOut != null) ...[
            const SizedBox(height: 18),
            const SectionLabel('Time Out'),
            const SizedBox(height: 8),
            Text(
              timeOut!,
              style: const TextStyle(
                fontSize: 34,
                fontWeight: FontWeight.w800,
                color: AppColors.red,
                height: 1,
                fontFeatures: [FontFeature.tabularFigures()],
              ),
            ),
          ],
          const SizedBox(height: 12),
          if (shiftStart != null && shiftEnd != null) ...[
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              decoration: BoxDecoration(
                color: AppColors.blueSoft,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                children: [
                  const Icon(Icons.access_time_rounded, size: 16, color: AppColors.blue),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Shift · $shiftStart – $shiftEnd',
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: AppColors.blue,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
          ],
          Text(
            timeOut != null
                ? checkOutLateMinutes != null && checkOutLateMinutes! > 0
                    ? 'Checked out ${_formatLateness(checkOutLateMinutes!)} after shift end'
                    : 'Checked out · ${verified ? 'GPS verified' : 'Recorded'}'
                : checkedIn
                    ? (checkInLateMinutes != null && checkInLateMinutes! > 0
                        ? 'On duty · checked in at $timeIn (${_formatLateness(checkInLateMinutes!)} late)'
                        : (timeIn != null ? 'On duty · checked in at $timeIn' : 'On duty'))
                    : timeIn != null
                        ? 'Checked in · ${verified ? 'GPS verified' : 'Recorded'}'
                        : 'Ready for check-in when you arrive at post',
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w500,
              color: AppColors.muted,
            ),
          ),
          const SizedBox(height: 14),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            decoration: BoxDecoration(
              color: AppColors.bg,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                const Icon(Icons.near_me_rounded, size: 16, color: AppColors.teal),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    gps,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: AppColors.teal,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _formatLateness(int minutes) {
    if (minutes < 60) {
      return '$minutes minute${minutes == 1 ? '' : 's'}';
    }

    final hours = minutes ~/ 60;
    final remaining = minutes % 60;

    if (remaining == 0) {
      return '$hours hour${hours == 1 ? '' : 's'}';
    }

    return '$hours hour${hours == 1 ? '' : 's'} $remaining minute${remaining == 1 ? '' : 's'}';
  }
}

class _StateBanner extends StatelessWidget {
  const _StateBanner({
    required this.icon,
    required this.label,
    required this.color,
    required this.background,
    required this.border,
  });

  final IconData icon;
  final String label;
  final Color color;
  final Color background;
  final Color border;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(AppRadius.sm),
        border: Border.all(color: border),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 8),
          Flexible(
            child: Text(
              label,
              textAlign: TextAlign.center,
              style: TextStyle(color: color, fontWeight: FontWeight.w800, fontSize: 14),
            ),
          ),
        ],
      ),
    );
  }
}

class _ActionButton extends StatelessWidget {
  const _ActionButton({
    required this.label,
    required this.icon,
    required this.color,
    required this.onPressed,
  });

  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    final enabled = onPressed != null;

    return SizedBox(
      width: double.infinity,
      child: FilledButton.icon(
        onPressed: onPressed,
        icon: Icon(icon, size: 20),
        label: Text(label.toUpperCase(), style: const TextStyle(letterSpacing: 0.8)),
        style: FilledButton.styleFrom(
          backgroundColor: enabled ? color : color.withValues(alpha: 0.35),
          foregroundColor: Colors.white,
          disabledBackgroundColor: color.withValues(alpha: 0.35),
          disabledForegroundColor: Colors.white.withValues(alpha: 0.85),
          padding: const EdgeInsets.symmetric(vertical: 16),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.sm)),
        ),
      ),
    );
  }
}

class _HistoryCard extends StatelessWidget {
  const _HistoryCard({
    required this.date,
    required this.range,
    required this.duration,
    required this.status,
  });

  final String date;
  final String range;
  final String duration;
  final String status;

  @override
  Widget build(BuildContext context) {
    final statusUpper = status.toUpperCase();
    final completed = statusUpper == 'COMPLETED' || statusUpper == 'PRESENT';
    final onDuty = statusUpper == 'ON DUTY';
    final badgeColor = completed
        ? AppColors.greenSoft
        : onDuty
            ? AppColors.blueSoft
            : AppColors.amberSoft;
    final badgeTextColor = completed
        ? AppColors.green
        : onDuty
            ? AppColors.blue
            : AppColors.amber;

    return AppCard(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
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
              completed
                  ? Icons.check_rounded
                  : onDuty
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
                  date,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                    color: AppColors.text,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  '$range · $duration',
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w500,
                    color: AppColors.muted,
                  ),
                ),
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
              status,
              style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.6,
                color: badgeTextColor,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ShiftPickerCard extends StatelessWidget {
  const _ShiftPickerCard({
    required this.post,
    required this.shiftRange,
    required this.scheduleLabel,
    required this.scheduleStatus,
    required this.selected,
    required this.onTap,
  });

  final String post;
  final String shiftRange;
  final String scheduleLabel;
  final String scheduleStatus;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final (badgeColor, badgeTextColor, borderColor) = switch (scheduleStatus) {
      'completed' => (AppColors.greenSoft, AppColors.green, AppColors.greenBorder),
      'on_duty' || 'late' => (AppColors.blueSoft, AppColors.blue, AppColors.blueBorder),
      'ready' => (AppColors.greenSoft, AppColors.green, AppColors.greenBorder),
      'upcoming' => (AppColors.amberSoft, AppColors.amber, const Color(0xFFFDE68A)),
      _ => (AppColors.bg, AppColors.muted, AppColors.border),
    };

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppRadius.md),
        child: AppCard(
          padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
          border: Border.all(
            color: selected ? AppColors.blue : borderColor,
            width: selected ? 2 : 1,
          ),
          color: selected ? AppColors.blueSoft.withValues(alpha: 0.45) : Colors.white,
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
                  selected ? Icons.radio_button_checked_rounded : Icons.location_on_rounded,
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
                      post,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: AppColors.text,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      shiftRange,
                      style: const TextStyle(
                        fontSize: 12.5,
                        fontWeight: FontWeight.w600,
                        color: AppColors.muted,
                      ),
                    ),
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
                  scheduleLabel.toUpperCase(),
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
        ),
      ),
    );
  }
}
