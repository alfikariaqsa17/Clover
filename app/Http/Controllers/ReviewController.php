<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Support\Msg;
use App\Support\Upload;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $r, Product $product)
    {
        if (! ProductController::canReview($r->user(), $product)) {
            return back()->with('toast', 'Ulasan hanya untuk pembeli yang pesanannya sudah selesai dan belum pernah mengulas produk ini.');
        }

        $data = $r->validate([
            'rating' => 'required|integer|min:1|max:5',
            'body' => 'required|string|min:10|max:1000',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:3072',
        ], Msg::messages() + ['photos.max' => 'Maksimal 3 foto.'], Msg::attributes() + ['photos' => 'Foto', 'photos.*' => 'Foto']);

        $paths = [];
        foreach ($r->file('photos', []) as $f) {
            $paths[] = Upload::save($f, 'reviews');
        }

        Review::create([
            'user_id' => $r->user()->id,
            'product_id' => $product->id,
            'rating' => $data['rating'],
            'body' => $data['body'],
            'photos' => $paths ?: null,
            'is_verified' => true,
            'status' => 'approved',
        ]);
        $product->refreshRating();

        return back()->with('toast', 'Terima kasih. Ulasan Anda tersimpan.');
    }
}
