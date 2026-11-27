<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengaturanKompresiController extends Controller
{
    private function currentSettings(): array
    {
        $row = DB::table('compression_settings')->orderBy('id')->first();

        return [
            'max_file_size_mb' => (int) ($row->max_file_size_mb ?? 4),
            'photo_target_size_kb' => (int) ($row->photo_target_size_kb ?? 100),
        ];
    }

    public function show(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->currentSettings(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'max_file_size_mb' => ['required', 'integer', 'min:1', 'max:10'],
            'photo_target_size_kb' => ['required', 'integer', 'min:50', 'max:1024'],
        ]);

        $existing = DB::table('compression_settings')->orderBy('id')->first();
        $now = now();

        if ($existing) {
            DB::table('compression_settings')
                ->where('id', $existing->id)
                ->update([
                    'max_file_size_mb' => (int) $validated['max_file_size_mb'],
                    'photo_target_size_kb' => (int) $validated['photo_target_size_kb'],
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('compression_settings')->insert([
                'max_file_size_mb' => (int) $validated['max_file_size_mb'],
                'photo_target_size_kb' => (int) $validated['photo_target_size_kb'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan kompresi berkas berhasil disimpan.',
            'data' => $this->currentSettings(),
        ]);
    }
}

