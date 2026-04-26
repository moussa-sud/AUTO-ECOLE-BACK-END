<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\TenantController;

Route::middleware(['auth:sanctum', 'tenant', 'role:owner'])->group(function () {
    Route::get('settings/tenant', [TenantController::class, 'show']);
    Route::put('settings/tenant', [TenantController::class, 'update']);
});
