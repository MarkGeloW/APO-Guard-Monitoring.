import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

enum AttendanceStep { pending, checkedIn, completed }

class AttendanceStepper extends StatelessWidget {
  const AttendanceStepper({super.key, required this.step});

  final AttendanceStep step;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        _StepNode(
          label: 'Check In',
          icon: Icons.login_rounded,
          active: step.index >= 0,
          done: step.index > 0,
        ),
        _Connector(active: step.index >= 1),
        _StepNode(
          label: 'On Duty',
          icon: Icons.verified_user_rounded,
          active: step.index >= 1,
          done: step.index > 1,
        ),
        _Connector(active: step.index >= 2),
        _StepNode(
          label: 'Check Out',
          icon: Icons.logout_rounded,
          active: step.index >= 2,
          done: step == AttendanceStep.completed,
        ),
      ],
    );
  }
}

class _Connector extends StatelessWidget {
  const _Connector({required this.active});

  final bool active;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        height: 3,
        margin: const EdgeInsets.only(bottom: 18),
        decoration: BoxDecoration(
          color: active ? AppColors.blue : const Color(0xFFE2E8F0),
          borderRadius: BorderRadius.circular(999),
        ),
      ),
    );
  }
}

class _StepNode extends StatelessWidget {
  const _StepNode({
    required this.label,
    required this.icon,
    required this.active,
    required this.done,
  });

  final String label;
  final IconData icon;
  final bool active;
  final bool done;

  @override
  Widget build(BuildContext context) {
    final color = done
        ? AppColors.green
        : active
            ? AppColors.blue
            : AppColors.muted;

    return Column(
      children: [
        Container(
          width: 34,
          height: 34,
          decoration: BoxDecoration(
            color: active ? color.withValues(alpha: 0.12) : const Color(0xFFF1F5F9),
            shape: BoxShape.circle,
            border: Border.all(
              color: active ? color : const Color(0xFFE2E8F0),
              width: 1.5,
            ),
          ),
          child: Icon(
            done ? Icons.check_rounded : icon,
            size: 16,
            color: color,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          label,
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 10,
            fontWeight: FontWeight.w800,
            color: active ? AppColors.text : AppColors.muted,
          ),
        ),
      ],
    );
  }
}
