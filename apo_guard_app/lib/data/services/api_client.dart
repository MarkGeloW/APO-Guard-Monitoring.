import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

import 'session_store.dart';

class ApiConfig {
  /// Default when no saved URL and no --dart-define override.
  static String get defaultBaseUrl {
    if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
      return 'http://10.0.2.2:8010';
    }

    return 'http://127.0.0.1:8010';
  }

  static String normalizeBaseUrl(String raw) {
    var url = raw.trim();
    if (url.isEmpty) {
      throw ApiException('Server URL is required.');
    }

    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      url = 'http://$url';
    }

    final uri = Uri.tryParse(url);
    if (uri == null || uri.host.isEmpty) {
      throw ApiException('Enter a valid server URL (e.g. https://abc.ngrok-free.app).');
    }

    final portSuffix = uri.hasPort ? ':${uri.port}' : '';
    url = '${uri.scheme}://${uri.host}$portSuffix';

    return url;
  }

  static String resolveBaseUrl({String? savedUrl}) {
    const compileOverride = String.fromEnvironment('API_BASE_URL');
    if (compileOverride.isNotEmpty) {
      return normalizeBaseUrl(compileOverride);
    }

    if (savedUrl != null && savedUrl.trim().isNotEmpty) {
      return normalizeBaseUrl(savedUrl);
    }

    return defaultBaseUrl;
  }
}

class ApiException implements Exception {
  ApiException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}

