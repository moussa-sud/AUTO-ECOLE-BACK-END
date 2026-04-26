<?php

namespace Modules\ExamRequests\Providers;

use Illuminate\Support\ServiceProvider;

class ExamRequestsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->app->register(RouteServiceProvider::class);
    }
}
