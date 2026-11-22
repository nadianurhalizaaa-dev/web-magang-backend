<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pendaftaran;
use App\Mail\PendaftaranBaruMail;
use App\Mail\SuratPenerimaanMail;
use App\Services\GmailApiMailer;
use App\Services\CompressionSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Services\WhatsAppService;

class PendaftaranController extends Controller
{
    // Admin views all (urutkan dari yang terbaru di paling atas)
    public function index()
    {
        $pendaftarans = Pendaftaran::with(['user', 'periode', 'pembimbing'])
            ->latest('id')
            ->get();
        return response()->json(['data' => $pendaftarans]);
    }

    // User views own registration
    public function myRegistration()
    {
        $user = Auth::user();
        $pendaftaran = Pendaftaran::with(['user', 'periode', 'pembimbing'])->where('user_id', $user->id)->first();
        return response()->json(['data' => $pendaftaran]);
    }

    public function downloadReplyLetter()
    {
        $pendaftaran = Pendaftaran::query()
            ->where('user_id', Auth::id())
            ->where('status', 'Pemagang Aktif')
            ->latest('id')
            ->first();

        if (! $pendaftaran || ! $pendaftaran->file_surat_balasan) {
            return response()->json(['message' => 'Surat balasan belum tersedia.'], 404);
        }

        $storedPath = $pendaftaran->file_surat_balasan;
        if (! str_starts_with($storedPath, 'storage/surat_balasan/')) {
            return response()->json(['message' => 'Lokasi surat balasan tidak valid.'], 404);
        }

        $relativePath = 'surat_balasan/' . basename($storedPath);
        $disk = Storage::disk('public');
        if (! $disk->exists($relativePath)) {
            $disk = Storage::disk('local');
            $relativePath = 'public/' . $relativePath;
            if (! $disk->exists($relativePath)) {
                return response()->json(['message' => 'Berkas surat balasan tidak ditemukan.'], 404);
            }
        }

        if (empty($pendaftaran->surat_balasan_diterima_at)) {
            $pendaftaran->surat_balasan_diterima_at = now();
            $pendaftaran->save();
        }

        return $disk->download($relativePath, basename($relativePath));
    }

    // Pemagang submit registration
    public function store(Request $request, CompressionSettings $compressionSettings)
    {
        $settings = $compressionSettings->get();
        $maxFileSizeKb = $settings['max_file_size_mb'] * 1024;

        $request->validate([
            'periode_id' => 'required|exists:periode_magangs,id',
            'nim' => 'nullable|string|max:50',
            'asal_instansi' => 'required|string|max:150',
            'no_hp' => 'required|string|max:20',
            'program_studi' => 'required|string|max:150',
            'alamat' => 'required|string|max:1000',
            'pas_foto' => 'required|image|mimes:jpg,jpeg,png|max:' . $settings['photo_target_size_kb'],
            'file_surat_pengantar' => 'required|file|mimes:pdf|max:' . $maxFileSizeKb,
        ]);

        $user = Auth::user();

        // Check if already registered
        $existing = Pendaftaran::where('user_id', $user->id)->first();
        if ($existing) {
            return response()->json(['message' => 'Anda sudah mendaftar magang'], 400);
        }

        $photoPath = $request->file('pas_foto')->store('pas_foto', 'public');

        $userUpdate = [
            'no_hp' => $request->input('no_hp'),
            'program_studi' => $request->input('program_studi'),
            'alamat' => $request->input('alamat'),
            'foto' => $photoPath,
        ];
        if (! empty($request->input('nim'))) {
            $userUpdate['nim'] = trim($request->input('nim'));
        }
        $user->update($userUpdate);

        // Handle file upload
        $file = $request->file('file_surat_pengantar');
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->storeAs('surat_pengantar', $filename, 'public');

        $pendaftaran = Pendaftaran::create([
            'user_id' => $user->id,
            'periode_id' => $request->periode_id,
            'asal_instansi' => $request->asal_instansi,
            'file_surat_pengantar' => 'storage/surat_pengantar/' . $filename,
            'tanggal_daftar' => now(),
            'status' => 'Calon Pemagang',
        ]);
        $pendaftaran->load(['user', 'periode', 'pembimbing']);

        $namaPeserta = $user->name ?? $user->nama ?? 'Peserta';
        $periodeName = $pendaftaran->periode
            ? $pendaftaran->periode->nama_periode
            : 'Tidak ditentukan';
        $adminWaNotification = WhatsAppService::createAdminRegistrationLink(
            (string) config('services.whatsapp.admin_number'),
            $namaPeserta,
            $user->nim,
            $pendaftaran->asal_instansi,
            $periodeName,
        );

        $emailNotification = ['sent' => false];
        $adminEmail = config('mail.admin_address');
        try {
            Mail::to($adminEmail)->send(new PendaftaranBaruMail(
                $pendaftaran,
                Storage::disk('public')->url('surat_pengantar/' . $filename),
            ));
            $emailNotification['sent'] = true;
        } catch (\Throwable $smtpException) {
            Log::warning('SMTP failed, trying Gmail API fallback.', [
                'error' => $smtpException->getMessage(),
            ]);

            // Fallback: Gmail API via HTTPS (bypass ISP SMTP block)
            try {
                $credentialsPath = storage_path('app/google/credentials.json');
                $tokenPath       = storage_path('app/google/token.json');

                if (file_exists($credentialsPath) && file_exists($tokenPath)) {
                    $subject  = 'Pendaftaran Magang Baru - ' . ($pendaftaran->user->name ?? 'Peserta');
                    $htmlBody = view('emails.pendaftaran-baru', [
                        'pendaftaran'          => $pendaftaran,
                        'fileSuratPengantarUrl' => Storage::disk('public')->url('surat_pengantar/' . $filename),
                        'loginUrl' => rtrim((string) config('services.frontend.url'), '/') . '/login',
                    ])->render();

                    $gmailApi = new GmailApiMailer();
                    $sent     = $gmailApi->send($adminEmail, $subject, $htmlBody);

                    if ($sent) {
                        $emailNotification['sent']   = true;
                        $emailNotification['method'] = 'gmail_api';
                    } else {
                        throw new \Exception('Gmail API send returned false');
                    }
                } else {
                    throw new \Exception('Gmail API credentials/token not found. Run: php artisan gmail:setup');
                }
            } catch (\Throwable $apiException) {
                Log::error('Failed to send email via SMTP and Gmail API.', [
                    'registration_id' => $pendaftaran->id,
                    'recipient'       => $adminEmail,
                    'smtp_error'      => $smtpException->getMessage(),
                    'api_error'       => $apiException->getMessage(),
                ]);
                $emailNotification['message'] = 'Pendaftaran tersimpan, tetapi email notifikasi admin gagal dikirim.';
            }
        }

        return response()->json([
            'data' => $pendaftaran,
            'message' => 'Pendaftaran magang berhasil dikirim!',
            'admin_whatsapp_notification' => $adminWaNotification,
            'email_notification' => $emailNotification,
        ]);
    }

