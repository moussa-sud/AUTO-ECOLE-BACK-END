<?php

namespace Modules\Settings\Providers;

use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
