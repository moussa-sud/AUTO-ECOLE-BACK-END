<?php

use Illuminate\Support\Facades\Route;
use Modules\Reservations\Http\Controllers\ReservationController;
use Modules\Reservations\Http\Controllers\ReservationSettingController;
use Modules\Reservations\Http\Controllers\TimeSlotController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

    // ── Time Slots ─────────────────────────────────────────────────
    // All authenticated users: available slots for a date
    Route::get('time-slots', [TimeSlotController::class, 'index']);

    // Owner/Manager: view all slots (no date filter)
    Route::middleware('role:owner,manager')->group(function () {
        Route::get('time-slots/all', [TimeSlotController::class, 'all']);
    });

    // Owner: manage slots
    Route::middleware('role:owner')->group(function () {
        Route::post('time-slots', [TimeSlotController::class, 'store']);
        Route::post('time-slots/generate', [TimeSlotController::class, 'generate']);
        Route::put('time-slots/{slot}', [TimeSlotController::class, 'update']);
        Route::delete('time-slots/{slot}', [TimeSlotController::class, 'destroy']);
    });

    // ── Reservations ───────────────────────────────────────────────
    Route::get('reservations', [ReservationController::class, 'index']);
    Route::get('reservations/{reservation}', [ReservationController::class, 'show']);
    Route::put('reservations/{reservation}', [ReservationController::class, 'update']);

    // Student: create reservation
    Route::middleware('role:student')->group(function () {
        Route::post('reservations', [ReservationController::class, 'store']);
    });

    // Owner: delete reservation
    Route::middleware('role:owner')->group(function () {
        Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy']);
    });

    // ── Settings ───────────────────────────────────────────────────
    Route::get('reservation-settings', [ReservationSettingController::class, 'show']);
    Route::middleware('role:owner')->group(function () {
        Route::put('reservation-settings', [ReservationSettingController::class, 'update']);
    });
});
