import 'dart:io';

import 'package:camera/camera.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/theme/app_theme.dart';
import '../../../data/services/face_signature.dart';

enum FaceScanMode { enroll, verify }

class FaceScanScreen extends StatefulWidget {
  const FaceScanScreen({
    super.key,
    required this.mode,
    this.title,
    this.subtitle,
  });

  final FaceScanMode mode;
  final String? title;
  final String? subtitle;

  @override
  State<FaceScanScreen> createState() => _FaceScanScreenState();
}

class _FaceScanScreenState extends State<FaceScanScreen> {
  CameraController? _controller;
  bool _initializing = true;
  bool _busy = false;
  String? _error;
  String? _hint;

  @override
  void initState() {
    super.initState();
    _initCamera();
  }

  Future<void> _initCamera() async {
    try {
      final cameras = await availableCameras();
      if (cameras.isEmpty) {
        if (!mounted) return;
        setState(() {
          _initializing = false;
          _error = 'No camera found on this device.';
        });
        return;
      }

      final front = cameras.firstWhere(
        (camera) => camera.lensDirection == CameraLensDirection.front,
        orElse: () => cameras.first,
      );

      final controller = CameraController(
        front,
        ResolutionPreset.medium,
        enableAudio: false,
        imageFormatGroup: ImageFormatGroup.jpeg,
      );

      await controller.initialize();
      if (!mounted) {
        await controller.dispose();
        return;
      }

      setState(() {
        _controller = controller;
        _initializing = false;
        _hint = widget.mode == FaceScanMode.enroll
            ? 'Center your face, then tap Capture to enroll.'
            : 'Center your face to verify your identity.';
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _initializing = false;
        _error = 'Unable to open camera. ${error.toString().replaceFirst('Exception: ', '')}';
      });
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  Future<void> _capture() async {
    final controller = _controller;
    if (controller == null || !controller.value.isInitialized || _busy) return;

    setState(() {
      _busy = true;
      _error = null;
      _hint = 'Scanning face…';
    });

    try {
      HapticFeedback.mediumImpact();
      final shot = await controller.takePicture();
      final file = File(shot.path);
      final face = await FaceSignatureBuilder.detectPrimaryFace(file);

      if (face == null) {
        await file.delete();
        throw Exception('No face detected. Hold the phone at eye level and try again.');
      }

      final quality = FaceSignatureBuilder.qualityMessage(face);
      if (quality != null) {
        await file.delete();
        throw Exception(quality);
      }

      final signature = FaceSignatureBuilder.buildSignature(face);
      if (signature == null || signature.length < 8) {
        await file.delete();
        throw Exception('Could not map facial landmarks. Improve lighting and try again.');
      }

      if (!mounted) return;
      Navigator.of(context).pop(FaceCaptureResult(imageFile: file, signature: signature));
    } catch (error) {
      if (!mounted) return;
      HapticFeedback.vibrate();
      setState(() {
        _busy = false;
        _error = error.toString().replaceFirst('Exception: ', '');
        _hint = 'Adjust position and try again.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final title = widget.title ??
        (widget.mode == FaceScanMode.enroll ? 'Enroll Face' : 'Verify Face');
    final subtitle = widget.subtitle ??
        (widget.mode == FaceScanMode.enroll
            ? 'Save your face once. It will be required for attendance.'
            : 'Face verification is required with GPS for attendance.');

    return Scaffold(
      backgroundColor: Colors.black,
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(8, 8, 8, 0),
              child: Row(
                children: [
                  IconButton(
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.close_rounded, color: Colors.white),
                  ),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          title,
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w800,
                            fontSize: 18,
                          ),
                        ),
                        Text(
                          subtitle,
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.7),
                            fontSize: 12,
                            height: 1.3,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(24),
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      if (_initializing)
                        const ColoredBox(
                          color: Color(0xFF111827),
                          child: Center(child: CircularProgressIndicator(color: Colors.white)),
                        )
                      else if (_controller != null && _controller!.value.isInitialized)
                        FittedBox(
                          fit: BoxFit.cover,
                          child: SizedBox(
                            width: _controller!.value.previewSize?.height ?? 1,
                            height: _controller!.value.previewSize?.width ?? 1,
                            child: CameraPreview(_controller!),
                          ),
                        )
                      else
                        ColoredBox(
                          color: const Color(0xFF111827),
                          child: Center(
                            child: Padding(
                              padding: const EdgeInsets.all(24),
                              child: Text(
                                _error ?? 'Camera unavailable',
                                textAlign: TextAlign.center,
                                style: const TextStyle(color: Colors.white70),
                              ),
                            ),
                          ),
                        ),
                      IgnorePointer(
                        child: CustomPaint(
                          painter: _FaceGuidePainter(),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
              child: Column(
                children: [
                  if (_hint != null)
                    Text(
                      _hint!,
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.85),
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  if (_error != null) ...[
                    const SizedBox(height: 8),
                    Text(
                      _error!,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Color(0xFFFCA5A5),
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                  const SizedBox(height: 18),
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton.icon(
                      onPressed: (_controller?.value.isInitialized == true && !_busy) ? _capture : null,
                      style: FilledButton.styleFrom(
                        backgroundColor: AppColors.blue,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      ),
                      icon: _busy
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                            )
                          : const Icon(Icons.face_retouching_natural_rounded),
                      label: Text(
                        _busy
                            ? 'Processing…'
                            : (widget.mode == FaceScanMode.enroll ? 'Capture & Enroll' : 'Capture & Verify'),
                        style: const TextStyle(fontWeight: FontWeight.w800),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _FaceGuidePainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final overlay = Paint()..color = Colors.black.withValues(alpha: 0.35);
    final clear = Path()
      ..addOval(
        Rect.fromCenter(
          center: Offset(size.width / 2, size.height * 0.42),
          width: size.width * 0.62,
          height: size.height * 0.48,
        ),
      );
    final full = Path()..addRect(Offset.zero & size);
    canvas.drawPath(
      Path.combine(PathOperation.difference, full, clear),
      overlay,
    );

    final ring = Paint()
      ..color = Colors.white.withValues(alpha: 0.85)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3;
    canvas.drawOval(
      Rect.fromCenter(
        center: Offset(size.width / 2, size.height * 0.42),
        width: size.width * 0.62,
        height: size.height * 0.48,
      ),
      ring,
    );
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
