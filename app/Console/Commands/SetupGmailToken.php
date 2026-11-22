<?php

namespace App\Console\Commands;

use Google\Client;
use Google\Service\Gmail;
use Illuminate\Console\Command;
use RuntimeException;

class SetupGmailToken extends Command
{
    protected $signature = 'gmail:setup';

    protected $description = 'Setup Gmail API OAuth2 token for sending emails';

    public function handle(): int
    {
        $credentialsPath = storage_path('app/google/credentials.json');
        $tokenPath = storage_path('app/google/token.json');

        if (! file_exists($credentialsPath)) {
            $this->error('File credentials.json tidak ditemukan di: '.$credentialsPath);
            $this->info('Download OAuth client dari Google Cloud Console dan simpan di lokasi tersebut.');

            return self::FAILURE;
        }

        $client = new Client;
        $client->setAuthConfig($credentialsPath);
        $client->setScopes([Gmail::GMAIL_SEND]);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $authUrl = $client->createAuthUrl();

        $this->info('Buka URL berikut di browser:');
        $this->line($authUrl);
        $this->newLine();

        $code = $this->ask('Masukkan authorization code dari browser');

        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            $this->error('Google OAuth gagal: '.($token['error_description'] ?? $token['error']));

            return self::FAILURE;
        }

        $tokenDirectory = dirname($tokenPath);
        if (! is_dir($tokenDirectory) && ! mkdir($tokenDirectory, 0700, true) && ! is_dir($tokenDirectory)) {
            throw new RuntimeException('Tidak dapat membuat direktori penyimpanan token Gmail.');
        }

        $tokenJson = json_encode($token, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (file_put_contents($tokenPath, $tokenJson, LOCK_EX) === false) {
            throw new RuntimeException('Tidak dapat menyimpan token Gmail.');
        }

        $this->info('Token berhasil disimpan di: '.$tokenPath);
        $this->info('Sekarang Gmail API siap digunakan!');

        return self::SUCCESS;
    }
}
