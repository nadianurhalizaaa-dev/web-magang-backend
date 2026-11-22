<?php

namespace App\Mail;

use App\Models\Pendaftaran;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuratPenerimaanMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Pendaftaran $pendaftaran,
        public string $fileSurat,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Surat Penerimaan Magang - ' . ($this->pendaftaran->user->name ?? 'Peserta'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.surat-penerimaan',
            with: [
                'loginUrl' => rtrim((string) config('services.frontend.url'), '/') . '/login',
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('public', $this->fileSurat)
                ->as('Surat_Penerimaan_Magang.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
