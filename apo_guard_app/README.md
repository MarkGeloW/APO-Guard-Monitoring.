# APO Guard App (Flutter)

Mobile app for **Security Guards** in the APO Guard Monitoring System.

## Folder

```
apo_guard_app/
```

## Suggested structure

```
lib/
  core/           # theme, config, shared widgets
  data/           # models, API services, repositories
  features/
    auth/         # login
    home/         # guard dashboard
    deployments/  # today's assignments
    attendance/   # GPS check-in / check-out
    profile/      # guard profile
```

## Run

```bash
cd apo_guard_app
flutter pub get
flutter run
```

## API connection

The Flutter app talks to the Laravel backend via Sanctum tokens:

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/v1/login` | Guard login |
| POST | `/api/v1/logout` | Sign out |
| GET | `/api/v1/dashboard` | Guard dashboard |
| GET | `/api/v1/attendance` | Today's attendance |
| POST | `/api/v1/attendance/check-in` | GPS check-in |
| POST | `/api/v1/attendance/check-out` | GPS check-out |
| GET | `/api/v1/alerts` | Alerts feed |

### Base URL

- Android emulator: `http://10.0.2.2:8010`
- iOS simulator / desktop: `http://127.0.0.1:8010`
- Physical phone: your PC LAN IP, e.g. `http://192.168.1.10:8010`

Override with:

```bash
flutter run --dart-define=API_BASE_URL=http://192.168.1.10:8010
```

### Run backend + app

```bash
# Terminal 1 — Laravel API
cd ..
php artisan serve --port=8010

# Terminal 2 — Flutter
cd apo_guard_app
flutter run
```

Demo guard: `guard1@aposecurity.com` / `password`

