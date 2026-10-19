<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enforce session & cache driver ke file agar tidak pernah query table sessions
        config([
            'session.driver' => 'file',
            'cache.default' => 'file',
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Paksa skema HTTPS untuk semua URL & asset saat di production / reverse proxy
        if ($this->app->environment('production') || request()->header('x-forwarded-proto') === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Blade::directive('rupiah', function ($expression) {
            return "<?php echo format_rupiah($expression); ?>";
        });

        Blade::directive('tglIndo', function ($expression) {
            return "<?php echo tgl_indo($expression); ?>";
        });
    }
}
