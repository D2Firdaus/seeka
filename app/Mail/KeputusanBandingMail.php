<?php

namespace App\Mail;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KeputusanBandingMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Buat email keputusan banding (diterima atau ditolak).
     *
     * @param  'resolved'|'dismissed'  $decision
     */
    public function __construct(
        public Dispute $dispute,
        public string $decision,
        public ?string $resolutionNote = null,
    ) {}

    /**
     * Susun subjek email sesuai keputusan banding.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: match ($this->decision) {
                'resolved' => 'Pengajuan Banding Diterima',
                default => 'Pengajuan Banding Ditolak',
            },
        );
    }

    /**
     * Tentukan template HTML dan teks untuk isi email keputusan banding.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verifikasi.banding',
            text: 'emails.verifikasi.banding-text',
        );
    }
}
