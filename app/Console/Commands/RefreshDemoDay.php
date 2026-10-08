<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Deployment;
use App\Models\Location;
use App\Models\SecurityPersonnel;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Console\Command;

class RefreshDemoDay extends Command
{
    protected $signature = 'demo:refresh-day {--force : Recreate even if today already has deployments}';

    protected $description = 'Ensure demo guards have deployments, attendance, and sample alerts for today';

    public function handle(): int
    {
        $personnel = SecurityPersonnel::orderBy('id')->get();
        $locations = Location::where('status', 'active')->orderBy('id')->get();
        $supervisor = User::where('role', UserRole::Supervisor)->first();
        $admin = User::where('role', UserRole::Admin)->first();

        if ($personnel->isEmpty() || $locations->isEmpty() || ! $supervisor) {
            $this->error('Missing demo users/locations. Run: php artisan migrate:fresh --seed');

            return self::FAILURE;
        }

        $existingToday = Deployment::whereDate('deployment_date', today())->count();

        if ($existingToday > 0 && ! $this->option('force')) {
            $this->info("Today already has {$existingToday} deployment(s). Use --force to recreate.");

            return self::SUCCESS;
        }

        if ($this->option('force') && $existingToday > 0) {
            Deployment::whereDate('deployment_date', today())->each(function (Deployment $deployment) {
                $deployment->attendance()?->delete();
                $deployment->delete();
            });
        }

        $personnel->each(function (SecurityPersonnel $person, int $index) use ($locations, $supervisor) {
            $location = $locations[$index % $locations->count()];

            $deployment = Deployment::create([
                'security_personnel_id' => $person->id,
                'location_id' => $location->id,
                'assigned_by' => $supervisor->id,
                'deployment_date' => today(),
                'shift_start' => '06:00',
                'shift_end' => '18:00',
                'status' => match ($index) {
                    0, 1, 2 => 'active',
                    default => 'scheduled',
                },
                'notes' => 'Daily demo assignment',
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
                'security_personnel_id' => $person->id,
                ...$attendanceData,
            ]);
        });

        if ($admin && $supervisor) {
            $samples = [
                [
                    'type' => 'attendance_absent',
                    'title' => 'Guard Absent',
                    'message' => 'Leonardo Aquino is ABSENT – Provincial Capitol post unattended',
                    'time' => [6, 15],
                ],
                [
                    'type' => 'attendance_late',
                    'title' => 'Late Check-In',
                    'message' => 'Jose Reyes reported 42 minutes late at City Hall Gate 1',
                    'time' => [6, 43],
                ],
                [
                    'type' => 'attendance_checkin',
                    'title' => 'Guard Checked In',
                    'message' => 'Ramon Torres checked in at Petron Station – on time',
                    'time' => [6, 1],
                ],
            ];

            foreach ($samples as $sample) {
                foreach ([$admin->id, $supervisor->id] as $userId) {
                    SystemNotification::create([
                        'user_id' => $userId,
                        'type' => $sample['type'],
                        'title' => $sample['title'],
                        'message' => $sample['message'],
                        'created_at' => today()->setTime($sample['time'][0], $sample['time'][1]),
                        'updated_at' => today()->setTime($sample['time'][0], $sample['time'][1]),
                    ]);
                }
            }
        }

        $this->info('Demo day refreshed for '.today()->toDateString().' ('.$personnel->count().' deployments).');

        return self::SUCCESS;
    }
}
