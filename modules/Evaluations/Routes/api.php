<?php

use Illuminate\Support\Facades\Route;
use Modules\Evaluations\Http\Controllers\StudentEvaluationController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get( 'students/{student}/evaluation', [StudentEvaluationController::class, 'show']);
    Route::post('students/{student}/evaluation', [StudentEvaluationController::class, 'store']);
});
