<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\UserAddress;
use App\Support\Cart;
use App\Support\CheckoutService;
use App\Support\Msg;
use App\Support\Notify;
use App\Support\OrderWorkflow;
use App\Support\SafeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    /** Tujuan pengiriman untuk menghitung ongkir awal: dari input sebelumnya atau alamat default. */
    private function destination(Request $r, $addresses): ?array
    {
        $id = $r->old('address_id', $addresses->firstWhere('is_default', true)?->id);
        if ($id !== null && $id !== '') {
            $a = $addresses->firstWhere('id', (int) $id);

            return $a ? ['postal_code' => $a->postal_code, 'city' => $a->city] : null;
        }
        $postal = $r->old('postal_code');
        $city = $r->old('city');

        return ($postal || $city) ? ['postal_code' => (string) $postal, 'city' => (string) $city] : null;
    }

    public function show(Request $r)
    {
        $lines = Cart::lines();
        if (! $lines) {
            return redirect()->route('products')->with('toast', 'Keranjang Anda masih kosong.');
        }

        $subtotal = (int) array_sum(array_column($lines, 'subtotal'));
        $addresses = $r->user()->addresses()->orderByDesc('is_default')->orderBy('id')->get();
        $dest = $this->destination($r, $addresses);
        $shipping = CheckoutService::shippingOptions($subtotal, $dest);
        $payments = CheckoutService::paymentOptions();
        $code = session('coupon_code');
        $quote = CheckoutService::quote($lines, $code, (string) old('shipping_method', array_key_first($shipping)), $dest);
        if ($quote['coupon_error']) {
            session()->forget('coupon_code');
        }

        return view('checkout.show', [
            'lines' => $lines,
            'addresses' => $addresses,
            'shipping' => $shipping,
            'payments' => $payments,
            'quote' => $quote,
            'subtotal' => $subtotal,
            'couponCode' => session('coupon_code'),
            'zoneLabel' => collect($shipping)->pluck('zone_label')->filter()->first(),
        ]);
    }

    /** JSON: biaya ongkir per metode untuk alamat tersimpan atau kode pos/kota yang sedang diketik. */
    public function shippingQuote(Request $r)
    {
        $subtotal = (int) array_sum(array_column(Cart::lines(), 'subtotal'));
        $dest = null;
        if ($r->filled('address_id')) {
            $a = UserAddress::where('user_id', $r->user()->id)->find($r->query('address_id'));
            $dest = $a ? ['postal_code' => $a->postal_code, 'city' => $a->city] : null;
        } elseif ($r->filled('postal_code') || $r->filled('city')) {
            $dest = ['postal_code' => (string) $r->query('postal_code'), 'city' => (string) $r->query('city')];
        }

        $out = [];
        $zone = null;
        foreach (CheckoutService::shippingOptions($subtotal, $dest) as $key => $o) {
            $cost = $o['final_cost'];
            $out[$key] = ['cost' => $cost, 'text' => $cost === null ? 'Isi alamat dulu' : ($cost ? OrderWorkflow::money($cost) : 'Gratis')];
            $zone ??= $o['zone_label'];
        }

        return response()->json(['zone' => $zone, 'options' => $out]);
    }

    public function applyCoupon(Request $r)
    {
        $d = $r->validate(['code' => 'required|string|max:40'], Msg::messages(), Msg::attributes());
        $lines = Cart::lines();
        $quote = CheckoutService::quote($lines, $d['code'], 'reguler');

        if ($quote['coupon_error']) {
            return back()->withErrors(['code' => $quote['coupon_error']]);
        }
        session(['coupon_code' => strtoupper(trim($d['code']))]);

        return back()->with('toast', 'Kupon diterapkan. Potongan Rp' . number_format($quote['discount'], 0, ',', '.') . '.');
    }

    public function removeCoupon()
    {
        session()->forget('coupon_code');

        return back()->with('toast', 'Kupon dilepas.');
    }

    public function store(Request $r)
    {
        $lines = Cart::lines();
        if (! $lines) {
            return redirect()->route('products')->with('toast', 'Keranjang Anda masih kosong.');
        }
        $user = $r->user();

        Msg::wa($r, 'phone');
        $rules = [
            'address_id' => 'nullable|integer',
            'recipient' => 'required_without:address_id|nullable|string|min:3|max:100',
            'phone' => ['required_without:address_id', 'nullable', 'regex:' . Msg::WA_RULE],
            'address' => 'required_without:address_id|nullable|string|min:5|max:255',
            'city' => 'required_without:address_id|nullable|string|max:100',
            'postal_code' => ['required_without:address_id', 'nullable', 'regex:/^[0-9]{5}$/'],
            'shipping_method' => ['required', Rule::in(array_keys(config('clover.shipping')))],
            'payment_method' => ['required', Rule::in(array_keys(CheckoutService::paymentOptions()))],
            'notes' => 'nullable|string|max:1000',
        ];
        $data = $r->validate($rules, Msg::messages(), Msg::attributes());

        if (! empty($data['address_id'])) {
            $saved = UserAddress::where('user_id', $user->id)->find($data['address_id']);
            if (! $saved) {
                return back()->withInput()->withErrors(['address_id' => 'Alamat tidak ditemukan.']);
            }
            $ship = ['recipient' => $saved->recipient, 'phone' => $saved->phone, 'address' => $saved->address, 'city' => $saved->city, 'postal_code' => $saved->postal_code];
        } else {
            $ship = ['recipient' => $data['recipient'], 'phone' => $data['phone'], 'address' => $data['address'], 'city' => $data['city'], 'postal_code' => $data['postal_code']];
        }

        $couponCode = session('coupon_code');
        $method = $data['shipping_method'];
        $cfg = config('clover.shipping.' . $method);

        try {
            $order = DB::transaction(function () use ($lines, $user, $ship, $data, $couponCode, $method, $cfg) {
                // Kunci baris produk agar stok tidak bisa terjual ganda.
                $ids = array_map(fn ($l) => $l['product']->id, $lines);
                $products = Product::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');

                $subtotal = 0;
                $rows = [];
                foreach ($lines as $l) {
                    $p = $products->get($l['product']->id);
                    if (! $p || ! $p->is_active || $p->stock < $l['qty']) {
                        throw new \RuntimeException('Stok "' . ($p->name ?? 'produk') . '" tidak mencukupi. Perbarui keranjang Anda.');
                    }
                    $subtotal += $p->price * $l['qty'];
                    $rows[] = ['product' => $p, 'qty' => $l['qty']];
                }

                $coupon = null;
                $discount = 0;
                if ($couponCode) {
                    $coupon = Coupon::where('code', $couponCode)->lockForUpdate()->first();
                    $err = $coupon ? $coupon->problem($subtotal) : 'Kupon tidak valid.';
                    if ($err) {
                        throw new \RuntimeException($err);
                    }
                    $discount = $coupon->discountFor($subtotal);
                }

                $shippingCost = (int) (CheckoutService::shippingOptions($subtotal, $ship)[$method]['final_cost'] ?? 0);
                $zoneKey = ! empty($cfg['zoned']) ? CheckoutService::zoneFor($ship['postal_code'], $ship['city'])['key'] : null;
                $total = max(0, $subtotal - $discount + $shippingCost);

                $order = Order::create([
                    'user_id' => $user->id,
                    'number' => 'CLV-' . now()->format('ymd') . '-' . strtoupper(Str::random(4)),
                    'name' => $ship['recipient'],
                    'whatsapp' => $ship['phone'],
                    'address' => $ship['address'],
                    'city' => $ship['city'],
                    'postal_code' => $ship['postal_code'],
                    'notes' => $data['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'coupon_code' => $coupon?->code,
                    'shipping' => $shippingCost,
                    'shipping_method' => $method,
                    'shipping_zone' => $zoneKey,
                    'total' => $total,
                    'status' => $data['payment_method'] === 'cod' ? 'Diproses' : 'Menunggu Pembayaran',
                ]);

                foreach ($rows as $row) {
                    $order->items()->create([
                        'product_id' => $row['product']->id,
                        'name' => $row['product']->name,
                        'price' => $row['product']->price,
                        'qty' => $row['qty'],
                    ]);
                    $row['product']->decrement('stock', $row['qty']);
                }

                Payment::create(['order_id' => $order->id, 'method' => $data['payment_method'], 'amount' => $total, 'status' => 'pending']);
                Shipment::create(['order_id' => $order->id, 'courier' => $cfg['courier'], 'service' => $cfg['label'], 'cost' => $shippingCost, 'status' => 'pending']);

                if ($coupon) {
                    CouponUsage::create(['coupon_id' => $coupon->id, 'user_id' => $user->id, 'order_id' => $order->id, 'amount' => $discount]);
                    $coupon->increment('used_count');
                }

                return $order;
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('toast', $e->getMessage());
        }

        if ($r->boolean('save_address') && empty($data['address_id'])) {
            $user->addresses()->create($ship + [
                'label' => 'Alamat ' . ($user->addresses()->count() + 1),
                'is_default' => ! $user->addresses()->exists(),
            ]);
        }

        Cart::clear();
        session()->forget('coupon_code');
        Notify::to($user->id, 'order', 'Pesanan ' . $order->number . ' dibuat', 'Total Rp' . number_format($order->total, 0, ',', '.') . '.', route('account.orders.show', $order));

        SafeMail::orderConfirmation($order);

        if ($data['payment_method'] === \App\Support\Qris::METHOD) {
            return redirect()->route('account.orders.pay', $order);   // tampilkan QRIS, nominal, dan instruksi
        }

        return redirect()->route('order.done', $order);
    }

    public function done(Order $order)
    {
        abort_unless($order->canView(auth()->user()), 403);
        $order->load(['items', 'payment', 'shipment']);

        return view('checkout.done', compact('order'));
    }
}
