<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Penerimaan Magang</title>
</head>
<body style="margin:0;padding:24px 12px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#172033;">
    @php
        $periode = $pendaftaran->periode;
        $rentangPeriode = $periode && $periode->tanggal_mulai && $periode->tanggal_selesai
            ? \Illuminate\Support\Carbon::parse($periode->tanggal_mulai)->locale('id')->translatedFormat('d F Y')
                .' - '.\Illuminate\Support\Carbon::parse($periode->tanggal_selesai)->locale('id')->translatedFormat('d F Y')
            : 'Silakan hubungi admin untuk memastikan jadwal periode magang.';
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
        <tr>
            <td style="padding:28px 32px;background:#1e3a8a;color:#ffffff;">
                <div style="font-size:12px;font-weight:700;letter-spacing:1.5px;color:#bfdbfe;">PAM TECHNO · CV. PUTRA ANUGRAH MANDIRI</div>
                <h1 style="margin:12px 0 0;font-size:24px;line-height:1.3;">Selamat, Anda diterima!</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:28px 32px;">
                <p style="margin:0 0 12px;font-size:15px;line-height:1.7;">Yth. {{ $pendaftaran->user->name ?? $pendaftaran->user->nama ?? 'Peserta Magang' }},</p>
                <p style="margin:0 0 18px;font-size:14px;line-height:1.7;color:#475569;">
                    Dengan senang hati kami menyampaikan bahwa pengajuan magang Anda di PAM TECHNO telah diterima. Rincian periode magang Anda:
                </p>

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 22px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
                    <tr>
                        <td style="padding:16px 18px;">
                            <div style="font-size:11px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;color:#64748b;">Periode magang</div>
                            <div style="margin-top:5px;font-size:16px;font-weight:700;color:#1e3a8a;">{{ $periode->nama_periode ?? 'Periode magang' }}</div>
                            <div style="margin-top:4px;font-size:13px;color:#475569;">{{ $rentangPeriode }}</div>
                        </td>
                    </tr>
                </table>

                <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#475569;">
                    Surat penerimaan resmi terlampir pada email ini. Anda juga dapat masuk ke portal peserta untuk melihat informasi dan mengunduh kembali surat tersebut.
                </p>

                <a href="{{ $loginUrl }}" style="display:inline-block;padding:12px 20px;background:#2563eb;border-radius:8px;color:#ffffff;text-decoration:none;font-size:13px;font-weight:700;">Masuk ke portal magang</a>

                <p style="margin:24px 0 0;font-size:14px;line-height:1.7;color:#475569;">
                    Selamat bergabung. Kami menantikan semangat dan kontribusi Anda selama program berlangsung.
                </p>
                <p style="margin:16px 0 0;font-size:14px;line-height:1.7;">Hormat kami,<br><strong>Admin Penerimaan Magang</strong><br>PAM TECHNO</p>
            </td>
        </tr>
        <tr>
            <td style="padding:18px 32px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:11px;line-height:1.6;color:#64748b;">
                Email ini dikirim otomatis oleh Portal Magang PAM TECHNO. Simpan email dan lampiran surat penerimaan ini sebagai arsip Anda.
            </td>
        </tr>
    </table>
</body>
</html>
