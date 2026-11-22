<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 35px 48px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 12px; line-height: 1.65; }
        .letterhead { border-bottom: 3px double #183b68; padding-bottom: 12px; margin-bottom: 24px; }
        .letterhead-table { width: 100%; border-collapse: collapse; }
        .letterhead-table td { vertical-align: middle; }
        .logo-cell { width: 86px; }
        .logo { width: 76px; height: 76px; }
        .letterhead-content { text-align: center; }
        .brand { color: #183b68; font-size: 22px; font-weight: bold; letter-spacing: 2px; }
        .company { font-size: 14px; font-weight: bold; margin-top: 2px; }
        .address { font-size: 10px; margin-top: 5px; }
        .date { text-align: right; margin: 20px 0; }
        .title { text-align: center; font-size: 15px; font-weight: bold; text-decoration: underline; margin: 24px 0; }
        .identity { margin: 18px 0 18px 28px; }
        .identity td { padding: 2px 6px 2px 0; vertical-align: top; }
        .signature { margin-top: 38px; margin-left: 60%; }
        .signature-name { margin-top: 58px; font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="letterhead">
        <table class="letterhead-table">
            <tr>
                <td class="logo-cell">
                    <img class="logo" src="{{ public_path('images/pam-techno-logo.png') }}" alt="Logo PAM TECHNO">
                </td>
                <td class="letterhead-content">
                    <div class="brand">PAM TECHNO</div>
                    <div class="company">CV. Putra Anugrah Mandiri</div>
                    <div class="address">Jl. M. Yunus No. 6, Lubuk Lintah, Kec. Kuranji,<br>Kota Padang, Sumatera Barat 25153</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="date">Padang, {{ $tanggalSurat->locale('id')->translatedFormat('d F Y') }}</div>
    <div class="title">SURAT PENERIMAAN MAGANG</div>

    <p>Dengan hormat,</p>
    <p>
        Berdasarkan surat permohonan magang kerja yang diajukan oleh mahasiswa dari
        <strong>{{ $pendaftaran->asal_instansi ?: '-' }}</strong> kepada PAM TECHNO, dengan ini kami memberitahukan
        bahwa permohonan magang kerja tersebut dapat kami terima. Adapun data peserta adalah sebagai berikut:
    </p>

    <table class="identity">
        <tr><td>Nama</td><td>:</td><td>{{ $pendaftaran->user->name ?? $pendaftaran->user->nama ?? '-' }}</td></tr>
        <tr><td>NIM</td><td>:</td><td>{{ $pendaftaran->user->nim ?? '-' }}</td></tr>
        <tr>
            <td>Periode magang</td><td>:</td>
            <td>
                {{ $pendaftaran->periode->nama_periode ?? 'Belum ditentukan' }}
                @if ($pendaftaran->periode?->tanggal_mulai && $pendaftaran->periode?->tanggal_selesai)
                    <br>
                    {{ \Illuminate\Support\Carbon::parse($pendaftaran->periode->tanggal_mulai)->locale('id')->translatedFormat('d F Y') }}
                    s.d.
                    {{ \Illuminate\Support\Carbon::parse($pendaftaran->periode->tanggal_selesai)->locale('id')->translatedFormat('d F Y') }}
                @endif
            </td>
        </tr>
    </table>

    <p>
        Dalam pelaksanaan magang kerja, peserta dimohon untuk menaati seluruh peraturan dan tata tertib
        yang berlaku di perusahaan.
    </p>
    <p>Demikian surat ini kami sampaikan. Atas perhatian dan kerja sama yang baik, kami ucapkan terima kasih.</p>

    <div class="signature">
        <div>Hormat kami,</div>
        <div>PAM TECHNO</div>
        <div class="signature-name">Admin Penerimaan Magang</div>
    </div>
</body>
</html>
