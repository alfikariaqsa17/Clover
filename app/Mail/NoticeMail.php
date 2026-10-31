<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Pemberitahuan umum: pembatalan, retur, refund, jadwal ulang, tanggal libur, dsb. */
class NoticeMail extends Mailable
{
    /** @param array<int,string> $lines */
    public function __construct(
        public string $recipientName,
        public string $subjectLine,
        public string $heading,
        public array $lines,
        public ?string $ctaLabel = null,
        public ?string $ctaUrl = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $replyTo = config('clover.mail_reply_to');

        return new Envelope(
            subject: $this->subjectLine . ' — Clover',
            replyTo: $replyTo ? [new Address($replyTo, 'Clover')] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notice', text: 'emails.notice-text');
    }
}
