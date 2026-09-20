<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PeriodeController;
use App\Http\Controllers\Api\PendaftaranController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/pembimbing', [AuthController::class, 'getPembimbing']);
    Route::get('/me', [AuthController::class, 'me']);

    // Periode
    Route::get('/periode', [PeriodeController::class, 'index']);
    Route::post('/periode', [PeriodeController::class, 'store']);
    Route::delete('/periode/{id}', [PeriodeController::class, 'destroy']);

    // Pendaftaran
    Route::get('/pendaftaran', [PendaftaranController::class, 'index']); // For admin
    Route::get('/pendaftaran/me', [PendaftaranController::class, 'myRegistration']); // For pemagang
    Route::post('/pendaftaran', [PendaftaranController::class, 'store']); // Submit form
    Route::post('/pendaftaran/{id}/status', [PendaftaranController::class, 'update']); // Admin update status
    Route::put('/pendaftaran/cancel', [PendaftaranController::class, 'cancelRegistration']); // Pemagang batalkan pengajuan
});
