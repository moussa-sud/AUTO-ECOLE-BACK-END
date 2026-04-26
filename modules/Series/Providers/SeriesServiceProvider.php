<?php

namespace Modules\Series\Providers;

use Illuminate\Support\ServiceProvider;

class SeriesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->app->register(RouteServiceProvider::class);
    }
}
