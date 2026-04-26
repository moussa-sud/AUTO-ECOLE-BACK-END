<?php

use Illuminate\Support\Facades\Route;
use Modules\Results\Http\Controllers\ResultController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Student: view own results
    Route::middleware('role:student')->group(function () {
        Route::get('results/my', [ResultController::class, 'myResults']);
        Route::get('results/{result}', [ResultController::class, 'show']);
    });

    // Owner & Manager: view all results
    Route::middleware('role:owner,manager')->group(function () {
        Route::get('results', [ResultController::class, 'index']);
        Route::get('results/student/{student}', [ResultController::class, 'studentResults']);
    });
});
