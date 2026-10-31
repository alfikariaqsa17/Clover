<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Kuitansi pembayaran, dikirim saat pembayaran lunas (transfer terverifikasi / COD diterima). */
class ReceiptMail extends Mailable
{
    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        $replyTo = config('clover.mail_reply_to');

        return new Envelope(
            subject: 'Kuitansi ' . $this->order->receipt_number . ' — pesanan ' . $this->order->number,
            replyTo: $replyTo ? [new Address($replyTo, 'Clover')] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.receipt', text: 'emails.receipt-text');
    }
}
