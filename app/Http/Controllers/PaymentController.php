<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentProof;
use App\Support\Msg;
use App\Support\Notify;
use App\Support\ProofFile;
use App\Support\Qris;
use App\Support\QrisWorkflow;
use App\Support\SafeMail;
use Illuminate\Http\Request;

/** Pembayaran QRIS GoPay sisi pelanggan: tampilkan QRIS, unggah bukti, lihat riwayat. Semua halaman hanya untuk pemilik pesanan. */
class PaymentController extends Controller
{
    private function own(Request $r, Order $order): Order
    {
        abort_unless($order->user_id && (int) $order->user_id === (int) $r->user()->id, 404);
        $order->load(['items', 'payment.proofs', 'shipment']);
        abort_unless($order->payment && $order->payment->isQris(), 404);

        return $order;
    }

    public function show(Request $r, Order $order)
    {
        $order = $this->own($r, $order);
        $pay = $order->payment;

        return view('account.pay', [
            'order' => $order,
            'pay' => $pay,
            'proofs' => $pay->proofs->sortByDesc('id')->values(),
            'qrisUrl' => Qris::imageUrl(),
            'qrisReady' => Qris::ready(),
            'qrisFit' => Qris::fit(),
            'merchant' => Qris::merchant(),
            'canUpload' => $order->status === 'Menunggu Pembayaran' && in_array($pay->status, ['pending', 'rejected'], true),
        ]);
    }

    public function submit(Request $r, Order $order)
    {
        $order = $this->own($r, $order);

        $d = $r->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp',
                'dimensions:min_width=200,min_height=200,max_width=8000,max_height=16000', 'max:4096'],
            'note' => 'nullable|string|max:200',
            'confirm' => 'accepted',
        ], Msg::messages() + [
            'proof.required' => 'Pilih file screenshot bukti pembayaran.',
            'proof.mimes' => 'Format harus JPG, PNG, atau WebP.',
            'proof.mimetypes' => 'Isi file bukan gambar JPG, PNG, atau WebP yang valid.',
            'proof.max' => 'Ukuran file maksimal 4 MB.',
            'proof.dimensions' => 'Dimensi gambar tidak valid (minimal 200x200 piksel).',
            'confirm.accepted' => 'Centang pernyataan bahwa Anda sudah membayar dengan nominal yang tepat.',
        ], Msg::attributes() + ['proof' => 'Bukti pembayaran', 'note' => 'Catatan', 'confirm' => 'Pernyataan']);

        try {
            QrisWorkflow::submit($order, $r->file('proof'), $d['note'] ?? null);
        } catch (\RuntimeException $e) {
            return redirect()->route('account.orders.pay', $order)->with('toast', $e->getMessage());
        }

        Notify::to($order->user_id, 'order', 'Bukti pembayaran ' . $order->number . ' diterima',
            'Menunggu verifikasi admin. Pesanan diproses setelah dana kami pastikan masuk.', route('account.orders.pay', $order));
        SafeMail::notice($r->user(), 'Bukti pembayaran ' . $order->number . ' diterima', 'Bukti pembayaran diterima', [
            'Terima kasih. Bukti pembayaran QRIS untuk pesanan ' . $order->number . ' sudah kami terima.',
            'Admin akan memeriksa dan memastikan dana ' . \App\Support\OrderWorkflow::money((int) $order->total) . ' masuk ke akun merchant. Screenshot belum berarti pembayaran lunas; Anda akan diberi tahu setelah diverifikasi.',
        ], 'Lihat status pembayaran', route('account.orders.pay', $order));

        return redirect()->route('account.orders.pay', $order)->with('toast', 'Bukti terkirim. Menunggu verifikasi admin.');
    }

    /** Bukti milik pesanan ini saja, hanya untuk pemiliknya. */
    public function file(Request $r, Order $order, PaymentProof $proof)
    {
        $order = $this->own($r, $order);
        abort_unless((int) $proof->payment_id === (int) $order->payment->id, 404);

        return ProofFile::response($proof);
    }
}
