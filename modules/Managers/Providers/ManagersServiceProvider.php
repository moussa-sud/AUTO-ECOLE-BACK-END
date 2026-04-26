<?php

namespace Modules\Managers\Providers;

use Illuminate\Support\ServiceProvider;

class ManagersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
