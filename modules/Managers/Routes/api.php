<?php

use Illuminate\Support\Facades\Route;
use Modules\Managers\Http\Controllers\ManagerController;

Route::middleware(['auth:sanctum', 'tenant', 'role:owner'])->group(function () {
    Route::get('managers',                              [ManagerController::class, 'index']);
    Route::post('managers',                             [ManagerController::class, 'store']);
    Route::get('managers/{manager}',                    [ManagerController::class, 'show']);
    Route::put('managers/{manager}',                    [ManagerController::class, 'update']);
    Route::delete('managers/{manager}',                 [ManagerController::class, 'destroy']);
    Route::patch('managers/{manager}/toggle-status',    [ManagerController::class, 'toggleStatus']);
    Route::post('managers/{manager}/avatar',            [ManagerController::class, 'uploadAvatar']);
    Route::delete('managers/{manager}/avatar',          [ManagerController::class, 'removeAvatar']);
});
