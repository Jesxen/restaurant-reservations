<?php

use App\Http\Controllers\Admin\BlackoutDateController;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MesaController;
use App\Http\Controllers\Admin\PlatoController;
use App\Http\Controllers\Admin\ReservaController as AdminReservaController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WaitlistController as AdminWaitlistController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\WaitlistController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Public
// ---------------------------------------------------------------------------
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:auth');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:auth');

// Signed email verification link (opened from an email client, no bearer token).
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

Route::get('/menu', [MenuController::class, 'index']);
Route::get('/disponibilidad', [AvailabilityController::class, 'show']);
Route::get('/horarios', [HorarioController::class, 'index']);
Route::post('/reservas', [ReservaController::class, 'store'])->middleware('throttle:reservas');
Route::get('/settings', [SettingController::class, 'publicShow']);
Route::post('/contacto', [ContactController::class, 'store'])->middleware('throttle:contacto');

// Reviews (public read + aggregate).
Route::get('/reviews', [ReviewController::class, 'index']);
Route::get('/reviews/resumen', [ReviewController::class, 'resumen']);

// Waitlist (public join, throttled like reservas).
Route::post('/waitlist', [WaitlistController::class, 'store'])->middleware('throttle:reservas');

// Stripe webhook (no auth, CSRF-exempt by virtue of being an API route;
// signature is verified inside the controller).
Route::post('/stripe/webhook', StripeWebhookController::class);

// ---------------------------------------------------------------------------
// Authenticated (any logged-in user)
// ---------------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:auth');

    Route::get('/mis-reservas', [ReservaController::class, 'misReservas']);
    Route::patch('/reservas/{reserva}', [ReservaController::class, 'actualizar']);
    Route::patch('/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar']);

    // Submit a review (eligibility enforced in request + policy).
    Route::post('/reviews', [ReviewController::class, 'store']);

    // Own waitlist entries.
    Route::get('/mis-esperas', [WaitlistController::class, 'misEsperas']);
});

// ---------------------------------------------------------------------------
// Staff + Admin (operations: reservations, tables, menu, blackout dates)
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
    Route::apiResource('blackout-dates', BlackoutDateController::class)->parameters([
        'blackout-dates' => 'blackoutDate',
    ])->except(['show']);

    // Reviews moderation.
    Route::get('/reviews', [AdminReviewController::class, 'index']);
    Route::patch('/reviews/{review}', [AdminReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy']);

    // Waitlist management.
    Route::get('/waitlist', [AdminWaitlistController::class, 'index']);
    Route::delete('/waitlist/{entry}', [AdminWaitlistController::class, 'destroy']);
});

// ---------------------------------------------------------------------------
// Admin only (users + settings)
// ---------------------------------------------------------------------------
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::apiResource('usuarios', UserController::class)->except(['show']);

    Route::get('/settings', [SettingController::class, 'show']);
    Route::patch('/settings', [SettingController::class, 'update']);
});
