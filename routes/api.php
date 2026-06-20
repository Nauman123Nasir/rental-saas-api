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

    // ── Phase 8: Operational Dashboard ────────────────────────────────────────
    Route::get('/dashboard/stats', [App\Http\Controllers\Api\V1\DashboardController::class, 'index']);

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
    // Assets
    // Assets
    Route::middleware('check.permission:assets.view')->group(function () {
        Route::get('/assets', [App\Modules\Assets\Controllers\AssetController::class, 'index']);
        Route::get('/assets/{asset}', [App\Modules\Assets\Controllers\AssetController::class, 'show']);
        Route::get('/asset-categories', [App\Modules\Assets\Controllers\AssetCategoryController::class, 'index']);
    });
    Route::middleware('check.permission:assets.create')->group(function () {
        Route::post('/assets', [App\Modules\Assets\Controllers\AssetController::class, 'store']);
    });
    Route::middleware('check.permission:assets.update')->group(function () {
        Route::put('/assets/{asset}', [App\Modules\Assets\Controllers\AssetController::class, 'update']);
    });
    Route::middleware('check.permission:assets.delete')->group(function () {
        Route::delete('/assets/{asset}', [App\Modules\Assets\Controllers\AssetController::class, 'destroy']);
    });

    // ── Phase 5: Reservations ─────────────────────────────────────────────────
    Route::middleware('check.permission:reservations.view')->group(function () {
        Route::get('/reservations', [App\Http\Controllers\Api\V1\ReservationController::class, 'index']);
        Route::get('/reservations/{id}', [App\Http\Controllers\Api\V1\ReservationController::class, 'show']);
    });
    Route::middleware('check.permission:reservations.create')->group(function () {
        Route::post('/reservations', [App\Http\Controllers\Api\V1\ReservationController::class, 'store']);
    });
    Route::middleware('check.permission:reservations.update')->group(function () {
        Route::put('/reservations/{id}', [App\Http\Controllers\Api\V1\ReservationController::class, 'update']);
    });
    Route::middleware('check.permission:reservations.delete')->group(function () {
        Route::delete('/reservations/{id}', [App\Http\Controllers\Api\V1\ReservationController::class, 'destroy']);
    });

    // ── Phase 6: Rentals / Operations ─────────────────────────────────────────
    Route::middleware('check.permission:rentals.view')->group(function () {
        Route::get('/rentals', [App\Http\Controllers\Api\V1\RentalController::class, 'index']);
        Route::get('/rentals/{id}', [App\Http\Controllers\Api\V1\RentalController::class, 'show']);
    });
    Route::middleware('check.permission:rentals.create')->group(function () {
        Route::post('/rentals/checkout', [App\Http\Controllers\Api\V1\RentalController::class, 'checkout']);
    });
    Route::middleware('check.permission:rentals.update')->group(function () {
        Route::post('/rentals/{id}/checkin', [App\Http\Controllers\Api\V1\RentalController::class, 'checkin']);
    });

    // ── Phase 7: Finance (Invoices & Payments) ─────────────────────────────────
    Route::prefix('finance')->group(function () {

        // Invoices
        Route::middleware('check.permission:finance.view')->group(function () {
            Route::get('/invoices',       [App\Modules\Finance\Controllers\InvoiceController::class, 'index']);
            Route::get('/invoices/{id}',  [App\Modules\Finance\Controllers\InvoiceController::class, 'show']);
        });
        Route::middleware('check.permission:finance.create')->group(function () {
            Route::post('/invoices/generate', [App\Modules\Finance\Controllers\InvoiceController::class, 'generate']);
        });
        Route::middleware('check.permission:finance.update')->group(function () {
            Route::patch('/invoices/{id}/void', [App\Modules\Finance\Controllers\InvoiceController::class, 'void']);
        });

        // Payments
        Route::middleware('check.permission:finance.view')->group(function () {
            Route::get('/payments',      [App\Modules\Finance\Controllers\PaymentController::class, 'index']);
            Route::get('/payments/{id}', [App\Modules\Finance\Controllers\PaymentController::class, 'show']);
        });
        Route::middleware('check.permission:finance.create')->group(function () {
            Route::post('/payments', [App\Modules\Finance\Controllers\PaymentController::class, 'store']);
        });
    });

});

