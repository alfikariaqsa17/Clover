<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function toggle(Request $r, Product $product)
    {
        $row = Wishlist::where('user_id', $r->user()->id)->where('product_id', $product->id)->first();
        if ($row) {
            $row->delete();
            $msg = $product->name . ' dihapus dari wishlist.';
        } else {
            Wishlist::create(['user_id' => $r->user()->id, 'product_id' => $product->id]);
            $msg = $product->name . ' ditambahkan ke wishlist.';
        }

        return back()->with('toast', $msg);
    }
}
