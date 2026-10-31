<?php

namespace App\Support;

use App\Mail\NoticeMail;
use App\Mail\OrderConfirmationMail;
use App\Mail\ReceiptMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Pengiriman email transaksi yang TIDAK PERNAH menggagalkan proses utama.
 * Bila SMTP bermasalah, pesanan tetap tersimpan; kegagalan dicatat di storage/logs.
 */
class SafeMail
{
    public static function send(?string $to, Mailable $mail, string $what): bool
    {
        if (! $to) {
            return false;
        }
        try {
            Mail::to($to)->send($mail);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Email "' . $what . '" gagal dikirim ke ' . $to . ': ' . $e->getMessage());

            return false;
        }
    }

    public static function orderConfirmation(Order $order): bool
    {
        $order->loadMissing(['items', 'payment', 'shipment', 'user']);

        return self::send($order->user?->email, new OrderConfirmationMail($order), 'konfirmasi pesanan ' . $order->number);
    }

    public static function receipt(Order $order): bool
    {
        $order->loadMissing(['items', 'payment', 'shipment', 'user']);

        return self::send($order->user?->email, new ReceiptMail($order), 'kuitansi ' . $order->number);
    }

    /** Email pemberitahuan umum (pembatalan, refund, retur, jadwal ulang, dsb). */
    public static function notice(?User $user, string $subject, string $heading, array $lines, ?string $ctaLabel = null, ?string $ctaUrl = null): bool
    {
        if (! $user) {
            return false;
        }

        return self::send($user->email, new NoticeMail($user->name, $subject, $heading, $lines, $ctaLabel, $ctaUrl), $subject);
    }
}
