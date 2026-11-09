<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BreakGlassAccess extends Command
{
    protected $signature = 'apo:break-glass
                            {--secret= : Break-glass secret from BREAK_GLASS_SECRET}
                            {--password= : Optional temporary password (random if omitted)}
                            {--restore-primary : Also restore the primary admin@aposecurity.com account}';

    protected $description = 'Emergency administrator access: unlock or create the break-glass admin account';

    public function handle(): int
    {
        $configuredSecret = (string) config('apo.break_glass.secret');

        if ($configuredSecret === '') {
            $this->error('BREAK_GLASS_SECRET is not set in .env. Add a long random secret first.');

            return self::FAILURE;
        }

        $providedSecret = (string) ($this->option('secret') ?: $this->secret('Enter break-glass secret'));

        if (! hash_equals($configuredSecret, $providedSecret)) {
            $this->error('Invalid break-glass secret.');
            Log::warning('Break-glass access denied: invalid secret.', [
                'ip' => gethostname(),
            ]);

            return self::FAILURE;
        }

        $email = (string) config('apo.break_glass.email', 'emergency@aposecurity.com');
        $name = (string) config('apo.break_glass.name', 'Emergency Administrator');
        $plainPassword = (string) ($this->option('password') ?: Str::password(16));

        $user = User::withTrashed()->where('email', $email)->first();
        $created = false;

        if (! $user) {
            $user = new User;
            $created = true;
        }

        if ($user->trashed()) {
            $user->restore();
        }

        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $plainPassword,
            'role' => UserRole::Admin,
            'is_active' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'remember_token' => null,
        ])->save();

        $user->tokens()->delete();

        if ($this->option('restore-primary')) {
            $this->restorePrimaryAdmin();
        }

        Log::warning('Break-glass administrator access was unlocked.', [
            'email' => $email,
            'created' => $created,
            'user_id' => $user->id,
        ]);

        $this->newLine();
        $this->warn('BREAK-GLASS ACCESS GRANTED');
        $this->line($created ? 'Emergency administrator account created.' : 'Emergency administrator account restored/unlocked.');
        $this->newLine();
        $this->table(
            ['Field', 'Value'],
            [
                ['Email', $email],
                ['Temporary password', $plainPassword],
                ['Role', 'Administrator'],
            ],
        );
        $this->newLine();
        $this->comment('Sign in now, then change this password immediately.');
        $this->comment('This action was written to the application log.');

        return self::SUCCESS;
    }

    private function restorePrimaryAdmin(): void
    {
        $primary = User::withTrashed()->where('email', 'admin@aposecurity.com')->first();

        if (! $primary) {
            $this->warn('Primary admin@aposecurity.com was not found. Skipped.');

            return;
        }

        if ($primary->trashed()) {
            $primary->restore();
        }

        $primary->forceFill([
            'role' => UserRole::Admin,
            'is_active' => true,
            'remember_token' => null,
        ])->save();

        $primary->tokens()->delete();

        $this->info('Primary admin@aposecurity.com was restored and reactivated.');
    }
}
