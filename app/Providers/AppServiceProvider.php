<?php

namespace App\Providers;

use App\Models\SecurityPersonnel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Built assets as root-relative paths so Cloudflare Tunnel works
        // even when APP_URL is still http://localhost:8010.
        // Skip when Vite hot file exists (local npm run dev).
        if (! file_exists(public_path('hot'))) {
            Vite::createAssetPathsUsing(
                fn (string $path, ?bool $secure = null) => '/'.ltrim($path, '/')
            );
        }

        \Illuminate\Pagination\Paginator::useTailwind();

        Route::bind('personnel', fn (string $value) => SecurityPersonnel::findOrFail($value));

        view()->share([
            'inputClass' => 'mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm',
            'labelClass' => 'block text-sm font-medium text-slate-700',
            'btnPrimary' => 'inline-flex items-center px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800',
            'btnSecondary' => 'inline-flex items-center px-4 py-2 bg-white border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50',
        ]);

        view()->composer('layouts.admin', function ($view) {
            if (auth()->check()) {
                $view->with(
                    'unreadNotifications',
                    \App\Models\SystemNotification::where('user_id', auth()->id())->whereNull('read_at')->count()
                );
            }
        });
    }
}
