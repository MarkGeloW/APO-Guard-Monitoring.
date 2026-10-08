<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Deployment;
use App\Models\Location;
use App\Models\SecurityPersonnel;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@aposecurity.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'phone' => '09171234567',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $supervisor = User::create([
            'name' => 'Miguel Santos',
            'email' => 'supervisor@aposecurity.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Supervisor,
            'phone' => '09181234567',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $guardUsers = collect([
            ['Juan', 'Dela Cruz', 'guard1@aposecurity.com', 'SG-001'],
            ['Pedro', 'Reyes', 'guard2@aposecurity.com', 'SG-002'],
            ['Ana', 'Garcia', 'guard3@aposecurity.com', 'SG-003'],
            ['Mark', 'Lopez', 'guard4@aposecurity.com', 'SG-004'],
        ])->map(function ($data, $index) {
            [$first, $last, $email, $badge] = $data;

            $user = User::create([
                'name' => "{$first} {$last}",
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => UserRole::Guard,
                'phone' => '0919'.str_pad((string) ($index + 1000000), 7, '0', STR_PAD_LEFT),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            return SecurityPersonnel::create([
                'user_id' => $user->id,
                'badge_number' => $badge,
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'phone' => $user->phone,
                'address' => 'Kidapawan City, North Cotabato',
                'date_hired' => now()->subMonths(6 + $index),
                'status' => 'active',
                'emergency_contact' => 'Emergency Contact',
                'emergency_phone' => '0917000000'.$index,
            ]);
        });

        $locations = collect([
            [
                'name' => 'Kidapawan City Hall',
                'client_name' => 'Kidapawan LGU',
                'address' => 'Quezon Blvd, Kidapawan City, North Cotabato',
                'latitude' => 7.0083,
                'longitude' => 125.0928,
                'geofence_radius' => 150,
            ],
            [
                'name' => 'Gaisano Grand Kidapawan',
                'client_name' => 'Gaisano Grand Mall',
                'address' => 'National Highway, Kidapawan City',
                'latitude' => 7.0125,
                'longitude' => 125.0891,
                'geofence_radius' => 200,
            ],
            [
                'name' => 'North Cotabato Provincial Capitol',
                'client_name' => 'Provincial Government',
                'address' => 'Amas, Kidapawan City, North Cotabato',
                'latitude' => 7.0156,
                'longitude' => 125.0854,
                'geofence_radius' => 150,
            ],
            [
                'name' => 'Apo Security Agency HQ',
                'client_name' => 'Apo Security Agency',
                'address' => 'Poblacion, Sinsuat, Kidapawan City, North Cotabato',
                'latitude' => 7.0064,
                'longitude' => 125.0889,
                'geofence_radius' => 100,
            ],
        ])->map(fn ($data) => Location::create([...$data, 'status' => 'active']));

        $guardUsers->each(function (SecurityPersonnel $personnel, $index) use ($locations, $supervisor) {
            $location = $locations[$index % $locations->count()];

            $deployment = Deployment::create([
                'security_personnel_id' => $personnel->id,
                'location_id' => $location->id,
                'assigned_by' => $supervisor->id,
                'deployment_date' => today(),
                'shift_start' => '06:00',
                'shift_end' => '18:00',
                'status' => match ($index) {
                    0, 1, 2 => 'active',
                    default => 'scheduled',
                },
                'notes' => 'Regular day shift assignment',
            ]);

            $attendanceData = match ($index) {
                0 => [
                    'time_in' => today()->setTime(6, 0),
                    'time_in_latitude' => $location->latitude,
                    'time_in_longitude' => $location->longitude,
                    'time_in_distance_meters' => 12,
                    'time_in_verified' => true,
                    'status' => 'present',
                ],
                1 => [
                    'time_in' => today()->setTime(6, 5),
                    'time_in_latitude' => $location->latitude,
                    'time_in_longitude' => $location->longitude,
                    'time_in_distance_meters' => 8,
                    'time_in_verified' => true,
                    'status' => 'present',
                ],
                2 => [
                    'time_in' => today()->setTime(6, 42),
                    'time_in_latitude' => $location->latitude,
                    'time_in_longitude' => $location->longitude,
                    'time_in_distance_meters' => 24,
                    'time_in_verified' => true,
                    'status' => 'late',
                ],
                default => [
                    'status' => 'pending',
                ],
            };

            Attendance::create([
                'deployment_id' => $deployment->id,
                'security_personnel_id' => $personnel->id,
                ...$attendanceData,
            ]);
        });

        collect([
            [
                'type' => 'gps_lost',
                'title' => 'GPS Signal Lost',
                'message' => 'GPS signal lost for Eduardo Lim (15 min ago)',
                'created_at' => today()->setTime(7, 12),
            ],
            [
                'type' => 'deployment_schedule',
                'title' => 'Deployment Schedule',
                'message' => 'Night shift deployment schedule generated for '.today()->addDay()->format('M j, Y'),
                'created_at' => today()->setTime(5, 0),
            ],
            [
                'type' => 'system_backup',
                'title' => 'System Backup',
                'message' => 'System backup completed successfully',
                'created_at' => today()->subDay()->setTime(23, 30),
            ],
        ])->each(function (array $alert) use ($admin, $supervisor) {
            foreach ([$admin->id, $supervisor->id] as $userId) {
                SystemNotification::create([
                    'user_id' => $userId,
                    'type' => $alert['type'],
                    'title' => $alert['title'],
                    'message' => $alert['message'],
                    'created_at' => $alert['created_at'],
                    'updated_at' => $alert['created_at'],
                ]);
            }
        });

        $this->command?->info('Demo accounts seeded (password: password)');
        $this->command?->info('Admin: admin@aposecurity.com');
        $this->command?->info('Supervisor: supervisor@aposecurity.com');
        $this->command?->info('Guard: guard1@aposecurity.com');
    }
}
