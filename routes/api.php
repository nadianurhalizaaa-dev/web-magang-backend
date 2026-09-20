<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LaporanMagangController;
use App\Http\Controllers\Api\PenilaianController;
use App\Http\Controllers\Api\RekapitulasiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Test endpoint
Route::get('/test', function () {
    return response()->json(['message' => 'Koneksi ke Laravel Berhasil!']);
});

// UC-01 s/d UC-06 Routes
Route::prefix('v1')->group(function () {
    // Laporan Magang
    Route::get('/laporan-magang', [LaporanMagangController::class, 'index']);
    Route::post('/laporan-magang', [LaporanMagangController::class, 'store']);
    Route::get('/laporan-magang/{id}', [LaporanMagangController::class, 'show']);
    Route::post('/laporan-magang/{id}/review', [LaporanMagangController::class, 'review']);
    Route::post('/laporan-magang/{id}/revisi', [LaporanMagangController::class, 'uploadRevisi']);
    Route::delete('/laporan-magang/{id}', [LaporanMagangController::class, 'destroy']);

    // Penilaian
    Route::get('/penilaian', [PenilaianController::class, 'index']);
    Route::post('/penilaian', [PenilaianController::class, 'store']);

    // Rekapitulasi
    Route::get('/rekapitulasi', [RekapitulasiController::class, 'index']);
});
