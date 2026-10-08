/// Philippines (PHT, UTC+8) helpers for display logic in the guard app.
class AppDateTime {
  AppDateTime._();

  static const Duration _philippinesOffset = Duration(hours: 8);

  static DateTime philippinesNow() {
    return DateTime.now().toUtc().add(_philippinesOffset);
  }
}
