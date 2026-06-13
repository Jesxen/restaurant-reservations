<?php

use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MesaController;
use App\Http\Controllers\Admin\PlatoController;
use App\Http\Controllers\Admin\ReservaController as AdminReservaController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Public
// ---------------------------------------------------------------------------
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::get('/menu', [MenuController::class, 'index']);
Route::get('/disponibilidad', [AvailabilityController::class, 'show']);
Route::post('/reservas', [ReservaController::class, 'store'])->middleware('throttle:reservas');
Route::get('/settings', [SettingController::class, 'publicShow']);
Route::post('/contacto', [ContactController::class, 'store'])->middleware('throttle:contacto');

// ---------------------------------------------------------------------------
// Authenticated (any logged-in user)
// ---------------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/mis-reservas', [ReservaController::class, 'misReservas']);
    Route::patch('/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar']);
});

// ---------------------------------------------------------------------------
// Staff + Admin (operations: reservations, tables, menu)
// ---------------------------------------------------------------------------
Route::middleware(['auth:sanctum', 'staff'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/reservas', [AdminReservaController::class, 'index']);
    Route::get('/reservas/export', [AdminReservaController::class, 'export']);
    Route::get('/reservas/{reserva}', [AdminReservaController::class, 'show']);
    Route::patch('/reservas/{reserva}', [AdminReservaController::class, 'update']);

    Route::apiResource('mesas', MesaController::class)->except(['show']);
    Route::apiResource('categorias', CategoriaController::class)->except(['show']);
    Route::apiResource('platos', PlatoController::class)->except(['show']);
});

// ---------------------------------------------------------------------------
// Admin only (users + settings)
// ---------------------------------------------------------------------------
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::apiResource('usuarios', UserController::class)->except(['show']);

    Route::get('/settings', [SettingController::class, 'show']);
    Route::patch('/settings', [SettingController::class, 'update']);
});
