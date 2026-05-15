<?php

use App\Http\Controllers\Api\V1\RiskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST — authentification Sanctum (Bearer token ou cookie SPA).
| Rate limiting : throttle:api (voir RouteServiceProvider / bootstrap).
|--------------------------------------------------------------------------
*/

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:api', 'tenant.resolve', 'api.hardening'])
    ->group(function (): void {
        Route::get('/missions/{mission}/risques', [RiskController::class, 'indexForMission'])
            ->name('api.v1.missions.risques.index');
        Route::get('/missions/{mission}/risk-cartography', [RiskController::class, 'cartography'])
            ->name('api.v1.missions.risk-cartography');
        Route::post('/risques', [RiskController::class, 'store'])->name('api.v1.risques.store');
        Route::get('/risques/{risque}', [RiskController::class, 'show'])->name('api.v1.risques.show');
        Route::patch('/risques/{risque}', [RiskController::class, 'update'])->name('api.v1.risques.update');
    });