    // Admin updates status
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:Calon Pemagang,Pemagang Aktif,Ditolak,Alumni',
            'pembimbing_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'pembimbing')),
            ],
            'tanggal_selesai' => 'nullable|date',
            'file_surat_balasan' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $pendaftaran = Pendaftaran::findOrFail($id);
        $statusSebelumnya = $pendaftaran->status;
        $baruDiterima = $statusSebelumnya !== 'Pemagang Aktif'
            && $request->status === 'Pemagang Aktif';
        $pendaftaran->status = $request->status;
        $pendaftaran->pembimbing_id = $request->pembimbing_id;
        $pendaftaran->tanggal_selesai = $request->tanggal_selesai;

        $suratBaru = false;
        if ($request->hasFile('file_surat_balasan')) {
            $file = $request->file('file_surat_balasan');
            $filename = 'surat-penerimaan-' . $pendaftaran->id . '-' . now()->format('YmdHis') . '.pdf';
            $file->storeAs('surat_balasan', $filename, 'public');
            $pendaftaran->file_surat_balasan = 'storage/surat_balasan/' . $filename;
            $pendaftaran->surat_balasan_diterima_at = null;
            $pendaftaran->surat_balasan_email_terkirim_at = null;
            $pendaftaran->surat_balasan_whatsapp_terkirim_at = null;
            $suratBaru = true;
        } elseif ($request->status === 'Pemagang Aktif') {
            if ($baruDiterima) {
                $pendaftaran->surat_balasan_email_terkirim_at = null;
                $pendaftaran->surat_balasan_whatsapp_terkirim_at = null;
            }
            $relativePath = $pendaftaran->file_surat_balasan
                ? 'surat_balasan/' . basename($pendaftaran->file_surat_balasan)
                : null;
            $suratTersedia = $relativePath && Storage::disk('public')->exists($relativePath);

            if ($baruDiterima || ! $suratTersedia) {
                $pendaftaran->load(['user', 'periode']);
                $filename = 'surat-penerimaan-' . $pendaftaran->id . '-' . now()->format('YmdHis') . '.pdf';
                $relativePath = 'surat_balasan/' . $filename;
                $pdf = Pdf::loadView('surat.penerimaan', [
                    'pendaftaran' => $pendaftaran,
                    'tanggalSurat' => now(),
                ])->setPaper('a4');
                Storage::disk('public')->put($relativePath, $pdf->output());
                $pendaftaran->file_surat_balasan = 'storage/' . $relativePath;
                $pendaftaran->surat_balasan_diterima_at = null;
                $suratBaru = true;
                $pendaftaran->surat_balasan_whatsapp_terkirim_at = null;
            }
        }

        $pendaftaran->save();

        $emailNotification = ['sent' => false];
        $perluKirimEmail = $suratBaru || empty($pendaftaran->surat_balasan_email_terkirim_at);
        if ($request->status === 'Pemagang Aktif' && $perluKirimEmail) {
            $pendaftaran->loadMissing(['user', 'periode']);
            $recipient = $pendaftaran->user->email ?? null;
            $relativePath = 'surat_balasan/' . basename($pendaftaran->file_surat_balasan);

            try {
                if (! $recipient) {
                    throw new \RuntimeException('Email peserta tidak tersedia.');
                }

                Mail::to($recipient)->send(new SuratPenerimaanMail($pendaftaran, $relativePath));
                $emailNotification['sent'] = true;
            } catch (\Throwable $smtpException) {
                Log::warning('SMTP failed for acceptance letter, trying Gmail API fallback.', [
                    'registration_id' => $pendaftaran->id,
                    'error' => $smtpException->getMessage(),
                ]);

                try {
                    $credentialsPath = storage_path('app/google/credentials.json');
                    $tokenPath = storage_path('app/google/token.json');
                    if (! file_exists($credentialsPath) || ! file_exists($tokenPath)) {
                        throw new \RuntimeException('Gmail API credentials/token not found. Run: php artisan gmail:setup');
                    }

                    $htmlBody = view('emails.surat-penerimaan', [
                        'pendaftaran' => $pendaftaran,
                        'loginUrl' => rtrim((string) config('services.frontend.url'), '/') . '/login',
                    ])->render();
                    $sent = (new GmailApiMailer())->send(
                        $recipient,
                        'Surat Penerimaan Magang - ' . ($pendaftaran->user->name ?? 'Peserta'),
                        $htmlBody,
                        null,
                        Storage::disk('public')->path($relativePath),
                        'Surat_Penerimaan_Magang.pdf',
                    );

                    if (! $sent) {
                        throw new \RuntimeException('Gmail API send returned false.');
                    }

                    $emailNotification['sent'] = true;
                    $emailNotification['method'] = 'gmail_api';
                } catch (\Throwable $apiException) {
                    Log::error('Failed to send acceptance letter via SMTP and Gmail API.', [
                        'registration_id' => $pendaftaran->id,
                        'recipient' => $recipient,
                        'smtp_error' => $smtpException->getMessage(),
                        'api_error' => $apiException->getMessage(),
                    ]);
                    $emailNotification['message'] = 'Surat tersimpan, tetapi email peserta gagal dikirim. Admin dapat menyimpan ulang untuk mencoba lagi.';
                }
            }

            if ($emailNotification['sent']) {
                $pendaftaran->surat_balasan_email_terkirim_at = now();
                $pendaftaran->save();
            }
        } elseif ($request->status === 'Pemagang Aktif') {
            $emailNotification['already_sent'] = true;
        }

        $whatsappNotification = ['manual' => false];
        if ($request->status === 'Pemagang Aktif') {
            $pendaftaran->loadMissing(['user']);
            $whatsappNotification = WhatsAppService::createAcceptanceLink(
                (string) ($pendaftaran->user->no_hp ?? ''),
                $pendaftaran->user->name ?? $pendaftaran->user->nama ?? 'Peserta',
            );
        }

        return response()->json([
            'data' => $pendaftaran,
            'message' => 'Status pendaftaran berhasil diupdate',
            'email_notification' => $emailNotification,
            'whatsapp_notification' => $whatsappNotification,
        ]);
    }

    // Admin membatalkan/menghapus pendaftaran
    public function cancelRegistration()
    {
        $user = Auth::user();

        $pendaftaran = Pendaftaran::where('user_id', $user->id)->first();

        if (!$pendaftaran) {
            return response()->json(['message' => 'Data pendaftaran tidak ditemukan'], 404);
        }

        if ($pendaftaran->status != 'Calon Pemagang') {
            return response()->json(['message' => 'Tidak dapat membatalkan pendaftaran pada tahap ini'], 400);
        }

        $pendaftaran->status = 'Dibatalkan';
        $pendaftaran->save();

        return response()->json([
            'data' => $pendaftaran,
            'message' => 'Pengajuan magang berhasil dibatalkan'
        ]);
    }

    // Admin menghapus data pendaftaran secara permanen
    public function destroy($id)
    {
        $pendaftaran = Pendaftaran::findOrFail($id);

        // Hapus file surat pengantar dari storage
        if ($pendaftaran->file_surat_pengantar) {
            $path = str_replace('storage/', '', $pendaftaran->file_surat_pengantar);
            Storage::disk('public')->delete($path);
        }

        // Hapus file surat balasan dari storage
        if ($pendaftaran->file_surat_balasan) {
            $path = str_replace('storage/', '', $pendaftaran->file_surat_balasan);
            Storage::disk('public')->delete($path);
        }

        $pendaftaran->delete();

        return response()->json(['message' => 'Data pendaftaran berhasil dihapus']);
    }
}
