<?php

namespace App\Mail;

use App\Models\Pendaftaran;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PendaftaranBaruMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Pendaftaran $pendaftaran,
        public string $fileSuratPengantarUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pendaftaran Magang Baru - ' . ($this->pendaftaran->user->name ?? 'Peserta'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pendaftaran-baru',
            with: [
                'loginUrl' => rtrim((string) config('services.frontend.url'), '/') . '/login',
            ],
        );
    }
}
