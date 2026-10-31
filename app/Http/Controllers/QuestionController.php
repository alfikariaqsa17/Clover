<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductQuestion;
use App\Support\Msg;
use Illuminate\Http\Request;

/** Pelanggan bertanya di halaman produk. */
class QuestionController extends Controller
{
    public function store(Request $r, Product $product)
    {
        abort_unless($product->is_active, 404);

        $d = $r->validate([
            'question' => 'required|string|min:10|max:300',
        ], Msg::messages() + ['question.min' => 'Tuliskan pertanyaan minimal :min karakter.'], Msg::attributes() + ['question' => 'Pertanyaan']);

        // Batas wajar agar tidak dipakai spam: 5 pertanyaan per pengguna per hari.
        $today = ProductQuestion::where('user_id', $r->user()->id)->where('created_at', '>=', now()->subDay())->count();
        if ($today >= 5) {
            return back()->withInput()->with('toast', 'Batas 5 pertanyaan per hari tercapai. Coba lagi besok.');
        }

        ProductQuestion::create(['product_id' => $product->id, 'user_id' => $r->user()->id, 'question' => $d['question']]);

        return redirect()->to(route('products.show', $product) . '#tanya-jawab')->with('toast', 'Pertanyaan terkirim. Tim kami akan menjawabnya.');
    }
}
