<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Alur pembayaran QRIS GoPay (QRIS STATIS, tanpa API/webhook):
 *
 *   pending --(pelanggan unggah bukti)--> awaiting_verification --(admin verifikasi)--> paid  (pesanan -> Diproses)
 *                                                      \--(admin tolak + alasan)--> rejected --(unggah ulang)--> awaiting_verification
 *
 * Screenshot HANYA bukti klaim. Pesanan baru lunas setelah admin memastikan dana masuk di aplikasi merchant.
 * Kesalahan yang boleh ditampilkan ke pengguna dilempar sebagai \RuntimeException.
 */
class QrisWorkflow
{
    /** Pelanggan mengunggah bukti (maupun mengunggah ulang setelah ditolak). */
    public static function submit(Order $order, UploadedFile $file, ?string $note): PaymentProof
    {
        $path = null;
        try {
            return DB::transaction(function () use ($order, $file, $note, &$path) {
                $o = Order::whereKey($order->id)->lockForUpdate()->first();
                $pay = Payment::where('order_id', $o->id)->lockForUpdate()->first();

                if (! $pay || $pay->method !== Qris::METHOD) {
                    throw new \RuntimeException('Pesanan ini tidak memakai pembayaran QRIS.');
                }
                if ($o->status !== 'Menunggu Pembayaran' || ! in_array($pay->status, ['pending', 'rejected'], true)) {
                    throw new \RuntimeException($pay->status === 'awaiting_verification'
                        ? 'Bukti pembayaran Anda sedang diverifikasi admin. Mohon tunggu.'
                        : 'Bukti pembayaran tidak dapat diunggah untuk pesanan ini.');
                }

                // Disk "local" = storage/app/private: TIDAK dapat diakses lewat URL publik.
                $mime = (string) $file->getMimeType();
                $size = (int) $file->getSize();
                $path = $file->store('payment-proofs/' . $o->id, 'local');
                if (! $path) {
                    throw new \RuntimeException('Gagal menyimpan file. Coba lagi.');
                }

                $proof = PaymentProof::create([
                    'payment_id' => $pay->id, 'path' => $path, 'mime' => $mime, 'size' => $size,
                    'note' => $note ? mb_substr($note, 0, 200) : null, 'status' => 'submitted',
                ]);
                $pay->update(['status' => 'awaiting_verification', 'rejection_reason' => null]);

                return $proof;
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);   // jangan tinggalkan file yatim bila transaksi gagal
            }
            throw $e;
        }
    }

    /** Admin memastikan dana masuk: pembayaran lunas dan pesanan otomatis diproses. */
    public static function verify(Order $order, User $admin, ?string $reference): void
    {
        DB::transaction(function () use ($order, $admin, $reference) {
            [$o, $pay] = self::lockForReview($order, $admin);

            // Pengaman integritas: nominal tagihan harus sama dengan total pesanan.
            if ((int) $pay->amount !== (int) $o->total) {
                throw new \RuntimeException('Nominal tagihan tidak sama dengan total pesanan. Pembayaran tidak dapat diverifikasi otomatis.');
            }
            $proof = $pay->proofs()->where('status', 'submitted')->latest('id')->first();
            if (! $proof) {
                throw new \RuntimeException('Belum ada bukti pembayaran yang dikirim pelanggan.');
            }

            $pay->update([
                'status' => 'paid', 'paid_at' => now(), 'reference' => $reference ?: null,
                'verified_by' => $admin->id, 'verified_at' => now(), 'rejection_reason' => null,
            ]);
            $proof->update(['status' => 'verified', 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);

            // Pesanan -> Diproses lewat satu-satunya jalur workflow (notifikasi, stok, dsb.). Gagal -> semua dibatalkan.
            $err = OrderWorkflow::transition($o, 'Diproses');
            if ($err !== null) {
                throw new \RuntimeException($err);
            }
        });

        $order = $order->fresh();
        Notify::to($order->user_id, 'order', 'Pembayaran ' . $order->number . ' terverifikasi',
            'Dana sudah kami terima. Pesanan Anda sedang diproses.', route('account.orders.show', $order));
        SafeMail::receipt($order);
    }

    /** Admin menolak bukti dengan alasan; pelanggan boleh mengunggah ulang. */
    public static function reject(Order $order, User $admin, string $reason): void
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw new \RuntimeException('Alasan penolakan wajib diisi (minimal 5 karakter).');
        }

        DB::transaction(function () use ($order, $admin, $reason) {
            [, $pay] = self::lockForReview($order, $admin);

            $proof = $pay->proofs()->where('status', 'submitted')->latest('id')->first();
            $pay->update(['status' => 'rejected', 'rejection_reason' => mb_substr($reason, 0, 250)]);
            if ($proof) {
                $proof->update(['status' => 'rejected', 'reason' => mb_substr($reason, 0, 250), 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);
            }
        });

        $order->refresh()->loadMissing('user');
        $url = route('account.orders.pay', $order);
        Notify::to($order->user_id, 'order', 'Bukti pembayaran ' . $order->number . ' ditolak', $reason, $url);
        SafeMail::notice($order->user, 'Bukti pembayaran ' . $order->number . ' ditolak', 'Bukti pembayaran ditolak', [
            'Bukti pembayaran QRIS untuk pesanan ' . $order->number . ' belum dapat kami terima.',
            'Alasan: ' . $reason,
            'Silakan unggah ulang bukti yang sesuai. Pastikan nominal yang dibayarkan tepat ' . OrderWorkflow::money((int) $order->total) . '.',
        ], 'Unggah ulang bukti', $url);
    }

    /**
     * Mengunci order+payment dan memeriksa syarat peninjauan.
     * @return array{0:Order,1:Payment}
     */
    private static function lockForReview(Order $order, User $admin): array
    {
        $o = Order::whereKey($order->id)->lockForUpdate()->first();
        $pay = Payment::where('order_id', $o->id)->lockForUpdate()->first();

        if (! $pay || $pay->method !== Qris::METHOD) {
            throw new \RuntimeException('Pembayaran ini bukan QRIS GoPay.');
        }
        if (! $admin->isAdmin()) {
            throw new \RuntimeException('Hanya admin yang dapat memverifikasi pembayaran.');
        }
        if ((int) $o->user_id === (int) $admin->id) {
            throw new \RuntimeException('Admin tidak dapat memverifikasi pesanannya sendiri.');
        }
        if ($pay->status !== 'awaiting_verification') {
            throw new \RuntimeException('Pembayaran ini tidak sedang menunggu verifikasi (status: ' . (Payment::STATUSES[$pay->status] ?? $pay->status) . ').');
        }
        if ($o->status !== 'Menunggu Pembayaran') {
            throw new \RuntimeException('Status pesanan sudah berubah menjadi "' . $o->status . '".');
        }

        return [$o, $pay];
    }
}
