import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:apo_guard_app/data/services/api_client.dart';
import 'package:apo_guard_app/main.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    await ApiClient.instance.clearSession();
    await ApiClient.instance.init();
  });

  testWidgets('APO Guard login screen loads when logged out', (WidgetTester tester) async {
    await tester.pumpWidget(const ApoGuardApp());
    await tester.pumpAndSettle();

    expect(find.text('APO GUARD'), findsOneWidget);
    expect(find.text('Welcome back'), findsOneWidget);
    expect(find.text('Sign In'), findsOneWidget);
  });
}
