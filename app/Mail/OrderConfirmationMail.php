<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Email konfirmasi saat pesanan dibuat. Tidak diantrekan (lihat SafeMail untuk penanganan kegagalan). */
class OrderConfirmationMail extends Mailable
{
    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        $replyTo = config('clover.mail_reply_to');

        return new Envelope(
            subject: 'Pesanan ' . $this->order->number . ' diterima — Clover',
            replyTo: $replyTo ? [new Address($replyTo, 'Clover')] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-confirmation', text: 'emails.order-confirmation-text');
    }
}
