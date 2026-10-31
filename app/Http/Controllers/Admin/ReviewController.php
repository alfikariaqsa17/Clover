<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Support\Msg;
use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function index(Request $r)
    {
        $status = (string) $r->query('status', '');

        return view('admin.reviews.index', [
            'reviews' => Review::with(['user', 'product'])
                ->when(array_key_exists($status, Review::STATUSES), fn ($q) => $q->where('status', $status))
                ->orderByDesc('id')->paginate(12)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function update(Request $r, Review $review)
    {
        $d = $r->validate(['status' => ['required', Rule::in(array_keys(Review::STATUSES))]], Msg::messages(), Msg::attributes());
        $review->update($d);
        $review->product?->refreshRating();

        return back()->with('toast', 'Status ulasan diperbarui.');
    }

    public function destroy(Review $review)
    {
        $product = $review->product;
        foreach ($review->photos ?? [] as $p) {
            Upload::delete($p, 'reviews');
        }
        $review->delete();
        $product?->refreshRating();

        return back()->with('toast', 'Ulasan dihapus.');
    }
}
