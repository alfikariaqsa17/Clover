<?php

namespace App\Support;

use App\Models\PaymentProof;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Menyajikan bukti transaksi dari penyimpanan PRIVAT. Otorisasi dilakukan oleh pemanggil (controller). */
class ProofFile
{
    private const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

    public static function response(PaymentProof $proof): StreamedResponse
    {
        $disk = Storage::disk('local');
        abort_unless(in_array($proof->mime, self::ALLOWED, true) && $disk->exists($proof->path), 404);

        return $disk->response($proof->path, 'bukti-pembayaran-' . $proof->id, [
            'Content-Type' => $proof->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            // Defense in depth: file ini tidak boleh menjalankan skrip walau disalahgunakan.
            'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox",
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ], 'inline');
    }
}
