<?php

namespace App\Providers;

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
        // Resolve after bootstrap/app.php selects the hosting document root.
        // A cached config generated through CLI may contain a different public path.
        config(['filesystems.disks.public.root' => public_path('storage')]);
    }
}
