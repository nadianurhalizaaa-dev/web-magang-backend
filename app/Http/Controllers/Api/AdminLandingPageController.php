<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AktivitasMagang;
use App\Models\AlumniMagang;
use App\Models\CompanyProfile;
use App\Models\KomentarAplikasi;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AdminLandingPageController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => [
                'profil_kantor' => CompanyProfile::query()->first(),
                'alumni_magang' => AlumniMagang::query()->latest()->get(),
                'aktivitas_magang' => AktivitasMagang::with('user:id,name,email')
                    ->latest('tanggal')
                    ->get(),
                'komentar_aplikasi' => KomentarAplikasi::with('user:id,name,email')
                    ->latest()
                    ->get(),
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'nama_perusahaan' => 'required|string|max:255',
            'deskripsi_singkat' => 'required|string',
            'tentang_kantor' => 'required|string',
            'bidang_usaha' => 'required|string|max:255',
            'visi' => 'nullable|string',
            'misi' => 'nullable|string',
            'alamat' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telepon' => 'nullable|string|max:50',
            'instagram' => 'nullable|string|max:255',
        ]);

        $profile = CompanyProfile::query()->first() ?? new CompanyProfile();
        $profile->fill($validated);
        $profile->save();

        return response()->json(['data' => $profile]);
    }

    public function storeAlumni(Request $request)
    {
        $alumni = AlumniMagang::create($this->validateAlumni($request));

        return response()->json(['data' => $alumni], 201);
    }

    public function updateAlumni(Request $request, AlumniMagang $alumni)
    {
        $alumni->update($this->validateAlumni($request));

        return response()->json(['data' => $alumni]);
    }

    public function destroyAlumni(AlumniMagang $alumni)
    {
        $alumni->delete();

        return response()->json(['message' => 'Data alumni berhasil dihapus.']);
    }

    public function storeActivity(Request $request)
    {
        $validated = $this->validateActivity($request);
        $validated['user_id'] = $request->user()->id;
        if ($request->hasFile('foto')) {
            $validated['foto'] = $this->storePhoto($request->file('foto'));
        }

        $activity = AktivitasMagang::create($validated);
        $activity->load('user:id,name,email');

        return response()->json(['data' => $activity], 201);
    }

    public function updateActivity(Request $request, AktivitasMagang $activity)
    {
        $validated = $this->validateActivity($request);
        $oldPhoto = $activity->foto;

        if ($request->hasFile('foto')) {
            $validated['foto'] = $this->storePhoto($request->file('foto'));
        } elseif ($request->exists('foto') && $request->input('foto') === null) {
            $validated['foto'] = null;
        }

        $activity->update($validated);
        if ($oldPhoto && $oldPhoto !== $activity->foto) {
            $this->deletePhoto($oldPhoto);
        }
        $activity->load('user:id,name,email');

        return response()->json(['data' => $activity]);
    }

    public function destroyActivity(AktivitasMagang $activity)
    {
        $photo = $activity->foto;
        $activity->delete();
        $this->deletePhoto($photo);

        return response()->json(['message' => 'Aktivitas berhasil dihapus.']);
    }

    public function destroyComment(KomentarAplikasi $comment)
    {
        $comment->delete();

        return response()->json(['message' => 'Ulasan berhasil dihapus.']);
    }

    public function updateCommentStatus(Request $request, KomentarAplikasi $comment)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:published,pending',
        ]);
        $comment->update($validated);

        return response()->json(['data' => $comment]);
    }

    private function validateAlumni(Request $request): array
    {
        return $request->validate([
            'nama' => 'required|string|max:255',
            'asal_instansi' => 'required|string|max:255',
            'jurusan' => 'required|string|max:255',
            'periode_magang' => 'required|string|max:255',
            'kesan_pesan' => 'required|string',
        ]);
    }

    private function validateActivity(Request $request): array
    {
        return $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'tanggal' => 'required|date',
            'kategori' => 'nullable|string|max:100',
            'lokasi' => 'nullable|string|max:255',
            'status' => 'required|string|in:active,draft,archived',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5048',
        ]);
    }

    private function storePhoto(UploadedFile $photo): string
    {
        $directory = public_path('uploads/aktivitas');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Folder upload aktivitas tidak dapat dibuat.');
        }

        $filename = now()->format('YmdHis').'_'.Str::uuid().'.'.$photo->getClientOriginalExtension();
        $photo->move($directory, $filename);

        return 'uploads/aktivitas/'.$filename;
    }

    private function deletePhoto(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'uploads/aktivitas/')) {
            return;
        }

        $directory = realpath(public_path('uploads/aktivitas'));
        $file = realpath(public_path($path));
        if ($directory && $file && str_starts_with(strtolower($file), strtolower($directory.DIRECTORY_SEPARATOR)) && is_file($file)) {
            unlink($file);
        }
    }
}
