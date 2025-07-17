<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('events', EventController::class);

// Rutas para eventos
Route::prefix('events')->group(function () {
    Route::get('/', [EventController::class, 'index']);
    Route::post('/', [EventController::class, 'store']);
    Route::get('/{event}', [EventController::class, 'show']);
    Route::put('/{event}', [EventController::class, 'update']);
    Route::delete('/{event}', [EventController::class, 'destroy']);
});

// Rutas adicionales para eventos
Route::prefix('events')->group(function () {
    Route::get('/filter/active', [EventController::class, 'index'])->defaults('is_active', true);
    Route::get('/filter/upcoming', [EventController::class, 'index'])->defaults('upcoming', true);
    Route::get('/filter/past', [EventController::class, 'index'])->defaults('past', true);
});
