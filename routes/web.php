<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Headless REST API Backend)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return response()->json([
        'status' => 'online',
        'message' => 'Backend REST API Server is running',
        'api_base_url' => url('/api')
    ]);
});

Route::get('/api-docs', function () {
    return redirect('/api');
});

// Penanganan typo umum seperti kurung tutup / tanda baca tak sengaja
Route::any('/api)', function () {
    return redirect('/api');
});

// Fallback route untuk file storage saat symlink Windows belum aktif
Route::get('/storage/{path}', function ($path) {
    $cleanPath = ltrim($path, '/\\');

    // Cegah path traversal
    if (str_contains($cleanPath, '..')) {
        abort(403);
    }

    $candidates = [
        storage_path('app/public/' . $cleanPath),
        storage_path('app/private/public/' . $cleanPath),
        storage_path('app/' . $cleanPath),
        storage_path('app/private/' . $cleanPath),
    ];

    foreach ($candidates as $filePath) {
        if (file_exists($filePath) && ! is_dir($filePath)) {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeType = match ($ext) {
                'pdf' => 'application/pdf',
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => null,
            };

            $headers = [
                'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
            ];
            if ($mimeType) {
                $headers['Content-Type'] = $mimeType;
            }

            return response()->file($filePath, $headers);
        }
    }

    abort(404, 'Berkas tidak ditemukan.');
})->where('path', '.*');
