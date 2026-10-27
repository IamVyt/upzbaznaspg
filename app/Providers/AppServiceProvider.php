<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        // Batasi percobaan login per akun, bukan hanya per IP. Ini mencegah
        // brute force tetap jalan saat header X-Forwarded-For dipalsukan.
        RateLimiter::for('login', function (Request $request) {
            $username = strtolower(trim((string) $request->input('username')));

            return [
                Limit::perMinute(6)->by('login-ip:'.$request->ip()),
                Limit::perMinute(10)->by('login-user:'.$username),
            ];
        });

        // Paksa skema HTTPS untuk semua URL & asset saat di production / reverse proxy
        if ($this->app->environment('production') || request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }

        Blade::directive('rupiah', function ($expression) {
            return "<?php echo format_rupiah($expression); ?>";
        });

        Blade::directive('tglIndo', function ($expression) {
            return "<?php echo tgl_indo($expression); ?>";
        });
    }
}
