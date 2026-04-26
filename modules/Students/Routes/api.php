<?php

use Illuminate\Support\Facades\Route;
use Modules\Students\Http\Controllers\StudentController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

    // Student: self-profile management
    Route::middleware('role:student')->group(function () {
        Route::get('profile',              [StudentController::class, 'myProfile']);
        Route::put('profile',              [StudentController::class, 'updateMyProfile']);
        Route::post('profile/avatar',      [StudentController::class, 'uploadMyAvatar']);
    });

    // Owner & Manager: view students
    Route::middleware('role:owner,manager')->group(function () {
        Route::get('students',                          [StudentController::class, 'index']);
        Route::get('students/{student}',                [StudentController::class, 'show']);
        Route::get('students/{student}/progress',       [StudentController::class, 'progress']);
        // Edit, avatar upload/remove
        Route::put('students/{student}',                [StudentController::class, 'update']);
        Route::post('students/{student}/avatar',        [StudentController::class, 'uploadAvatar']);
        Route::delete('students/{student}/avatar',      [StudentController::class, 'removeAvatar']);
    });

    // Owner only: create, delete, toggle-status
    Route::middleware('role:owner')->group(function () {
        Route::post('students',                         [StudentController::class, 'store']);
        Route::delete('students/{student}',             [StudentController::class, 'destroy']);
        Route::patch('students/{student}/toggle-status',[StudentController::class, 'toggleStatus']);
    });
});
