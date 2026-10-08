import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Persists auth session. Falls back to memory if native plugin isn't ready
/// (common right after adding packages until a full app restart/rebuild).
class SessionStore {
  SessionStore._();
  static final SessionStore instance = SessionStore._();

  static const _tokenKey = 'auth_token';
  static const _userKey = 'auth_user';
  static const _apiBaseUrlKey = 'api_base_url';
  static const _rememberKey = 'remember_me';
  static const dashboardCacheKey = 'dashboard_cache';
  static const dutyQueueKey = 'duty_ping_queue';
  static const dutyLastSentKey = 'duty_last_sent_at';

  SharedPreferences? _prefs;
  final Map<String, String> _memory = {};
  bool _useMemoryOnly = false;

  Future<void> init() async {
    if (_useMemoryOnly) return;

    try {
      _prefs = await SharedPreferences.getInstance();
    } on MissingPluginException {
      _useMemoryOnly = true;
      _prefs = null;
      debugPrint(
        'shared_preferences plugin missing — using in-memory session. '
        'Stop the app completely and run a full rebuild (not hot reload).',
      );
    } catch (e) {
      _useMemoryOnly = true;
      _prefs = null;
      debugPrint('shared_preferences unavailable ($e) — using in-memory session.');
    }
  }

  Future<bool> isRememberMeEnabled() async {
    await init();
    if (_prefs != null) {
      return _prefs!.getBool(_rememberKey) ?? false;
    }
    return _memory[_rememberKey] == 'true';
  }

  Future<String?> getToken() async {
    await init();
    if (!await isRememberMeEnabled()) {
      return _memory[_tokenKey];
    }
    if (_prefs != null) return _prefs!.getString(_tokenKey);
    return _memory[_tokenKey];
  }

  Future<String?> getApiBaseUrl() async {
    await init();
    if (_prefs != null) return _prefs!.getString(_apiBaseUrlKey);
    return _memory[_apiBaseUrlKey];
  }

  Future<void> saveApiBaseUrl(String url) async {
    await init();

    if (_prefs != null) {
      try {
        await _prefs!.setString(_apiBaseUrlKey, url);
        return;
      } on MissingPluginException {
        _useMemoryOnly = true;
        _prefs = null;
      }
    }

    _memory[_apiBaseUrlKey] = url;
  }

  Future<Map<String, dynamic>?> getUser() async {
    await init();
    if (!await isRememberMeEnabled()) {
      final raw = _memory[_userKey];
      if (raw == null || raw.isEmpty) return null;
      try {
        return jsonDecode(raw) as Map<String, dynamic>;
      } catch (_) {
        return null;
      }
    }

    final raw = _prefs != null ? _prefs!.getString(_userKey) : _memory[_userKey];
    if (raw == null || raw.isEmpty) return null;
    try {
      return jsonDecode(raw) as Map<String, dynamic>;
    } catch (_) {
      return null;
    }
  }

  Future<void> saveSession({
    required String token,
    required Map<String, dynamic> user,
    required bool remember,
  }) async {
    await init();
    final encodedUser = jsonEncode(user);

    _memory[_tokenKey] = token;
    _memory[_userKey] = encodedUser;
    _memory[_rememberKey] = remember.toString();

    if (_prefs != null) {
      try {
        await _prefs!.setBool(_rememberKey, remember);
        if (remember) {
          await _prefs!.setString(_tokenKey, token);
          await _prefs!.setString(_userKey, encodedUser);
        } else {
          await _prefs!.remove(_tokenKey);
          await _prefs!.remove(_userKey);
        }
        return;
      } on MissingPluginException {
        _useMemoryOnly = true;
        _prefs = null;
      }
    }
  }

  Future<void> clear() async {
    await init();
    final savedApiUrl = _memory[_apiBaseUrlKey];
    _memory.remove(_tokenKey);
    _memory.remove(_userKey);
    _memory.remove(_rememberKey);

    if (_prefs != null) {
      try {
        await _prefs!.remove(_tokenKey);
        await _prefs!.remove(_userKey);
        await _prefs!.remove(_rememberKey);
      } on MissingPluginException {
        _useMemoryOnly = true;
        _prefs = null;
      }
    }

    if (savedApiUrl != null) {
      _memory[_apiBaseUrlKey] = savedApiUrl;
    }

    await remove(dashboardCacheKey);
    await remove(dutyQueueKey);
    await remove(dutyLastSentKey);
  }

  Future<String?> readString(String key) async {
    await init();
    if (_prefs != null) return _prefs!.getString(key);
    return _memory[key];
  }

  Future<void> writeString(String key, String value) async {
    await init();
    _memory[key] = value;
    if (_prefs != null) {
      try {
        await _prefs!.setString(key, value);
      } on MissingPluginException {
        _useMemoryOnly = true;
        _prefs = null;
      }
    }
  }

  Future<void> remove(String key) async {
    await init();
    _memory.remove(key);
    if (_prefs != null) {
      try {
        await _prefs!.remove(key);
      } on MissingPluginException {
        _useMemoryOnly = true;
        _prefs = null;
      }
    }
  }
}
