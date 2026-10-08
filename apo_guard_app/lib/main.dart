import 'package:flutter/material.dart';

import 'core/theme/app_theme.dart';
import 'data/services/api_client.dart';
import 'features/auth/screens/login_screen.dart';
import 'features/home/screens/guard_shell.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await ApiClient.instance.init();
  runApp(const ApoGuardApp());
}

class ApoGuardApp extends StatelessWidget {
  const ApoGuardApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'APO Guard',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light(),
      home: ApiClient.instance.isAuthenticated
          ? const GuardShell()
          : const LoginScreen(),
    );
  }
}
