<?php

use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Http\Controllers\DashboardController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::middleware('role:owner')->get('dashboard/owner', [DashboardController::class, 'ownerDashboard']);
    Route::middleware('role:manager')->get('dashboard/manager', [DashboardController::class, 'managerDashboard']);
    Route::middleware('role:student')->get('dashboard/student', [DashboardController::class, 'studentDashboard']);

    // Notifications
    Route::get('notifications', [DashboardController::class, 'notifications']);
    Route::patch('notifications/{id}/read', [DashboardController::class, 'markNotificationRead']);
    Route::patch('notifications/mark-all-read', [DashboardController::class, 'markAllRead']);
});
