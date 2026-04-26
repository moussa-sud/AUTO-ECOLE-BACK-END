<?php

use Illuminate\Support\Facades\Route;
use Modules\Feedback\Http\Controllers\FeedbackController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

    // Student: get staff list to rate + submit feedback
    Route::middleware('role:student')->group(function () {
        Route::get('staff',           [FeedbackController::class, 'staff']);
        Route::get('feedback/my',     [FeedbackController::class, 'myFeedback']);
        Route::post('feedback',       [FeedbackController::class, 'store']);
    });

    // Owner only: view all feedback results
    Route::middleware('role:owner')->group(function () {
        Route::get('feedback',                   [FeedbackController::class, 'index']);
        Route::get('feedback/staff/{staff}',     [FeedbackController::class, 'staffFeedback']);
    });
});
