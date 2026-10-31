<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Email OTP. Sengaja TIDAK memakai antrean: status pengiriman harus akurat saat itu juga. */
class VerificationCodeMail extends Mailable
{
    public function __construct(
        public User $user,
        public string $code,
        public int $minutes = 10,
    ) {
    }

    public function envelope(): Envelope
    {
        $replyTo = config('clover.mail_reply_to');

        return new Envelope(
            subject: 'Kode verifikasi akun Clover',
            replyTo: $replyTo ? [new Address($replyTo, 'Clover')] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verification-code',
            text: 'emails.verification-code-text',
        );
    }
}
