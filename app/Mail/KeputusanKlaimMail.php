<?php

namespace App\Mail;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KeputusanKlaimMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Buat email keputusan verifikasi klaim (disetujui, ditolak, atau dicabut).
     *
     * @param  'approved'|'rejected'|'revoked'  $decision
     */
    public function __construct(
        public Claim $claim,
        public string $decision,
        public ?string $adminNote = null,
    ) {}

    /**
     * Susun subjek email sesuai keputusan verifikasi klaim.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: match ($this->decision) {
                'approved' => 'Klaim Barang Temuan Disetujui',
                'revoked' => 'Klaim Barang Temuan Dicabut',
                default => 'Klaim Barang Temuan Tidak Disetujui',
            },
        );
    }

    /**
     * Tentukan template HTML dan teks untuk isi email keputusan klaim.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verifikasi.klaim',
            text: 'emails.verifikasi.klaim-text',
        );
    }
}
