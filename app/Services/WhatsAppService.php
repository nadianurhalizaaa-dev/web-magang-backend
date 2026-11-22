<?php

namespace App\Services;

class WhatsAppService
{
    public static function formatPhoneNumber(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleaned, '0')) {
            return '62'.substr($cleaned, 1);
        }

        if (str_starts_with($cleaned, '8')) {
            return '62'.$cleaned;
        }

        return $cleaned;
    }

    public static function createAcceptanceLink(string $phone, string $namaPeserta): array
    {
        $message = "Halo, {$namaPeserta}.\n\n"
            ."Selamat, pengajuan magang Anda di PAM TECHNO telah diterima.\n"
            ."Surat penerimaan resmi sudah tersedia di portal peserta.\n\n"
            ."Hormat kami,\nAdmin Penerimaan Magang\nPAM TECHNO";

        return self::createLink($phone, $message);
    }

    public static function createAdminRegistrationLink(
        string $phone,
        string $namaPeserta,
        ?string $nim,
        string $kampus,
        string $periode,
    ): array {
        $message = "Pemberitahuan pendaftaran magang baru di portal PAM TECHNO.\n\n"
            ."Nama: {$namaPeserta}\n"
            .'NIM: '.($nim ?: '-')."\n"
            ."Asal kampus: {$kampus}\n"
            ."Periode: {$periode}";

        return self::createLink($phone, $message);
    }

    private static function createLink(string $phone, string $message): array
    {
        $target = self::formatPhoneNumber($phone);
        if ($target === '') {
            return [
                'manual' => false,
                'message' => 'Nomor tujuan WhatsApp belum tersedia.',
            ];
        }

        return [
            'manual' => true,
            'sent' => false,
            'url' => 'https://wa.me/'.$target.'?text='.rawurlencode($message),
            'message' => 'Pesan belum terkirim. Tekan Kirim di WhatsApp untuk mengirimnya.',
        ];
    }
}
