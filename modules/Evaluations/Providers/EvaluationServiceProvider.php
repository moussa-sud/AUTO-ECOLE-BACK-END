<?php

namespace Modules\Evaluations\Providers;

use Illuminate\Support\ServiceProvider;

class EvaluationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->app->register(RouteServiceProvider::class);
    }
}
