<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Satu-satunya jalur untuk mengubah status pesanan (menjaga stok, pembayaran, pengiriman, refund, notifikasi). */
class OrderWorkflow
{
    public static function money(int $n): string
    {
        return 'Rp' . number_format($n, 0, ',', '.');
    }

    public static function newNumber(string $prefix): string
    {
        return $prefix . '-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
    }

    /**
     * @param  array{reason?:string,by?:string,bank?:array{bank_name:string,account_number:string,account_holder:string}}  $meta
     *         Dipakai saat 'Dibatalkan': alasan, pelaku (customer|admin), dan rekening refund bila sudah dibayar.
     * @return string|null pesan error, null bila berhasil
     */
    public static function transition(Order $order, string $to, array $meta = []): ?string
    {
        $error = null;
        $refund = null;
        $receiptNow = false;

        DB::transaction(function () use ($order, $to, $meta, &$error, &$refund, &$receiptNow) {
            // Baca ulang dengan kunci baris: mencegah dua aksi bersamaan (mis. batal + kirim) atau refund ganda.
            Order::whereKey($order->id)->lockForUpdate()->first();
            $order->refresh();

            if (! in_array($to, $order->nextStatuses(), true)) {
                $error = 'Perubahan status dari "' . $order->status . '" ke "' . $to . '" tidak diizinkan.';

                return;
            }

            $order->load('items', 'payment', 'shipment');
            $extra = [];

            if ($to === 'Diproses' && $order->payment && $order->payment->method === 'qris_gopay' && $order->payment->status !== 'paid') {
                $error = 'Pembayaran QRIS belum diverifikasi. Verifikasi dulu di menu Pembayaran QRIS.';

                return;
            }

            if ($to === 'Dibatalkan') {
                foreach ($order->items as $i) {
                    if ($i->product_id) {
                        Product::where('id', $i->product_id)->increment('stock', $i->qty);
                    }
                }
                if ($usage = CouponUsage::where('order_id', $order->id)->first()) {
                    Coupon::where('id', $usage->coupon_id)->where('used_count', '>', 0)->decrement('used_count');
                    $usage->delete();
                }
                if ($order->payment && in_array($order->payment->status, ['pending', 'awaiting_verification', 'rejected'], true)) {
                    $order->payment->update(['status' => 'failed']);
                }

                // Sudah dibayar -> uang harus dikembalikan penuh (pesanan belum dikirim).
                if ($order->payment && $order->payment->status === 'paid') {
                    $bank = $meta['bank'] ?? null;
                    $refund = Refund::create([
                        'number' => self::newNumber('RFD'),
                        'order_id' => $order->id,
                        'source' => 'cancel',
                        'amount' => (int) $order->payment->amount,
                        'reason' => $meta['reason'] ?? 'Pesanan dibatalkan',
                        'status' => $bank ? 'Diminta' : 'Menunggu Rekening',
                        'bank_name' => $bank['bank_name'] ?? null,
                        'account_number' => $bank['account_number'] ?? null,
                        'account_holder' => $bank['account_holder'] ?? null,
                    ]);
                }

                $extra = [
                    'cancelled_at' => now(),
                    'cancel_reason' => isset($meta['reason']) ? Str::limit($meta['reason'], 250, '') : null,
                    'cancelled_by' => $meta['by'] ?? 'admin',
                ];
            }

            if ($to === 'Dikirim' && $order->shipment) {
                $order->shipment->update(['status' => 'shipped', 'shipped_at' => now()]);
            }

            if ($to === 'Selesai') {
                foreach ($order->items as $i) {
                    if ($i->product_id) {
                        Product::where('id', $i->product_id)->increment('sold_count', $i->qty);
                    }
                }
                if ($order->shipment) {
                    $order->shipment->update(['status' => 'delivered', 'delivered_at' => now()]);
                }
                if ($order->payment && $order->payment->method === 'cod' && $order->payment->status === 'pending') {
                    $order->payment->update(['status' => 'paid', 'paid_at' => now()]);
                    $receiptNow = true;   // COD baru lunas saat barang diterima -> kirim kuitansi
                }
            }

            $order->update(['status' => $to] + $extra);
        });

        if ($error !== null) {
            return $error;
        }

        Notify::to($order->user_id, 'order', 'Status pesanan ' . $order->number . ': ' . $to,
            'Pesanan Anda kini berstatus "' . $to . '".', route('account.orders.show', $order));

        if ($to === 'Dibatalkan') {
            self::announceCancellation($order, $refund);
        }
        if ($receiptNow) {
            SafeMail::receipt($order);
        }

        return null;
    }

    private static function announceCancellation(Order $order, ?Refund $refund): void
    {
        $lines = ['Pesanan ' . $order->number . ' telah dibatalkan' . ($order->cancelled_by === 'customer' ? ' sesuai permintaan Anda.' : ' oleh admin.')];
        if ($order->cancel_reason) {
            $lines[] = 'Alasan: ' . $order->cancel_reason;
        }
        $lines[] = 'Stok produk dan kuota kupon sudah dikembalikan.';

        if ($refund) {
            $lines[] = 'Karena pesanan sudah dibayar, dana ' . self::money($refund->amount) . ' akan dikembalikan (refund ' . $refund->number . ').';
            if ($refund->status === 'Menunggu Rekening') {
                $lines[] = 'Mohon isi rekening tujuan refund di halaman detail pesanan agar dana dapat kami transfer.';
                Notify::to($order->user_id, 'order', 'Isi rekening untuk refund ' . $refund->number,
                    'Dana ' . self::money($refund->amount) . ' menunggu rekening tujuan Anda.', route('account.orders.show', $order));
            } else {
                $lines[] = 'Admin akan memproses transfer ke rekening yang Anda isi.';
            }
        }

        SafeMail::notice($order->user, 'Pesanan ' . $order->number . ' dibatalkan', 'Pesanan dibatalkan', $lines,
            'Lihat pesanan', route('account.orders.show', $order));
    }

    /** Admin memverifikasi transfer: pembayaran lunas, pesanan otomatis diproses, kuitansi dikirim. */
    public static function confirmPayment(Order $order): ?string
    {
        $order->load('payment');
        if (! $order->payment || $order->payment->status === 'paid') {
            return 'Pembayaran tidak ditemukan atau sudah lunas.';
        }
        if ($order->status !== 'Menunggu Pembayaran') {
            return 'Pesanan tidak dalam status menunggu pembayaran.';
        }
        if ($order->payment->method === 'qris_gopay') {
            return 'Pembayaran QRIS harus diverifikasi lewat menu Pembayaran QRIS (periksa bukti dan dana masuk).';
        }
        $order->payment->update(['status' => 'paid', 'paid_at' => now()]);

        $err = self::transition($order, 'Diproses');
        if ($err === null) {
            SafeMail::receipt($order->fresh());
        }

        return $err;
    }
}
