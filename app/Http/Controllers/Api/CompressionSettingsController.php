<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CompressionSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompressionSettingsController extends Controller
{
    public function show(CompressionSettings $settings): JsonResponse
    {
        return response()->json([
            'data' => $settings->get(),
        ]);
    }

    public function update(Request $request, CompressionSettings $settings): JsonResponse
    {
        $validated = $request->validate([
            'max_file_size_mb' => [
                'required',
                'integer',
                'min:1',
                'max:' . CompressionSettings::MAX_FILE_SIZE_MB,
            ],
            'photo_target_size_kb' => [
                'required',
                'integer',
                'min:' . CompressionSettings::MIN_PHOTO_TARGET_SIZE_KB,
                'max:' . CompressionSettings::MAX_PHOTO_TARGET_SIZE_KB,
            ],
        ]);

        $savedSettings = $settings->update(
            (int) $validated['max_file_size_mb'],
            (int) $validated['photo_target_size_kb']
        );

        return response()->json([
            'message' => 'Pengaturan ukuran surat pengantar dan target kompresi pas foto berhasil disimpan.',
            'data' => $savedSettings,
        ]);
    }
}
