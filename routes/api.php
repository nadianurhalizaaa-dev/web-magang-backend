<?php

use App\Http\Controllers\Api\AktivitasController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BerandaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - REST API Endpoint (React Frontend Integration)
|--------------------------------------------------------------------------
*/

// Public Beranda, Profil, & Komentar Read APIs
Route::get('/beranda', [BerandaController::class, 'index']);
Route::get('/beranda/alumni', [BerandaController::class, 'getAlumni']);
Route::get('/beranda/profil', [BerandaController::class, 'getProfil']);
Route::get('/komentar', [BerandaController::class, 'getKomentar']);

// Public Aktivitas Magang APIs (Input Admin, Dibaca Pengguna)
Route::get('/aktivitas', [AktivitasController::class, 'index']);
Route::get('/aktivitas/{id}', [AktivitasController::class, 'show']);

// Public REST API Auth Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected REST API Auth Routes (Requires Sanctum Bearer Token & Login)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Kirim Komentar Aplikasi Umum
    Route::post('/komentar', [BerandaController::class, 'storeKomentar']);

    // Aktivitas Magang: Input Aktivitas (Admin) & Kirim Komentar Aktivitas (Pengguna)
    Route::post('/aktivitas', [AktivitasController::class, 'store']);
    Route::post('/aktivitas/{id}/komentar', [AktivitasController::class, 'storeKomentar']);
});
