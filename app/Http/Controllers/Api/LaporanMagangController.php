<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaporanMagang;
use App\Models\RiwayatRevisiLaporan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LaporanMagangController extends Controller
{
    /**
     * Display a listing of reports with search & filter.
     */
    public function index(Request $request)
    {
        $query = LaporanMagang::with(['riwayatRevisi', 'penilaian']);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul_laporan', 'like', "%{$search}%")
                  ->orWhere('abstrak', 'like', "%{$search}%");
            });
        }

        if ($request->has('kategori') && !empty($request->kategori) && $request->kategori !== 'Semua') {
            $query->where('kategori_laporan', $request->kategori);
        }

        if ($request->has('status') && !empty($request->status) && $request->status !== 'Semua') {
            $query->where('status_laporan', $request->status);
        }

        $laporan = $query->orderBy('id_laporan', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar laporan magang berhasil diambil',
            'data' => $laporan
        ]);
    }

    /**
     * UC-02: Mengunggah Laporan Magang (Anak Magang)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_peserta' => 'required|integer',
            'judul_laporan' => 'required|string|max:255',
            'kategori_laporan' => 'required|in:Individu,Kelompok',
            'abstrak' => 'nullable|string',
            'file_laporan_pdf' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $laporan = LaporanMagang::create([
            'id_peserta' => $request->id_peserta,
            'judul_laporan' => $request->judul_laporan,
            'kategori_laporan' => $request->kategori_laporan,
            'abstrak' => $request->abstrak,
            'file_laporan_pdf' => $request->file_laporan_pdf,
            'tgl_submit' => now(),
            'status_laporan' => 'Menunggu Review',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan magang berhasil diunggah',
            'data' => $laporan
        ], 201);
    }

    /**
     * Display the specified report.
     */
    public function show($id)
    {
        $laporan = LaporanMagang::with(['riwayatRevisi', 'penilaian'])->find($id);

        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Data laporan tidak ditemukan'
            ], 44);
        }

        return response()->json([
            'success' => true,
            'data' => $laporan
        ]);
    }

    /**
     * UC-03: Review & Moderasi Laporan (Pembimbing)
     */
    public function review(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status_laporan' => 'required|in:Revisi,Disetujui',
            'catatan_revisi' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $laporan = LaporanMagang::find($id);
        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan'
            ], 404);
        }

        $laporan->status_laporan = $request->status_laporan;
        if ($request->has('catatan_revisi')) {
            $laporan->catatan_revisi = $request->catatan_revisi;
        }

        $laporan->save();

        return response()->json([
            'success' => true,
            'message' => 'Review laporan berhasil disimpan',
            'data' => $laporan
        ]);
    }

    /**
     * UC-04: Mengunggah Ulang / Revisi Laporan (Anak Magang)
     */
    public function uploadRevisi(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'link_google_doc' => 'required|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $laporan = LaporanMagang::find($id);
        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan'
            ], 404);
        }

        // Add revision history entry
        $revisi = RiwayatRevisiLaporan::create([
            'id_laporan' => $laporan->id_laporan,
            'link_google_doc' => $request->link_google_doc,
            'tanggal_revisi' => now(),
        ]);

        // Update status back to Menunggu Review
        $laporan->status_laporan = 'Menunggu Review';
        $laporan->save();

        return response()->json([
            'success' => true,
            'message' => 'Revisi laporan berhasil diunggah',
            'data' => [
                'laporan' => $laporan,
                'revisi' => $revisi
            ]
        ]);
    }

    /**
     * Destroy a report.
     */
    public function destroy($id)
    {
        $laporan = LaporanMagang::find($id);
        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $laporan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data laporan berhasil dihapus'
        ]);
    }
}
