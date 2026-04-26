<?php

namespace Modules\Reservations\Providers;

use Illuminate\Support\ServiceProvider;

class ReservationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->app->register(RouteServiceProvider::class);
    }
}
