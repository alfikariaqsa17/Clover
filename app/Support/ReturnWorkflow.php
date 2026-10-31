<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Alur retur barang & refund dana.
 *
 * Retur : Diajukan -> Disetujui | Ditolak
 *         Disetujui -> (bila barang harus dikirim balik) Dikirim Balik -> Diterima
 *         Selesai otomatis setelah refund-nya selesai.
 * Refund: Menunggu Rekening -> Diminta -> Selesai | Ditolak
 *
 * Setiap error yang boleh ditampilkan ke pengguna dilempar sebagai \RuntimeException.
 */
class ReturnWorkflow
{
    /** Pelanggan mengajukan retur. $evidence = path foto yang sudah tersimpan. */
    public static function request(Order $order, User $user, array $data, array $evidence): OrderReturn
    {
        return DB::transaction(function () use ($order, $user, $data, $evidence) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();
            $locked->load('payment', 'shipment');
            if (! $locked->canReturn()) {
                throw new \RuntimeException('Pesanan ini tidak dapat diretur (sudah melewati batas waktu, belum selesai, atau sudah pernah diajukan).');
            }

            return OrderReturn::create([
                'number' => OrderWorkflow::newNumber('RTN'),
                'order_id' => $locked->id,
                'user_id' => $user->id,
                'reason_code' => $data['reason_code'],
                'description' => $data['description'],
                'evidence' => $evidence ?: null,
                'status' => 'Diajukan',
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'],
                'account_holder' => $data['account_holder'],
            ]);
        });
    }

    /** Admin menyetujui / menolak pengajuan retur. */
    public static function decide(OrderReturn $ret, bool $approve, array $in): void
    {
        DB::transaction(function () use ($ret, $approve, $in) {
            $ret->refresh();
            if ($ret->status !== 'Diajukan') {
                throw new \RuntimeException('Pengajuan ini sudah diproses.');
            }
            $ret->load('order.payment');

            if ($approve) {
                $max = (int) ($ret->order->payment->amount ?? 0);
                $amount = (int) ($in['refund_amount'] ?? $max);
                if ($amount < 1 || $amount > $max) {
                    throw new \RuntimeException('Nominal refund harus antara Rp1 dan ' . OrderWorkflow::money($max) . '.');
                }
                $ret->update([
                    'status' => 'Disetujui', 'needs_return' => (bool) ($in['needs_return'] ?? false),
                    'refund_amount' => $amount, 'admin_note' => $in['admin_note'] ?? null, 'decided_at' => now(),
                ]);
                if (! $ret->needs_return) {
                    self::createRefund($ret);   // tanaman mati/rusak: tidak perlu dikirim balik
                }
            } else {
                $ret->update(['status' => 'Ditolak', 'admin_note' => $in['admin_note'], 'decided_at' => now()]);
            }
        });

        $ret->refresh();
        $url = route('account.returns.show', $ret);

        if ($ret->status === 'Disetujui') {
            $lines = ['Pengajuan retur ' . $ret->number . ' untuk pesanan ' . $ret->order->number . ' disetujui.'];
            if ($ret->needs_return) {
                $lines[] = 'Mohon kirim barang kembali ke toko, lalu isi nama kurir dan nomor resi di halaman retur.';
            } else {
                $lines[] = 'Barang tidak perlu dikirim balik. Refund ' . OrderWorkflow::money((int) $ret->refund_amount) . ' akan ditransfer ke rekening yang Anda isi.';
            }
            if ($ret->admin_note) {
                $lines[] = 'Catatan admin: ' . $ret->admin_note;
            }
            $title = 'Retur ' . $ret->number . ' disetujui';
        } else {
            $lines = ['Pengajuan retur ' . $ret->number . ' belum dapat kami setujui.', 'Alasan: ' . $ret->admin_note];
            $title = 'Retur ' . $ret->number . ' ditolak';
        }
        Notify::to($ret->user_id, 'order', $title, null, $url);
        SafeMail::notice($ret->user, $title, $title, $lines, 'Lihat retur', $url);
    }

    /** Pelanggan mengisi data pengiriman balik barang. */
    public static function shipBack(OrderReturn $ret, array $in): void
    {
        DB::transaction(function () use ($ret, $in) {
            $ret->refresh();
            if (! $ret->canShipBack()) {
                throw new \RuntimeException('Retur ini tidak dalam tahap pengiriman balik.');
            }
            $ret->update([
                'status' => 'Dikirim Balik', 'return_courier' => $in['return_courier'],
                'return_tracking' => $in['return_tracking'], 'shipped_back_at' => now(),
            ]);
        });
    }

    /** Admin menerima barang retur; opsional dikembalikan ke stok; refund otomatis dibuat. */
    public static function receive(OrderReturn $ret, bool $restock): void
    {
        DB::transaction(function () use ($ret, $restock) {
            $ret->refresh();
            if ($ret->status !== 'Dikirim Balik') {
                throw new \RuntimeException('Barang retur belum dikirim balik oleh pelanggan.');
            }
            $ret->load('order.items');
            if ($restock) {
                foreach ($ret->order->items as $i) {
                    if ($i->product_id) {
                        Product::where('id', $i->product_id)->increment('stock', $i->qty);
                    }
                }
            }
            $ret->update(['status' => 'Diterima', 'received_at' => now(), 'restock' => $restock]);
            self::createRefund($ret);
        });

        $ret->refresh();
        $url = route('account.returns.show', $ret);
        Notify::to($ret->user_id, 'order', 'Barang retur ' . $ret->number . ' diterima', 'Refund sedang diproses admin.', $url);
        SafeMail::notice($ret->user, 'Barang retur ' . $ret->number . ' sudah kami terima', 'Barang retur diterima',
            ['Barang retur untuk pesanan ' . $ret->order->number . ' sudah kami terima.', 'Refund ' . OrderWorkflow::money((int) $ret->refund_amount) . ' sedang diproses ke rekening Anda.'],
            'Lihat retur', $url);
    }

    /** Membuat refund dari retur (sekali saja). */
    private static function createRefund(OrderReturn $ret): Refund
    {
        $existing = Refund::where('order_return_id', $ret->id)->first();
        if ($existing) {
            return $existing;
        }

        return Refund::create([
            'number' => OrderWorkflow::newNumber('RFD'),
            'order_id' => $ret->order_id,
            'order_return_id' => $ret->id,
            'source' => 'return',
            'amount' => (int) $ret->refund_amount,
            'reason' => $ret->reason_label,
            'status' => ($ret->bank_name && $ret->account_number && $ret->account_holder) ? 'Diminta' : 'Menunggu Rekening',
            'bank_name' => $ret->bank_name,
            'account_number' => $ret->account_number,
            'account_holder' => $ret->account_holder,
        ]);
    }

    /** Pelanggan mengisi / memperbarui rekening tujuan refund (selama belum diproses). */
    public static function saveBank(Refund $refund, array $bank): void
    {
        if (! in_array($refund->status, ['Menunggu Rekening', 'Diminta'], true)) {
            throw new \RuntimeException('Refund ini sudah diproses, rekening tidak dapat diubah.');
        }
        $refund->update([
            'bank_name' => $bank['bank_name'], 'account_number' => $bank['account_number'],
            'account_holder' => $bank['account_holder'], 'status' => 'Diminta',
        ]);
    }

    /** Admin menandai refund sudah ditransfer. */
    public static function completeRefund(Refund $refund, array $in, ?UploadedFile $proof): void
    {
        DB::transaction(function () use ($refund, $in, $proof) {
            $refund->refresh();
            if ($refund->status !== 'Diminta' || ! $refund->hasBank()) {
                throw new \RuntimeException('Refund belum siap diproses (rekening tujuan belum diisi atau sudah selesai).');
            }
            $refund->update([
                'status' => 'Selesai', 'transfer_reference' => $in['transfer_reference'],
                'admin_note' => $in['admin_note'] ?? null, 'processed_at' => now(),
                'proof_path' => $proof ? Upload::save($proof, 'refunds') : null,
            ]);

            $order = Order::with('payment')->find($refund->order_id);
            $done = (int) Refund::where('order_id', $refund->order_id)->where('status', 'Selesai')->sum('amount');
            if ($order?->payment && $done >= (int) $order->payment->amount) {
                $order->payment->update(['status' => 'refunded', 'refunded_at' => now()]);
            }
            if ($refund->order_return_id) {
                OrderReturn::whereKey($refund->order_return_id)->update(['status' => 'Selesai']);
            }
        });

        $refund->refresh()->load('order.user');
        $url = route('account.orders.show', $refund->order);
        Notify::to($refund->order->user_id, 'order', 'Refund ' . $refund->number . ' selesai',
            OrderWorkflow::money($refund->amount) . ' sudah ditransfer ke rekening Anda.', $url);
        SafeMail::notice($refund->order->user, 'Refund ' . $refund->number . ' sudah ditransfer', 'Refund selesai',
            ['Dana ' . OrderWorkflow::money($refund->amount) . ' untuk pesanan ' . $refund->order->number . ' sudah ditransfer ke ' . $refund->bank_name . ' ' . $refund->maskedAccount() . '.',
                'Referensi transfer: ' . $refund->transfer_reference],
            'Lihat pesanan', $url);
    }

    /** Admin menolak refund (wajib dengan alasan). */
    public static function rejectRefund(Refund $refund, string $note): void
    {
        DB::transaction(function () use ($refund, $note) {
            $refund->refresh();
            if (! in_array($refund->status, ['Menunggu Rekening', 'Diminta'], true)) {
                throw new \RuntimeException('Refund ini sudah diproses.');
            }
            $refund->update(['status' => 'Ditolak', 'admin_note' => $note, 'processed_at' => now()]);
            if ($refund->order_return_id) {
                OrderReturn::whereKey($refund->order_return_id)->update(['status' => 'Selesai']);
            }
        });

        $refund->refresh()->load('order.user');
        $url = route('account.orders.show', $refund->order);
        Notify::to($refund->order->user_id, 'order', 'Refund ' . $refund->number . ' ditolak', $note, $url);
        SafeMail::notice($refund->order->user, 'Refund ' . $refund->number . ' tidak dapat diproses', 'Refund ditolak',
            ['Refund untuk pesanan ' . $refund->order->number . ' tidak dapat kami proses.', 'Alasan: ' . $note, 'Silakan hubungi kami melalui halaman Kontak bila ada pertanyaan.'],
            'Lihat pesanan', $url);
    }
}
