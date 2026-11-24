<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PdfFinalSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PdfFinalSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $setting = PdfFinalSetting::find(1);
        $disk = Storage::disk('local');
        $configured = $setting && $disk->exists($setting->signature_path);

        return response()->json([
            'success' => true,
            'data' => ['configured' => $configured],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'signature' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $existingSetting = PdfFinalSetting::find(1);
        $disk = Storage::disk('local');
        if ($existingSetting && $disk->exists($existingSetting->signature_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Foto tanda tangan di atas stempel sudah tersimpan untuk penggunaan otomatis.',
            ], 409);
        }

        $signaturePath = $request->file('signature')->store('pdf-final-assets', 'local');
        if (! is_string($signaturePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Foto tanda tangan gagal disimpan.',
            ], 500);
        }

        try {
            $setting = $existingSetting ?? new PdfFinalSetting();
            if (! $existingSetting) {
                $setting->id = 1;
            }
            $setting->signature_path = $signaturePath;
            $setting->save();
        } catch (\Throwable $exception) {
            $disk->delete($signaturePath);
            throw $exception;
        }

        if ($existingSetting && $existingSetting->signature_path !== $signaturePath) {
            $disk->delete($existingSetting->signature_path);
        }

        return response()->json([
            'success' => true,
            'message' => 'Foto tanda tangan di atas stempel tersimpan untuk penggunaan otomatis.',
            'data' => ['configured' => true],
        ], 201);
    }

    public function signature()
    {
        $setting = PdfFinalSetting::find(1);
        $path = $setting?->signature_path;
        $disk = Storage::disk('local');

        if (! $path || ! $disk->exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Foto tanda tangan di atas stempel belum tersedia.',
            ], 404);
        }

        return $disk->response($path, null, [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
