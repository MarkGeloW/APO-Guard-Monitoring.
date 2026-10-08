import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/theme/app_theme.dart';
import '../../../data/services/api_client.dart';
import '../../home/screens/guard_shell.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, this.initialMessage});

  final String? initialMessage;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen>
    with SingleTickerProviderStateMixin {
  final _formKey = GlobalKey<FormState>();
  final _apiUrlController = TextEditingController();
  final _emailController = TextEditingController(text: 'guard1@aposecurity.com');
  final _passwordController = TextEditingController(text: 'password');

  late final AnimationController _anim;
  late final Animation<double> _fade;
  late final Animation<Offset> _slide;

  bool _obscure = true;
  bool _loading = false;
  bool _remember = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _error = widget.initialMessage;
    _apiUrlController.text = ApiClient.instance.baseUrl;
    _anim = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 700),
    );
    _fade = CurvedAnimation(parent: _anim, curve: Curves.easeOutCubic);
    _slide = Tween<Offset>(
      begin: const Offset(0, 0.08),
      end: Offset.zero,
    ).animate(CurvedAnimation(parent: _anim, curve: Curves.easeOutCubic));
    _anim.forward();
  }

  @override
  void dispose() {
    _anim.dispose();
    _apiUrlController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      await ApiClient.instance.setBaseUrl(_apiUrlController.text);
      await ApiClient.instance.login(
        email: _emailController.text,
        password: _passwordController.text,
        remember: _remember,
      );

      if (!mounted) return;

      Navigator.of(context).pushReplacement(
        PageRouteBuilder(
          pageBuilder: (context, animation, secondaryAnimation) => const GuardShell(),
          transitionsBuilder: (context, animation, secondaryAnimation, child) {
            return FadeTransition(opacity: animation, child: child);
          },
          transitionDuration: const Duration(milliseconds: 350),
        ),
      );
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() {
        _error =
            'Cannot reach server at ${ApiClient.instance.baseUrl}. Check the API URL and that Laravel/ngrok is running.';
      });
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.viewInsetsOf(context).bottom;

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        resizeToAvoidBottomInset: true,
        body: Stack(
          children: [
            Container(
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    Color(0xFF071833),
                    Color(0xFF0A3A9A),
                    Color(0xFF1B5FE8),
                  ],
                  stops: [0.0, 0.45, 1.0],
                ),
              ),
            ),
            Positioned(
              top: -80,
              right: -60,
              child: _GlowCircle(size: 220, color: Colors.white.withValues(alpha: 0.08)),
            ),
            Positioned(
              bottom: 180,
              left: -70,
              child: _GlowCircle(
                size: 180,
                color: const Color(0xFF60A5FA).withValues(alpha: 0.18),
              ),
            ),
            SafeArea(
              child: FadeTransition(
                opacity: _fade,
                child: SlideTransition(
                  position: _slide,
                  child: SingleChildScrollView(
                    padding: EdgeInsets.fromLTRB(20, 24, 20, 24 + bottomInset),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const SizedBox(height: 12),
                        _BrandHeader(),
                        const SizedBox(height: 28),
                        Center(
                          child: _LoginCard(
                            formKey: _formKey,
                            apiUrlController: _apiUrlController,
                            emailController: _emailController,
                            passwordController: _passwordController,
                            obscure: _obscure,
                            remember: _remember,
                            loading: _loading,
                            error: _error,
                            onToggleObscure: () {
                              setState(() => _obscure = !_obscure);
                            },
                            onToggleRemember: (value) {
                              setState(() => _remember = value);
                            },
                            onDemoTap: () {
                              _emailController.text = 'guard1@aposecurity.com';
                              _passwordController.text = 'password';
                            },
                            onSubmit: _login,
                          ),
                        ),
                        const SizedBox(height: 20),
                        Text(
                          'API: ${ApiClient.instance.baseUrl}',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.55),
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _BrandHeader extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Container(
          width: 78,
          height: 78,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: Colors.white.withValues(alpha: 0.12),
            border: Border.all(color: Colors.white.withValues(alpha: 0.28), width: 1.5),
          ),
          child: const Icon(Icons.shield, size: 38, color: Colors.white),
        ),
        const SizedBox(height: 18),
        const Text(
          'APO GUARD',
          textAlign: TextAlign.center,
          style: TextStyle(
            color: Colors.white,
            fontSize: 34,
            fontWeight: FontWeight.w900,
            letterSpacing: 1.4,
            height: 1,
          ),
        ),
        const SizedBox(height: 8),
        Text(
          'Monitoring System',
          textAlign: TextAlign.center,
          style: TextStyle(
            color: Colors.white.withValues(alpha: 0.78),
            fontSize: 14,
            fontWeight: FontWeight.w600,
            letterSpacing: 2.2,
          ),
        ),
        const SizedBox(height: 16),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(999),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 8,
                height: 8,
                decoration: const BoxDecoration(
                  color: Color(0xFF4ADE80),
                  shape: BoxShape.circle,
                ),
              ),
              const SizedBox(width: 8),
              Text(
                'SECURITY GUARD ACCESS',
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.92),
                  fontSize: 11,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 1.1,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _LoginCard extends StatelessWidget {
  const _LoginCard({
    required this.formKey,
    required this.apiUrlController,
    required this.emailController,
    required this.passwordController,
    required this.obscure,
    required this.remember,
    required this.loading,
    required this.error,
    required this.onToggleObscure,
    required this.onToggleRemember,
    required this.onSubmit,
    this.onDemoTap,
  });

  final GlobalKey<FormState> formKey;
  final TextEditingController apiUrlController;
  final TextEditingController emailController;
  final TextEditingController passwordController;
  final bool obscure;
  final bool remember;
  final bool loading;
  final String? error;
  final VoidCallback onToggleObscure;
  final ValueChanged<bool> onToggleRemember;
  final VoidCallback onSubmit;
  final VoidCallback? onDemoTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      constraints: const BoxConstraints(maxWidth: 420),
      padding: const EdgeInsets.fromLTRB(22, 24, 22, 22),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(28),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.22),
            blurRadius: 36,
            offset: const Offset(0, 18),
          ),
        ],
      ),
      child: Form(
        key: formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text(
              'Welcome back',
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.w800,
                color: AppColors.text,
              ),
            ),
            const SizedBox(height: 6),
            const Text(
              'Sign in with your guard credentials to start your shift.',
              style: TextStyle(
                fontSize: 13.5,
                height: 1.4,
                color: AppColors.muted,
                fontWeight: FontWeight.w500,
              ),
            ),
            if (error != null) ...[
              const SizedBox(height: 14),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFFFEBEE),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFFECACA)),
                ),
                child: Text(
                  error!,
                  style: const TextStyle(
                    color: AppColors.red,
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                    height: 1.35,
                  ),
                ),
              ),
            ],
            const SizedBox(height: 22),
            const _FieldLabel(text: 'API Server URL'),
            const SizedBox(height: 8),
            TextFormField(
              controller: apiUrlController,
              keyboardType: TextInputType.url,
              textInputAction: TextInputAction.next,
              autocorrect: false,
              enableSuggestions: false,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'Server URL is required';
                }
                try {
                  ApiConfig.normalizeBaseUrl(value);
                } on ApiException catch (e) {
                  return e.message;
                }
                return null;
              },
              decoration: _inputDecoration(
                hint: 'https://xxxx.ngrok-free.app',
                prefix: Icons.dns_rounded,
              ),
            ),
            const SizedBox(height: 6),
            const Text(
              'Use ngrok, LAN IP, or localhost. Do not add /api/v1.',
              style: TextStyle(
                fontSize: 11.5,
                height: 1.35,
                color: AppColors.muted,
                fontWeight: FontWeight.w500,
              ),
            ),
            const SizedBox(height: 16),
            const _FieldLabel(text: 'Email'),
            const SizedBox(height: 8),
            TextFormField(
              controller: emailController,
              keyboardType: TextInputType.emailAddress,
              textInputAction: TextInputAction.next,
              validator: (value) {
                if (value == null || value.trim().isEmpty) return 'Email is required';
                if (!value.contains('@')) return 'Enter a valid email';
                return null;
              },
              decoration: _inputDecoration(
                hint: 'guard@aposecurity.com',
                prefix: Icons.mail_outline_rounded,
              ),
            ),
            const SizedBox(height: 14),
            const _FieldLabel(text: 'Password'),
            const SizedBox(height: 8),
            TextFormField(
              controller: passwordController,
              obscureText: obscure,
              textInputAction: TextInputAction.done,
              onFieldSubmitted: (_) => onSubmit(),
              validator: (value) {
                if (value == null || value.isEmpty) return 'Password is required';
                return null;
              },
              decoration: _inputDecoration(
                hint: 'Enter your password',
                prefix: Icons.lock_outline_rounded,
                suffix: IconButton(
                  onPressed: onToggleObscure,
                  icon: Icon(
                    obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                    color: AppColors.muted,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                SizedBox(
                  height: 24,
                  width: 24,
                  child: Checkbox(
                    value: remember,
                    onChanged: (value) => onToggleRemember(value ?? false),
                    activeColor: AppColors.blue,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(5)),
                  ),
                ),
                const SizedBox(width: 8),
                const Expanded(
                  child: Text(
                    'Remember me',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.text),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 18),
            SizedBox(
              height: 54,
              child: FilledButton(
                onPressed: loading ? null : onSubmit,
                style: FilledButton.styleFrom(
                  backgroundColor: AppColors.blue,
                  disabledBackgroundColor: AppColors.blue.withValues(alpha: 0.6),
                  foregroundColor: Colors.white,
                  elevation: 0,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                ),
                child: loading
                    ? const SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white),
                      )
                    : const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text('Sign In', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                          SizedBox(width: 8),
                          Icon(Icons.arrow_forward_rounded, size: 20),
                        ],
                      ),
              ),
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppColors.blueSoft,
                borderRadius: BorderRadius.circular(14),
              ),
              child: InkWell(
                onTap: onDemoTap,
                borderRadius: BorderRadius.circular(10),
                child: const Row(
                  children: [
                    Icon(Icons.info_outline_rounded, size: 18, color: AppColors.blue),
                    SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        'Demo: guard1@aposecurity.com / password',
                        style: TextStyle(
                          fontSize: 12.5,
                          fontWeight: FontWeight.w600,
                          color: AppColors.blue,
                          height: 1.3,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  InputDecoration _inputDecoration({
    required String hint,
    required IconData prefix,
    Widget? suffix,
  }) {
    return InputDecoration(
      hintText: hint,
      hintStyle: const TextStyle(color: Color(0xFFA0AAB8), fontWeight: FontWeight.w500),
      prefixIcon: Icon(prefix, color: AppColors.muted),
      suffixIcon: suffix,
      filled: true,
      fillColor: const Color(0xFFF7F9FC),
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 16),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: AppColors.blue, width: 1.6),
      ),
    );
  }
}

class _FieldLabel extends StatelessWidget {
  const _FieldLabel({required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Text(
      text.toUpperCase(),
      style: const TextStyle(
        fontSize: 11,
        fontWeight: FontWeight.w800,
        letterSpacing: 1.1,
        color: AppColors.muted,
      ),
    );
  }
}

class _GlowCircle extends StatelessWidget {
  const _GlowCircle({required this.size, required this.color});

  final double size;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(shape: BoxShape.circle, color: color),
    );
  }
}
