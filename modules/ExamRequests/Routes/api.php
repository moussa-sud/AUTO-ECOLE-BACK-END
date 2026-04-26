<?php

use Illuminate\Support\Facades\Route;
use Modules\ExamRequests\Http\Controllers\ExamRequestController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Student: submit and view own requests
    Route::middleware('role:student')->group(function () {
        Route::post('exam-requests', [ExamRequestController::class, 'store']);
        Route::get('exam-requests/my', [ExamRequestController::class, 'myRequests']);
    });

    // Manager: review requests
    Route::middleware('role:manager')->group(function () {
        Route::get('exam-requests', [ExamRequestController::class, 'index']);
        Route::post('exam-requests/{examRequest}/review', [ExamRequestController::class, 'managerReview']);
    });

    // Owner: view all + final decision
    Route::middleware('role:owner')->group(function () {
        Route::get('exam-requests', [ExamRequestController::class, 'index']);
        Route::post('exam-requests/{examRequest}/decide', [ExamRequestController::class, 'ownerDecide']);
        Route::delete('exam-requests/{examRequest}', [ExamRequestController::class, 'destroy']);
    });

    Route::get('exam-requests/{examRequest}', [ExamRequestController::class, 'show'])
        ->middleware('role:owner,manager,student');
});
