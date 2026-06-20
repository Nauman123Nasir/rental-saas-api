<?php

use App\Modules\Auth\Controllers\AuthController;
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

    // ── Phase 2: Customers ────────────────────────────────────────────────────
    // Route::apiResource('customers', CustomerController::class);

    // ── Phase 3: Assets / Fleet ───────────────────────────────────────────────
    // Route::apiResource('assets', AssetController::class);
    // Route::get('assets/availability', [AssetController::class, 'availability']);

    // ── Phase 4: Reservations ─────────────────────────────────────────────────
    // Route::apiResource('reservations', ReservationController::class);

    // ── Phase 5: Rentals / Operations ─────────────────────────────────────────
    // Route::post('rentals/checkout', [RentalController::class, 'checkout']);
    // Route::post('rentals/checkin',  [RentalController::class, 'checkin']);

});
