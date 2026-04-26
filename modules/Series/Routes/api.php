<?php

use Illuminate\Support\Facades\Route;
use Modules\Series\Http\Controllers\SeriesController;
use Modules\Series\Http\Controllers\QuizController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // All authenticated users can view series
    Route::get('series', [SeriesController::class, 'index']);
    Route::get('series/{series}', [SeriesController::class, 'show']);
    Route::get('series/{series}/questions', [SeriesController::class, 'questions']);

    // All roles can watch videos and submit quizzes
    Route::post('series/{series}/watch-video', [SeriesController::class, 'markVideoWatched']);
    Route::post('series/{series}/submit-quiz', [QuizController::class, 'submit']);

    // Owner only: full CRUD
    Route::middleware('role:owner')->group(function () {
        Route::post('series', [SeriesController::class, 'store']);
        Route::put('series/{series}', [SeriesController::class, 'update']);
        Route::delete('series/{series}', [SeriesController::class, 'destroy']);

        // Questions & Answers management
        Route::post('series/{series}/questions', [SeriesController::class, 'addQuestion']);
        Route::put('series/{series}/questions/{question}', [SeriesController::class, 'updateQuestion']);
        Route::delete('series/{series}/questions/{question}', [SeriesController::class, 'deleteQuestion']);
    });
});
