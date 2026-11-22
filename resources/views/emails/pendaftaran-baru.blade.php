<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pendaftaran Magang Baru</title>
</head>
<body style="margin:0;padding:24px 12px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#172033;">
    @php
        $periode = $pendaftaran->periode;
        $rentangPeriode = $periode && $periode->tanggal_mulai && $periode->tanggal_selesai
            ? \Illuminate\Support\Carbon::parse($periode->tanggal_mulai)->locale('id')->translatedFormat('d F Y')
                .' - '.\Illuminate\Support\Carbon::parse($periode->tanggal_selesai)->locale('id')->translatedFormat('d F Y')
            : 'Tanggal periode belum tersedia';
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
        <tr>
            <td style="padding:28px 32px;background:#1e3a8a;color:#ffffff;">
                <div style="font-size:12px;font-weight:700;letter-spacing:1.5px;color:#bfdbfe;">PAM TECHNO · PORTAL MAGANG</div>
                <h1 style="margin:12px 0 0;font-size:24px;line-height:1.3;">Pendaftaran magang baru</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:28px 32px;">
                <p style="margin:0 0 12px;font-size:15px;line-height:1.7;">Halo Admin,</p>
                <p style="margin:0 0 22px;font-size:14px;line-height:1.7;color:#475569;">
                    Seorang calon peserta telah mengirimkan pengajuan magang. Mohon tinjau data dan dokumen berikut melalui portal admin.
                </p>

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:13px;">
                    <tr><td style="padding:10px 12px;width:155px;background:#f8fafc;color:#64748b;font-weight:700;">Nama peserta</td><td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;">{{ $pendaftaran->user->name ?? $pendaftaran->user->nama ?? 'Peserta' }}</td></tr>
                    <tr><td style="padding:10px 12px;background:#f8fafc;color:#64748b;font-weight:700;">Email</td><td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;">{{ $pendaftaran->user->email ?? '-' }}</td></tr>
                    <tr><td style="padding:10px 12px;background:#f8fafc;color:#64748b;font-weight:700;">NIM</td><td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;">{{ $pendaftaran->user->nim ?: '-' }}</td></tr>
                    <tr><td style="padding:10px 12px;background:#f8fafc;color:#64748b;font-weight:700;">Asal kampus</td><td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;">{{ $pendaftaran->asal_instansi ?: '-' }}</td></tr>
                    <tr><td style="padding:10px 12px;background:#f8fafc;color:#64748b;font-weight:700;">Program studi</td><td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;">{{ $pendaftaran->user->program_studi ?: '-' }}</td></tr>
                    <tr><td style="padding:10px 12px;background:#f8fafc;color:#64748b;font-weight:700;">Periode magang</td><td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;">{{ $periode->nama_periode ?? 'Periode belum ditentukan' }}<br><span style="color:#64748b;">{{ $rentangPeriode }}</span></td></tr>
                    <tr><td style="padding:10px 12px;background:#f8fafc;color:#64748b;font-weight:700;">Tanggal pengajuan</td><td style="padding:10px 12px;">{{ $pendaftaran->tanggal_daftar ? \Illuminate\Support\Carbon::parse($pendaftaran->tanggal_daftar)->locale('id')->translatedFormat('d F Y, H:i') : '-' }}</td></tr>
                </table>

                <div style="margin:24px 0 0;">
                    <a href="{{ url($fileSuratPengantarUrl) }}" style="display:inline-block;margin:0 8px 8px 0;padding:12px 18px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;color:#1d4ed8;text-decoration:none;font-size:13px;font-weight:700;">Lihat surat pengantar</a>
                    <a href="{{ $loginUrl }}" style="display:inline-block;margin:0 0 8px;padding:12px 18px;background:#2563eb;border-radius:8px;color:#ffffff;text-decoration:none;font-size:13px;font-weight:700;">Masuk ke halaman admin</a>
                </div>
                <p style="margin:14px 0 0;font-size:12px;line-height:1.6;color:#64748b;">
                    Tautan admin akan membuka halaman masuk portal. Gunakan akun admin Anda untuk melanjutkan verifikasi.
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding:18px 32px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:11px;line-height:1.6;color:#64748b;">
                Email ini dikirim otomatis oleh Portal Magang PAM TECHNO. Mohon tidak membalas email ini.
            </td>
        </tr>
    </table>
</body>
</html>
