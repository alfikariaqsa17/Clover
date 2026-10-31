<?php

namespace App\Support;

use App\Models\Cart as CartModel;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;

/**
 * Keranjang: tamu -> session, pengguna login -> tabel carts/cart_items (tersimpan antar perangkat).
 * Harga SELALU diambil dari database, tidak pernah dari browser.
 */
class Cart
{
    private static function read(): array
    {
        if (auth()->check()) {
            $cart = CartModel::where('user_id', auth()->id())->first();

            return $cart ? $cart->items()->pluck('qty', 'product_id')->map(fn ($q) => (int) $q)->all() : [];
        }

        return session('cart', []);
    }

    private static function write(array $items): void
    {
        if (auth()->check()) {
            $cart = CartModel::firstOrCreate(['user_id' => auth()->id()]);
            $cart->items()->whereNotIn('product_id', array_keys($items) ?: [0])->delete();
            foreach ($items as $pid => $qty) {
                CartItem::updateOrCreate(['cart_id' => $cart->id, 'product_id' => $pid], ['qty' => $qty]);
            }

            return;
        }
        session(['cart' => $items]);
    }

    public static function add(int $id, int $qty = 1): void
    {
        $p = Product::active()->find($id);
        if (! $p || $p->stock < 1) {
            return;
        }
        $c = self::read();
        $c[$id] = min($p->stock, ($c[$id] ?? 0) + max(1, $qty));
        self::write($c);
    }

    public static function set(int $id, int $qty): void
    {
        $c = self::read();
        if ($qty < 1) {
            unset($c[$id]);
        } else {
            $p = Product::find($id);
            $c[$id] = $p ? min($p->stock, $qty) : $qty;
        }
        self::write($c);
    }

    public static function remove(int $id): void
    {
        $c = self::read();
        unset($c[$id]);
        self::write($c);
    }

    public static function clear(): void
    {
        if (auth()->check()) {
            $cart = CartModel::where('user_id', auth()->id())->first();
            $cart?->items()->delete();

            return;
        }
        session()->forget('cart');
    }

    /** Pindahkan isi keranjang session ke keranjang database setelah login/daftar. */
    public static function mergeSessionInto(User $user): void
    {
        $guest = session('cart', []);
        if (! $guest) {
            return;
        }
        $cart = CartModel::firstOrCreate(['user_id' => $user->id]);
        foreach ($guest as $pid => $qty) {
            $p = Product::active()->find($pid);
            if (! $p || $p->stock < 1) {
                continue;
            }
            $item = CartItem::where('cart_id', $cart->id)->where('product_id', $pid)->first();
            $new = min($p->stock, ($item ? $item->qty : 0) + (int) $qty);
            CartItem::updateOrCreate(['cart_id' => $cart->id, 'product_id' => $pid], ['qty' => $new]);
        }
        session()->forget('cart');
    }

    /** @return array<int, array{product: Product, qty: int, subtotal: int}> */
    public static function lines(): array
    {
        $raw = self::read();
        if (! $raw) {
            return [];
        }
        $products = Product::active()->with('images')->whereIn('id', array_keys($raw))->get()->keyBy('id');
        $lines = [];
        $clean = [];
        foreach ($raw as $id => $qty) {
            $p = $products->get($id);
            if (! $p || $p->stock < 1) {
                continue;
            }
            $qty = min((int) $qty, $p->stock);
            $clean[$id] = $qty;
            $lines[] = ['product' => $p, 'qty' => $qty, 'subtotal' => $p->price * $qty];
        }
        if ($clean != $raw) {
            self::write($clean);
        }

        return $lines;
    }
}
