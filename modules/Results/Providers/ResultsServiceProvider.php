<?php

namespace Modules\Results\Providers;

use Illuminate\Support\ServiceProvider;

class ResultsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->app->register(RouteServiceProvider::class);
    }
}
