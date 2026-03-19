<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PvDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// PV Dashboard API Routes
Route::prefix('pv')->group(function () {
    // Get latest PV data
    Route::get('/latest', function () {
        $latest = \App\Models\PvData::getLatest();
        $isOffline = \App\Models\PvData::isOffline();
        $lastUpdateTime = $latest ? $latest->updated_at->format('H:i') : null;
        
        return response()->json([
            'success' => true,
            'data' => $latest,
            'isOffline' => $isOffline,
            'lastUpdateTime' => $lastUpdateTime,
        ]);
    });

    // Get power output chart data
    Route::get('/power-output', [PvDashboardController::class, 'getPowerOutputData']);

    // Get environment chart data
    Route::get('/environment', [PvDashboardController::class, 'getEnvironmentData']);

    // Get generic parameter chart data
    Route::get('/chart', [PvDashboardController::class, 'getChartData']);

    // Store new PV data
    Route::post('/store', [PvDashboardController::class, 'store']);

    // Ingest data from ESP sensor
    Route::post('/ingest', [PvDashboardController::class, 'ingest']);
});
