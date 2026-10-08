import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class AppColors {
  static const Color navy = Color(0xFF0B3A8C);
  static const Color blue = Color(0xFF1E5BE0);
  static const Color blueSoft = Color(0xFFE8F0FF);
  static const Color bg = Color(0xFFF3F6FB);
  static const Color text = Color(0xFF0F1B2D);
  static const Color muted = Color(0xFF8A94A6);
  static const Color green = Color(0xFF16A34A);
  static const Color greenSoft = Color(0xFFE9F9EF);
  static const Color greenBorder = Color(0xFFB7E8C7);
  static const Color blueBorder = Color(0xFFC9DBFF);
  static const Color red = Color(0xFFE11D48);
  static const Color amber = Color(0xFFF59E0B);
  static const Color amberSoft = Color(0xFFFFF8E8);
  static const Color teal = Color(0xFF0D9488);
  static const Color border = Color(0xFFE6EBF3);
}

class AppRadius {
  static const double sm = 12;
  static const double md = 16;
  static const double lg = 20;
  static const double xl = 28;
}

class AppShadows {
  static List<BoxShadow> get card => [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.05),
          blurRadius: 16,
          offset: const Offset(0, 4),
        ),
      ];

  static List<BoxShadow> get float => [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.08),
          blurRadius: 24,
          offset: const Offset(0, 8),
        ),
      ];
}

class AppTheme {
  static ThemeData light() {
    final scheme = ColorScheme.fromSeed(
      seedColor: AppColors.blue,
      brightness: Brightness.light,
      surface: Colors.white,
    );

    return ThemeData(
      useMaterial3: true,
      scaffoldBackgroundColor: AppColors.bg,
      colorScheme: scheme,
      fontFamily: 'Roboto',
      splashFactory: InkSparkle.splashFactory,
      appBarTheme: const AppBarTheme(
        elevation: 0,
        scrolledUnderElevation: 0,
        backgroundColor: Colors.transparent,
        foregroundColor: AppColors.text,
        systemOverlayStyle: SystemUiOverlayStyle.dark,
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.sm)),
        contentTextStyle: const TextStyle(fontWeight: FontWeight.w700),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.sm)),
          textStyle: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
        ),
      ),
      textTheme: const TextTheme(
        headlineMedium: TextStyle(
          fontSize: 28,
          fontWeight: FontWeight.w800,
          color: AppColors.text,
          height: 1.12,
        ),
        titleMedium: TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w800,
          color: AppColors.text,
        ),
        bodyMedium: TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.w500,
          color: AppColors.muted,
          height: 1.4,
        ),
      ),
    );
  }
}
