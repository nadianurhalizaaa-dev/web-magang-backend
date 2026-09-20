<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TugasController;
use App\Http\Controllers\Api\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function () {
    return response()->json([
        'message' => 'Halo dari Laravel API!'
    ]);
});

// Login (terbuka, dibatasi 10 percobaan per menit)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Semua role yang sudah login
    Route::get('/tugas', [TugasController::class, 'index']);
    Route::get('/tugas/{id}', [TugasController::class, 'show']);

    // Admin & Pembimbing: kelola tugas (buat, ubah, hapus)
    Route::middleware('role:admin,pembimbing')->group(function () {
        Route::post('/tugas', [TugasController::class, 'store']);
        Route::put('/tugas/{id}', [TugasController::class, 'update']);
        Route::delete('/tugas/{id}', [TugasController::class, 'destroy']);
    });

    // Admin & Pembimbing: lihat daftar submisi per tugas
    Route::get('/tugas/{id}/submisi', [TugasController::class, 'daftarSubmisi'])
        ->middleware('role:admin,pembimbing');

    // Peserta: kumpul tugas & lihat submisi sendiri
    Route::middleware('role:peserta')->group(function () {
        Route::post('/tugas/{id}/submit', [TugasController::class, 'submit']);
        Route::get('/submisi-saya', [TugasController::class, 'submisiSaya']);
    });

    // Pembimbing & Admin: lihat semua submisi, unduh file, beri nilai
    Route::middleware('role:admin,pembimbing')->group(function () {
        Route::get('/submisi', [TugasController::class, 'semuaSubmisi']);
        Route::get('/submisi/{submisiId}/download', [TugasController::class, 'download']);
        Route::put('/submisi/{submisiId}/nilai', [TugasController::class, 'beriNilai']);
    });
});