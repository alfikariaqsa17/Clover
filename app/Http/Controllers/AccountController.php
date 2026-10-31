<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CareSchedule;
use App\Models\Order;
use App\Models\Refund;
use App\Support\CareReminder;
use App\Support\Msg;
use App\Support\OrderWorkflow;
use App\Support\ReturnWorkflow;
use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index(Request $r)
    {
        $u = $r->user();
        CareReminder::generate($u);

        return view('account.index', [
            'orderCount' => Order::where('user_id', $u->id)->count(),
            'bookingCount' => Booking::where('user_id', $u->id)->count(),
            'wishCount' => $u->wishlistProducts()->count(),
            'plantCount' => $u->plants()->count(),
            'latestOrders' => Order::where('user_id', $u->id)->latest()->take(3)->get(),
            'dueSchedules' => CareSchedule::with('userPlant')->where('is_active', true)
                ->whereDate('next_due_date', '<=', today()->addDays(3)->toDateString())
                ->whereHas('userPlant', fn ($q) => $q->where('user_id', $u->id))
                ->orderBy('next_due_date')->take(5)->get(),
        ]);
    }

    public function orders(Request $r)
    {
        return view('account.orders', [
            'orders' => Order::with(['items', 'payment'])->where('user_id', $r->user()->id)->latest()->paginate(8),
        ]);
    }

    public function orderShow(Request $r, Order $order)
    {
        abort_unless((int) $order->user_id === (int) $r->user()->id, 404);
        $order->load(['items.product', 'payment', 'shipment', 'returns.refund', 'refunds']);

        return view('account.order-show', compact('order'));
    }

    public function uploadProof(Request $r, Order $order)
    {
        abort_unless((int) $order->user_id === (int) $r->user()->id, 404);
        $order->load('payment');

        if (! $order->payment || $order->payment->method !== 'transfer' || $order->payment->status !== 'pending' || $order->status !== 'Menunggu Pembayaran') {
            return back()->with('toast', 'Bukti pembayaran tidak dapat diunggah untuk pesanan ini.');
        }

        $r->validate(['proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:3072'], Msg::messages(), Msg::attributes());

        Upload::delete($order->payment->proof_path, 'proofs');
        $order->payment->update(['proof_path' => Upload::save($r->file('proof'), 'proofs')]);

        return back()->with('toast', 'Bukti pembayaran diunggah. Admin akan memverifikasinya.');
    }

    /** Kuitansi cetak: hanya untuk pesanan yang pembayarannya sudah lunas. */
    public function receipt(Request $r, Order $order)
    {
        abort_unless((int) $order->user_id === (int) $r->user()->id, 404);
        $order->load(['items', 'payment', 'shipment']);
        abort_unless(in_array($order->payment?->status, ['paid', 'refunded'], true), 404);

        return view('account.receipt', ['order' => $order, 'back' => route('account.orders.show', $order)]);
    }

    /** Pelanggan membatalkan pesanan selama belum dikirim. Bila sudah dibayar, refund penuh dibuat otomatis. */
    public function cancelOrder(Request $r, Order $order)
    {
        abort_unless((int) $order->user_id === (int) $r->user()->id, 404);
        $order->load('payment');

        if (! $order->canCancel()) {
            return back()->with('toast', 'Pesanan ini sudah tidak dapat dibatalkan (sudah dikirim, selesai, atau sudah dibatalkan).');
        }

        $paid = $order->payment?->status === 'paid';
        Msg::digits($r, 'account_number');
        $rules = [
            'reason' => ['required', Rule::in(array_keys(config('clover.cancel_reasons')))],
            'reason_note' => 'nullable|string|max:300',
        ];
        if ($paid) {
            $rules += Msg::bankRules();
        }
        $d = $r->validate($rules, Msg::messages(), Msg::attributes());

        $reason = config('clover.cancel_reasons.' . $d['reason']);
        if (! empty($d['reason_note'])) {
            $reason .= ' — ' . $d['reason_note'];
        }
        $meta = ['reason' => $reason, 'by' => 'customer'];
        if ($paid) {
            $meta['bank'] = ['bank_name' => $d['bank_name'], 'account_number' => $d['account_number'], 'account_holder' => $d['account_holder']];
        }

        $err = OrderWorkflow::transition($order, 'Dibatalkan', $meta);
        if ($err) {
            return back()->with('toast', $err);
        }

        return back()->with('toast', $paid
            ? 'Pesanan dibatalkan. Refund ' . OrderWorkflow::money((int) $order->payment->amount) . ' akan ditransfer ke rekening Anda setelah diproses admin.'
            : 'Pesanan dibatalkan. Stok dan kupon sudah dikembalikan.');
    }

    /** Pelanggan mengisi / memperbarui rekening tujuan refund. */
    public function refundBank(Request $r, Refund $refund)
    {
        abort_unless((int) $refund->order()->value('user_id') === (int) $r->user()->id, 404);

        Msg::digits($r, 'account_number');
        $d = $r->validate(Msg::bankRules(), Msg::messages(), Msg::attributes());

        try {
            ReturnWorkflow::saveBank($refund, $d);
        } catch (\RuntimeException $e) {
            return back()->with('toast', $e->getMessage());
        }

        return back()->with('toast', 'Rekening tujuan refund disimpan. Admin akan memproses transfer.');
    }

    public function bookings(Request $r)
    {
        return view('account.bookings', [
            'bookings' => Booking::with('service')->where('user_id', $r->user()->id)->latest()->paginate(8),
        ]);
    }

    public function wishlist(Request $r)
    {
        return view('account.wishlist', [
            'products' => $r->user()->wishlistProducts()->with(['images', 'category'])->where('products.is_active', true)->latest('wishlists.id')->get(),
        ]);
    }

    public function profile()
    {
        return view('account.profile');
    }

    public function updateProfile(Request $r)
    {
        Msg::wa($r, 'phone');
        $data = $r->validate([
            'name' => 'required|string|min:3|max:100',
            'phone' => ['nullable', 'regex:' . Msg::WA_RULE],
        ], Msg::messages(), Msg::attributes());

        $r->user()->update($data);

        return back()->with('toast', 'Profil diperbarui.');
    }

    public function updatePassword(Request $r)
    {
        $data = $r->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|max:100|confirmed',
        ], Msg::messages(), Msg::attributes());

        if (! Hash::check($data['current_password'], $r->user()->password)) {
            return back()->withErrors(['current_password' => 'Kata sandi saat ini salah.']);
        }
        $r->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('toast', 'Kata sandi diganti.');
    }
}
