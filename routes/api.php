<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Core routes — each module registers its own routes via RouteServiceProvider.
| All module routes are prefixed with /api via their respective providers.
*/

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'app' => config('app.name')]);
});