class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();

  static const Duration requestTimeout = Duration(seconds: 15);

  static void Function(String message)? onSessionExpired;

  String? _token;
  Map<String, dynamic>? _user;
  String _baseUrl = ApiConfig.defaultBaseUrl;
  bool _authExitInProgress = false;

  static bool isConnectionError(ApiException error) => error.statusCode == null;

  String get baseUrl => _baseUrl;
  String get apiPrefix => '$_baseUrl/api/v1';

  String? get token => _token;
  Map<String, dynamic>? get user => _user;
  bool get isAuthenticated => _token != null && _token!.isNotEmpty;

  Future<void> init() async {
    await SessionStore.instance.init();
    final savedUrl = await SessionStore.instance.getApiBaseUrl();
    _baseUrl = ApiConfig.resolveBaseUrl(savedUrl: savedUrl);
    _token = await SessionStore.instance.getToken();
    _user = await SessionStore.instance.getUser();
  }

  Future<void> setBaseUrl(String raw) async {
    _baseUrl = ApiConfig.normalizeBaseUrl(raw);
    await SessionStore.instance.saveApiBaseUrl(_baseUrl);
  }

  Future<void> _persistSession(
    String token,
    Map<String, dynamic> user, {
    required bool remember,
  }) async {
    _token = token;
    _user = user;
    await SessionStore.instance.saveSession(
      token: token,
      user: user,
      remember: remember,
    );
  }

  Future<void> clearSession() async {
    _token = null;
    _user = null;
    await SessionStore.instance.clear();
  }

  Future<void> forceReturnToLogin(String message) async {
    if (_authExitInProgress) return;

    _authExitInProgress = true;
    try {
      await clearSession();
      onSessionExpired?.call(message);
    } finally {
      _authExitInProgress = false;
    }
  }

  Future<bool> handleUnreachableOnBootstrap(
    ApiException error, {
    required bool hasCachedData,
  }) async {
    if (error.statusCode == 401) {
      return true;
    }

    if (hasCachedData || !isConnectionError(error)) {
      return false;
    }

    await forceReturnToLogin(
      error.message.isNotEmpty
          ? error.message
          : 'Cannot connect to the server. Please sign in again.',
    );

    return true;
  }

  ApiException _connectionError(String message) => ApiException(message);

  Map<String, String> _headers({bool auth = true, bool json = true}) {
    final headers = <String, String>{
      'Accept': 'application/json',
      if (json) 'Content-Type': 'application/json',
      if (auth && _token != null) 'Authorization': 'Bearer $_token',
    };

    if (_baseUrl.contains('ngrok')) {
      headers['Ngrok-Skip-Browser-Warning'] = 'true';
    }

    return headers;
  }

  Future<Map<String, dynamic>> _sendMultipart(http.MultipartRequest request) async {
    try {
      final streamed = await request.send().timeout(requestTimeout);
      final response = await http.Response.fromStream(streamed);
      return await _decode(response);
    } on TimeoutException {
      throw _connectionError(
        'Cannot reach the server. Check your connection and server URL.',
      );
    } on SocketException {
      throw _connectionError(
        'Cannot connect to the server. Check the server URL and your network.',
      );
    } on http.ClientException catch (error) {
      throw _connectionError(
        error.message.isNotEmpty
            ? error.message
            : 'Network error while contacting the server.',
      );
    } on ApiException {
      rethrow;
    } catch (error) {
      throw ApiException('Unexpected error: $error');
    }
  }

  Future<Map<String, dynamic>> _send(Future<http.Response> Function() request) async {
    try {
      final response = await request().timeout(requestTimeout);
      return await _decode(response);
    } on TimeoutException {
      throw _connectionError(
        'Cannot reach the server. Check your connection and server URL.',
      );
    } on SocketException {
      throw _connectionError(
        'Cannot connect to the server. Check the server URL and your network.',
      );
    } on http.ClientException catch (error) {
      throw _connectionError(
        error.message.isNotEmpty
            ? error.message
            : 'Network error while contacting the server.',
      );
    } on ApiException {
      rethrow;
    } catch (error) {
      throw ApiException('Unexpected error: $error');
    }
  }

  Future<Map<String, dynamic>> _decode(http.Response response) async {
    Map<String, dynamic> body = {};
    if (response.body.isNotEmpty) {
      try {
        final decoded = jsonDecode(response.body);
        if (decoded is Map<String, dynamic>) {
          body = decoded;
        }
      } on FormatException {
        throw _connectionError('Invalid response from server.');
      }
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return body;
    }

    if (response.statusCode == 401) {
      await forceReturnToLogin('Your session has expired. Please sign in again.');
    }

    String message = body['message']?.toString() ?? 'Request failed.';
    if (body['errors'] is Map) {
      final errors = body['errors'] as Map;
      final first = errors.values.expand((e) => e is List ? e : [e]).firstOrNull;
      if (first != null) message = first.toString();
    }

    throw ApiException(message, statusCode: response.statusCode);
  }

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
    bool remember = true,
  }) async {
    final data = await _send(() => http.post(
          Uri.parse('$apiPrefix/login'),
          headers: _headers(auth: false),
          body: jsonEncode({
            'email': email.trim(),
            'password': password,
            'device_name': 'apo-guard-mobile',
          }),
        ));

    final token = data['token']?.toString();
    final user = data['user'];
    if (token == null || user is! Map<String, dynamic>) {
      throw ApiException('Invalid login response from server.');
    }

    await _persistSession(token, user, remember: remember);
    return data;
  }

  Future<void> logout() async {
    try {
      if (isAuthenticated) {
        await _send(() => http.post(
              Uri.parse('$apiPrefix/logout'),
              headers: _headers(),
            ));
      }
    } catch (_) {
      // Ignore network errors on logout; still clear local session.
    } finally {
      await clearSession();
    }
  }

  Future<Map<String, dynamic>> getDashboard() async {
    return _send(() => http.get(
          Uri.parse('$apiPrefix/dashboard'),
          headers: _headers(),
        ));
  }

  Future<Map<String, dynamic>> getAttendance({int? deploymentId}) async {
    final uri = deploymentId != null
        ? Uri.parse('$apiPrefix/attendance?deployment_id=$deploymentId')
        : Uri.parse('$apiPrefix/attendance');

    return _send(() => http.get(
          uri,
          headers: _headers(),
        ));
  }

  Future<Map<String, dynamic>> getFaceStatus() async {
    return _send(() => http.get(
          Uri.parse('$apiPrefix/face'),
          headers: _headers(),
        ));
  }

  Future<Map<String, dynamic>> enrollFace({
    required File faceImage,
    required List<double> faceSignature,
  }) async {
    final request = http.MultipartRequest('POST', Uri.parse('$apiPrefix/face/enroll'));
    request.headers.addAll(_headers(json: false));
    request.fields['face_signature'] = jsonEncode(faceSignature);
    request.files.add(await http.MultipartFile.fromPath('face_image', faceImage.path));
    return _sendMultipart(request);
  }

  Future<Map<String, dynamic>> checkIn({
    required double latitude,
    required double longitude,
    required File faceImage,
    required List<double> faceSignature,
    int? deploymentId,
  }) async {
    final request = http.MultipartRequest('POST', Uri.parse('$apiPrefix/attendance/check-in'));
    request.headers.addAll(_headers(json: false));
    request.fields['latitude'] = latitude.toString();
    request.fields['longitude'] = longitude.toString();
    request.fields['face_signature'] = jsonEncode(faceSignature);
    if (deploymentId != null) {
      request.fields['deployment_id'] = deploymentId.toString();
    }
    request.files.add(await http.MultipartFile.fromPath('face_image', faceImage.path));
    return _sendMultipart(request);
  }

  Future<Map<String, dynamic>> checkOut({
    required double latitude,
    required double longitude,
    required File faceImage,
    required List<double> faceSignature,
    int? deploymentId,
  }) async {
    final request = http.MultipartRequest('POST', Uri.parse('$apiPrefix/attendance/check-out'));
    request.headers.addAll(_headers(json: false));
    request.fields['latitude'] = latitude.toString();
    request.fields['longitude'] = longitude.toString();
    request.fields['face_signature'] = jsonEncode(faceSignature);
    if (deploymentId != null) {
      request.fields['deployment_id'] = deploymentId.toString();
    }
    request.files.add(await http.MultipartFile.fromPath('face_image', faceImage.path));
    return _sendMultipart(request);
  }

  Future<Map<String, dynamic>> sendDutyPings(List<Map<String, dynamic>> pings) async {
    return _send(() => http.post(
          Uri.parse('$apiPrefix/duty/pings'),
          headers: _headers(),
          body: jsonEncode({'pings': pings}),
        ));
  }

  Future<Map<String, dynamic>> getAlerts() async {
    return _send(() => http.get(
          Uri.parse('$apiPrefix/alerts'),
          headers: _headers(),
        ));
  }

  Future<void> markAlertRead(int alertId) async {
    await _send(() => http.patch(
          Uri.parse('$apiPrefix/alerts/$alertId/read'),
          headers: _headers(),
        ));
  }

  Future<void> markAllAlertsRead() async {
    await _send(() => http.post(
          Uri.parse('$apiPrefix/alerts/read-all'),
          headers: _headers(),
        ));
  }

  static int? parseIntOrNull(dynamic value) {
    if (value == null) return null;
    if (value is int) return value;
    if (value is num) return value.toInt();
    return int.tryParse(value.toString());
  }
}
