<?php

use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Customers\Controllers\CustomerController;
use App\Modules\Vehicles\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Global Asset Rental Management SaaS
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1 (configured in bootstrap/app.php).
| JWT guard is the default auth guard (configured in config/auth.php).
|
*/

// ── Public Auth Routes ────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login',           [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
});

// ── Protected Routes (require valid JWT) ─────────────────────────────────────
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout',  [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me',       [AuthController::class, 'me']);
    });

    // ── Phase 1: Tenants & Organisation ──────────────────────────────────────
    // (Placeholder – controllers to be added in Phase 1)
    // Route::apiResource('branches', BranchController::class);

    // ── Phase 3: Customers ────────────────────────────────────────────────────
    Route::middleware('check.permission:customers.view')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/customers/{id}', [CustomerController::class, 'show']);
    });
    Route::middleware('check.permission:customers.create')->group(function () {
        Route::post('/customers', [CustomerController::class, 'store']);
    });
    Route::middleware('check.permission:customers.update')->group(function () {
        Route::put('/customers/{id}', [CustomerController::class, 'update']);
    });
    Route::middleware('check.permission:customers.delete')->group(function () {
        Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);
    });

    // ── Phase 4: Assets / Fleet ───────────────────────────────────────────────
    Route::middleware('check.permission:vehicles.view')->group(function () {
        Route::get('/vehicles', [VehicleController::class, 'index']);
        Route::get('/vehicles/{id}', [VehicleController::class, 'show']);
    });
    Route::middleware('check.permission:vehicles.create')->group(function () {
        Route::post('/vehicles', [VehicleController::class, 'store']);
    });
    Route::middleware('check.permission:vehicles.update')->group(function () {
        Route::put('/vehicles/{id}', [VehicleController::class, 'update']);
    });
    Route::middleware('check.permission:vehicles.delete')->group(function () {
        Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy']);
    });

    // ── Phase 4: Reservations ─────────────────────────────────────────────────
    // Route::apiResource('reservations', ReservationController::class);

    // ── Phase 5: Rentals / Operations ─────────────────────────────────────────
    // Route::post('rentals/checkout', [RentalController::class, 'checkout']);
    // Route::post('rentals/checkin',  [RentalController::class, 'checkin']);

});
