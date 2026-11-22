<?php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use Illuminate\Support\Facades\Log;

class GmailApiMailer
{
    protected Client $client;
    protected Gmail $gmail;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(storage_path('app/google/credentials.json'));
        $this->client->setScopes([Gmail::GMAIL_SEND]);
        $this->client->setAccessType('offline');

        $tokenPath = storage_path('app/google/token.json');
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $this->client->setAccessToken($accessToken);
        }

        // Refresh token if expired
        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                file_put_contents($tokenPath, json_encode($newToken));
            }
        }

        $this->gmail = new Gmail($this->client);
    }

    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $fromName = null,
        ?string $attachmentPath = null,
        ?string $attachmentName = null,
    ): bool
    {
        try {
            $from = config('mail.from.address');
            $name = $fromName ?? config('mail.from.name');
            $headers = "From: {$name} <{$from}>\r\n"
                . "To: {$to}\r\n"
                . "Subject: =?utf-8?B?" . base64_encode($subject) . "?=\r\n"
                . "MIME-Version: 1.0\r\n";

            if ($attachmentPath === null) {
                $rawMessage = $headers
                    . "Content-Type: text/html; charset=utf-8\r\n"
                    . "Content-Transfer-Encoding: base64\r\n\r\n"
                    . base64_encode($htmlBody);
            } else {
                if (! is_file($attachmentPath) || ! is_readable($attachmentPath)) {
                    throw new \RuntimeException('Email attachment is missing or unreadable.');
                }
                $attachmentContents = file_get_contents($attachmentPath);
                if ($attachmentContents === false) {
                    throw new \RuntimeException('Email attachment could not be read.');
                }

                $boundary = '=_mixed_' . bin2hex(random_bytes(12));
                $safeName = addcslashes($attachmentName ?? basename($attachmentPath), "\"\\");
                $rawMessage = $headers
                    . "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n\r\n"
                    . "--{$boundary}\r\n"
                    . "Content-Type: text/html; charset=utf-8\r\n"
                    . "Content-Transfer-Encoding: base64\r\n\r\n"
                    . chunk_split(base64_encode($htmlBody), 76, "\r\n")
                    . "--{$boundary}\r\n"
                    . "Content-Type: application/pdf; name=\"{$safeName}\"\r\n"
                    . "Content-Disposition: attachment; filename=\"{$safeName}\"\r\n"
                    . "Content-Transfer-Encoding: base64\r\n\r\n"
                    . chunk_split(base64_encode($attachmentContents), 76, "\r\n")
                    . "--{$boundary}--";
            }

            $encodedMessage = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

            $message = new \Google\Service\Gmail\Message();
            $message->setRaw($encodedMessage);

            $this->gmail->users_messages->send('me', $message);
            return true;
        } catch (\Throwable $e) {
            Log::error('Gmail API send failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
