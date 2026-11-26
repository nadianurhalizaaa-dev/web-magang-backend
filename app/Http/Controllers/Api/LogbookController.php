<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use App\Models\Pendaftaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogbookController extends Controller
{
    private function isSupervisorRole(?string $role): bool
    {
        return strtolower(trim((string) $role)) === 'pembimbing';
    }

    private function isAdminRole(?string $role): bool
    {
        return in_array(strtolower(trim((string) $role)), ['admin', 'administrator'], true);
    }

    private function assignedParticipantIds(int $supervisorId)
    {
        return Pendaftaran::where('pembimbing_id', $supervisorId)
            ->where('status', 'Pemagang Aktif')
            ->pluck('user_id');
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = strtolower(trim((string) $user?->role));

        $query = Logbook::with([
            'user:id,name,email,nim,program_studi,foto',
            'user.pendaftarans' => fn ($q) => $q->orderByDesc('id'),
            'verifier:id,name',
        ]);

        if ($this->isSupervisorRole($role)) {
            $query->whereIn('user_id', $this->assignedParticipantIds($user->id));
        } elseif (! $this->isAdminRole($role)) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('user_id') && ($this->isAdminRole($role) || $this->isSupervisorRole($role))) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('month') && preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month'))) {
            [$year, $month] = explode('-', (string) $request->input('month'));
            $query->whereYear('tanggal', $year)->whereMonth('tanggal', $month);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->input('kategori'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('judul_kegiatan', 'like', "%{$search}%")
                    ->orWhere('deskripsi_kegiatan', 'like', "%{$search}%")
                    ->orWhere('catatan_pembimbing', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $items = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->map(function (Logbook $item) {
                $registration = $item->user?->pendaftarans
                    ->first(fn (Pendaftaran $p) => $p->status === 'Pemagang Aktif')
                    ?? $item->user?->pendaftarans->first();

                $item->setAttribute('nama_peserta', $item->user?->name);
                $item->setAttribute('nim_peserta', $item->user?->nim);
                $item->setAttribute('asal_instansi', $registration?->asal_instansi);
                $item->setAttribute('nama_verifikator', $item->verifier?->name);

                return $item;
            });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'jam_mulai' => ['nullable', 'string', 'max:10'],
            'jam_selesai' => ['nullable', 'string', 'max:10'],
            'kategori' => ['nullable', 'string', 'max:50'],
            'judul_kegiatan' => ['required', 'string', 'max:255'],
            'deskripsi_kegiatan' => ['required', 'string'],
            'foto_kegiatan' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'link_video' => ['nullable', 'url:http,https', 'max:500'],
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto_kegiatan')) {
            $fotoPath = $request->file('foto_kegiatan')->store('logbook', 'public');
        }

        $logbook = Logbook::create([
            'user_id' => $request->user()->id,
            'tanggal' => $validated['tanggal'],
            'jam_mulai' => $validated['jam_mulai'] ?? null,
            'jam_selesai' => $validated['jam_selesai'] ?? null,
            'kategori' => $validated['kategori'] ?: 'Teknis',
            'judul_kegiatan' => $validated['judul_kegiatan'],
            'deskripsi_kegiatan' => $validated['deskripsi_kegiatan'],
            'foto_kegiatan' => $fotoPath,
            'link_video' => ($linkVideo = trim((string) ($validated['link_video'] ?? ''))) !== '' ? $linkVideo : null,
            'status' => 'Menunggu Verifikasi',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Catatan logbook harian berhasil disimpan.',
            'data' => $logbook->fresh(['user:id,name,nim', 'verifier:id,name']),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $logbook = Logbook::find($id);
        if (! $logbook) {
            return response()->json(['message' => 'Catatan logbook tidak ditemukan.'], 404);
        }

        if ((int) $logbook->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Anda tidak memiliki izin mengubah catatan ini.'], 403);
        }

        if ($logbook->status === 'Disetujui') {
            return response()->json(['message' => 'Logbook yang sudah disetujui tidak dapat diubah.'], 422);
        }

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'jam_mulai' => ['nullable', 'string', 'max:10'],
            'jam_selesai' => ['nullable', 'string', 'max:10'],
            'kategori' => ['nullable', 'string', 'max:50'],
            'judul_kegiatan' => ['required', 'string', 'max:255'],
            'deskripsi_kegiatan' => ['required', 'string'],
            'foto_kegiatan' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'link_video' => ['nullable', 'url:http,https', 'max:500'],
            'remove_foto' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_foto') && $logbook->foto_kegiatan) {
            Storage::disk('public')->delete($logbook->foto_kegiatan);
            $logbook->foto_kegiatan = null;
        }

        if ($request->hasFile('foto_kegiatan')) {
            if ($logbook->foto_kegiatan) {
                Storage::disk('public')->delete($logbook->foto_kegiatan);
            }
            $logbook->foto_kegiatan = $request->file('foto_kegiatan')->store('logbook', 'public');
        }

        $logbook->fill([
            'tanggal' => $validated['tanggal'],
            'jam_mulai' => $validated['jam_mulai'] ?? null,
            'jam_selesai' => $validated['jam_selesai'] ?? null,
            'kategori' => $validated['kategori'] ?: 'Teknis',
            'judul_kegiatan' => $validated['judul_kegiatan'],
            'deskripsi_kegiatan' => $validated['deskripsi_kegiatan'],
            'link_video' => $request->has('link_video')
                ? (($linkVideo = trim((string) ($validated['link_video'] ?? ''))) !== '' ? $linkVideo : null)
                : $logbook->link_video,
            'status' => 'Menunggu Verifikasi',
        ]);
        $logbook->save();

        return response()->json([
            'success' => true,
            'message' => 'Catatan logbook harian berhasil diperbarui.',
            'data' => $logbook->fresh(['user:id,name,nim', 'verifier:id,name']),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $logbook = Logbook::find($id);
        if (! $logbook) {
            return response()->json(['message' => 'Catatan logbook tidak ditemukan.'], 404);
        }

        $user = $request->user();
        $isAdmin = $this->isAdminRole($user?->role);

        if (! $isAdmin) {
            if ((int) $logbook->user_id !== (int) $user->id) {
                return response()->json(['message' => 'Anda tidak memiliki izin menghapus catatan ini.'], 403);
            }
            if ($logbook->status === 'Disetujui') {
                return response()->json(['message' => 'Logbook yang sudah disetujui tidak dapat dihapus.'], 422);
            }
        }

        if ($logbook->foto_kegiatan) {
            Storage::disk('public')->delete($logbook->foto_kegiatan);
        }

        $logbook->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catatan logbook berhasil dihapus.',
        ]);
    }

    public function verify(Request $request, int $id): JsonResponse
    {
        $logbook = Logbook::find($id);
        if (! $logbook) {
            return response()->json(['message' => 'Catatan logbook tidak ditemukan.'], 404);
        }

        $user = $request->user();
        if ($this->isSupervisorRole($user?->role)) {
            $assignedIds = $this->assignedParticipantIds($user->id);
            if (! $assignedIds->contains((int) $logbook->user_id)) {
                return response()->json(['message' => 'Peserta ini bukan peserta bimbingan Anda.'], 403);
            }
        }

        $validated = $request->validate([
            'status' => ['required', 'in:Disetujui,Perlu Revisi,Menunggu Verifikasi'],
            'catatan_pembimbing' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['status'] === 'Perlu Revisi' && trim((string) ($validated['catatan_pembimbing'] ?? '')) === '') {
            return response()->json([
                'message' => 'Catatan pembimbing wajib diisi saat meminta revisi logbook.',
            ], 422);
        }

        $logbook->update([
            'status' => $validated['status'],
            'catatan_pembimbing' => $validated['catatan_pembimbing'] ?? null,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status verifikasi logbook berhasil diperbarui.',
            'data' => $logbook->fresh(['user:id,name,nim', 'verifier:id,name']),
        ]);
    }
}

