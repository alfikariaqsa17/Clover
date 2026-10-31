<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Cart;
use App\Support\Msg;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function page()
    {
        $lines = Cart::lines();

        return view('cart.page', ['lines' => $lines, 'subtotal' => array_sum(array_column($lines, 'subtotal'))]);
    }

    public function add(Request $r)
    {
        $d = $r->validate([
            'product_id' => 'required|integer|exists:products,id',
            'qty' => 'nullable|integer|min:1|max:99',
        ], Msg::messages(), Msg::attributes());

        $product = Product::active()->find($d['product_id']);
        if (! $product || $product->stock < 1) {
            return back()->with('toast', 'Produk sedang tidak tersedia.');
        }

        Cart::add($product->id, (int) ($d['qty'] ?? 1));

        if ($r->has('buy')) {
            return redirect()->route('checkout');
        }

        return back()->with('toast', $product->name . ' ditambahkan ke keranjang.')->with('open_cart', true);
    }

    public function update(Request $r, Product $product)
    {
        $d = $r->validate(['qty' => 'required|integer|min:0|max:99'], Msg::messages(), Msg::attributes());
        Cart::set($product->id, (int) $d['qty']);

        return back()->with('open_cart', ! $r->boolean('from_page'));
    }

    public function remove(Request $r, Product $product)
    {
        Cart::remove($product->id);

        return back()->with('open_cart', ! $r->boolean('from_page'))->with('toast', $product->name . ' dihapus dari keranjang.');
    }
}
