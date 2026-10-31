<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Support\Msg;
use App\Support\ProofFile;
use App\Support\Qris;
use App\Support\QrisWorkflow;
use Illuminate\Http\Request;

/** Peninjauan pembayaran QRIS oleh admin. Route berada di grup admin (hanya role admin). */
class PaymentController extends Controller
{
    private const FILTERS = ['awaiting_verification', 'rejected', 'paid', 'failed', 'pending'];

    public function index(Request $r)
    {
        $status = (string) $r->query('status', 'awaiting_verification');
        $payments = Payment::with('order')->where('method', Qris::METHOD)
            ->when($status !== 'semua' && in_array($status, self::FILTERS, true), fn ($q) => $q->where('status', $status))
            ->orderByRaw("case status when 'awaiting_verification' then 0 else 1 end")
            ->orderByDesc('id')->paginate(12)->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments, 'status' => $status,
            'counts' => Payment::where('method', Qris::METHOD)->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'qrisReady' => Qris::ready(),
        ]);
    }

    private function qrisOrder(Order $order): Order
    {
        $order->load(['items', 'user', 'payment.proofs.reviewer', 'payment.verifier', 'shipment']);
        abort_unless($order->payment && $order->payment->isQris(), 404);

        return $order;
    }

    public function show(Order $order)
    {
        $order = $this->qrisOrder($order);

        return view('admin.payments.show', [
            'order' => $order, 'pay' => $order->payment,
            'proofs' => $order->payment->proofs->sortByDesc('id')->values(),
            'ownOrder' => (int) $order->user_id === (int) auth()->id(),
        ]);
    }

    public function verify(Request $r, Order $order)
    {
        $order = $this->qrisOrder($order);
        $d = $r->validate([
            'confirm_funds' => 'accepted',
            'reference' => 'nullable|string|max:120',
        ], Msg::messages() + ['confirm_funds.accepted' => 'Centang konfirmasi bahwa dana sudah benar-benar masuk di aplikasi merchant.'],
            Msg::attributes() + ['confirm_funds' => 'Konfirmasi dana masuk', 'reference' => 'Referensi transaksi']);

        try {
            QrisWorkflow::verify($order, $r->user(), $d['reference'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('toast', $e->getMessage());
        }

        return redirect()->route('admin.payments.show', $order)->with('toast', 'Pembayaran diverifikasi. Pesanan masuk ke status Diproses dan kuitansi dikirim.');
    }

    public function reject(Request $r, Order $order)
    {
        $order = $this->qrisOrder($order);
        $d = $r->validate(['rejection_reason' => 'required|string|min:5|max:250'], Msg::messages(), Msg::attributes() + ['rejection_reason' => 'Alasan penolakan']);

        try {
            QrisWorkflow::reject($order, $r->user(), $d['rejection_reason']);
        } catch (\RuntimeException $e) {
            return back()->with('toast', $e->getMessage());
        }

        return redirect()->route('admin.payments.show', $order)->with('toast', 'Bukti ditolak. Pelanggan diberi tahu dan dapat mengunggah ulang.');
    }

    public function file(Order $order, PaymentProof $proof)
    {
        $order = $this->qrisOrder($order);
        abort_unless((int) $proof->payment_id === (int) $order->payment->id, 404);

        return ProofFile::response($proof);
    }
}
