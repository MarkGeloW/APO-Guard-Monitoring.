import 'dart:io';

import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';

class FaceCaptureResult {
  const FaceCaptureResult({
    required this.imageFile,
    required this.signature,
  });

  final File imageFile;
  final List<double> signature;
}

class FaceSignatureBuilder {
  static const landmarkOrder = <FaceLandmarkType>[
    FaceLandmarkType.leftEye,
    FaceLandmarkType.rightEye,
    FaceLandmarkType.noseBase,
    FaceLandmarkType.bottomMouth,
    FaceLandmarkType.leftMouth,
    FaceLandmarkType.rightMouth,
    FaceLandmarkType.leftCheek,
    FaceLandmarkType.rightCheek,
  ];

  static FaceDetector createDetector() {
    return FaceDetector(
      options: FaceDetectorOptions(
        enableLandmarks: true,
        enableContours: false,
        enableClassification: true,
        performanceMode: FaceDetectorMode.accurate,
        minFaceSize: 0.25,
      ),
    );
  }

  static Future<Face?> detectPrimaryFace(File imageFile) async {
    final detector = createDetector();
    try {
      final input = InputImage.fromFile(imageFile);
      final faces = await detector.processImage(input);
      if (faces.isEmpty) return null;

      faces.sort((a, b) {
        final aArea = a.boundingBox.width * a.boundingBox.height;
        final bArea = b.boundingBox.width * b.boundingBox.height;
        return bArea.compareTo(aArea);
      });

      return faces.first;
    } finally {
      await detector.close();
    }
  }

  static List<double>? buildSignature(Face face) {
    final box = face.boundingBox;
    if (box.width <= 1 || box.height <= 1) return null;

    final signature = <double>[];

    for (final type in landmarkOrder) {
      final landmark = face.landmarks[type];
      if (landmark == null) return null;

      signature.add(((landmark.position.x - box.left) / box.width).clamp(0.0, 1.0));
      signature.add(((landmark.position.y - box.top) / box.height).clamp(0.0, 1.0));
    }

    final yaw = ((face.headEulerAngleY ?? 0) + 45) / 90;
    final roll = ((face.headEulerAngleZ ?? 0) + 45) / 90;
    signature.add(yaw.clamp(0.0, 1.0));
    signature.add(roll.clamp(0.0, 1.0));

    return signature;
  }

  static String? qualityMessage(Face face) {
    if (face.boundingBox.width < 120 || face.boundingBox.height < 120) {
      return 'Move closer so your face fills the frame.';
    }

    final yaw = (face.headEulerAngleY ?? 0).abs();
    final roll = (face.headEulerAngleZ ?? 0).abs();
    if (yaw > 25 || roll > 25) {
      return 'Look straight at the camera.';
    }

    final leftEye = face.leftEyeOpenProbability;
    final rightEye = face.rightEyeOpenProbability;
    if (leftEye != null && rightEye != null && leftEye < 0.3 && rightEye < 0.3) {
      return 'Keep your eyes open for face verification.';
    }

    return null;
  }
}
