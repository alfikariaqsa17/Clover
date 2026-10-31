<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $r)
    {
        $q = Product::active()->with(['images', 'category', 'plant']);

        $search = trim((string) $r->query('q', ''));
        if ($search !== '') {
            self::applySearch($q, $search);
        }

        $kategori = (string) $r->query('kategori', '');
        if ($kategori !== '') {
            $q->whereHas('category', fn ($c) => $c->where('slug', $kategori));
        }

        $cahaya = (string) $r->query('cahaya', '');
        if (in_array($cahaya, ['rendah', 'sedang', 'tinggi'], true)) {
            $q->whereHas('plant', fn ($c) => $c->where('light_level', $cahaya));
        }

        $rawat = (string) $r->query('perawatan', '');
        if (in_array($rawat, ['mudah', 'sedang', 'sulit'], true)) {
            $q->whereHas('plant', fn ($c) => $c->where('care_level', $rawat));
        }

        if ($r->query('stok') === 'tersedia') {
            $q->where('stock', '>', 0);
        }

        switch ($r->query('harga')) {
            case 'a': $q->where('price', '<', 50000); break;
            case 'b': $q->whereBetween('price', [50000, 100000]); break;
            case 'c': $q->whereBetween('price', [100001, 200000]); break;
            case 'd': $q->where('price', '>', 200000); break;
        }

        $minRating = (int) $r->query('rating', 0);
        if (in_array($minRating, [3, 4], true)) {
            $q->where('rating_avg', '>=', $minRating);
        }

        // Saat mencari tanpa memilih urutan, tampilkan yang paling relevan (nama diawali kata kunci lebih dulu).
        $sort = $r->query('urut', $search !== '' ? 'relevan' : 'terbaru');
        switch ($sort) {
            case 'relevan':
                $first = str_replace(['%', '_'], '', mb_strtolower($search));
                $q->orderByRaw('case when lower(name) like ? then 0 when lower(name) like ? then 1 else 2 end', [$first . '%', '%' . $first . '%'])
                    ->orderByDesc('sold_count');
                break;
            case 'rating': $q->orderByDesc('rating_avg')->orderByDesc('reviews_count'); break;
            case 'termurah': $q->orderBy('price'); break;
            case 'termahal': $q->orderByDesc('price'); break;
            case 'populer': $q->orderByDesc('sold_count')->orderByDesc('id'); break;
            default: $q->orderByDesc('id');
        }

        $products = $q->paginate(12)->withQueryString();

        if ($r->header('X-Partial')) {
            return view('products.partials.grid', compact('products'));
        }

        return view('products.index', [
            'products' => $products,
            'categories' => Category::active()->get(),
            'didYouMean' => ($products->total() === 0 && $search !== '') ? self::didYouMean($search) : null,
        ]);
    }

    /** Setiap kata kunci harus cocok di nama, deskripsi, kategori, atau nama tanaman (AND antar kata). */
    private static function applySearch($q, string $search): void
    {
        $terms = preg_split('/\s+/u', mb_strtolower($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach (array_slice($terms, 0, 5) as $t) {
            $like = '%' . str_replace(['%', '_'], '', $t) . '%';
            $q->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like))
                    ->orWhereHas('plant', fn ($c) => $c->where('name', 'like', $like)->orWhere('latin_name', 'like', $like));
            });
        }
    }

    /** Saran ejaan ("Maksud Anda: monstera?") berdasarkan jarak edit terhadap kata di nama produk/tanaman. */
    private static function didYouMean(string $search): ?string
    {
        $vocab = [];
        foreach (Product::active()->pluck('name') as $n) {
            foreach (preg_split('/\s+/u', mb_strtolower($n), -1, PREG_SPLIT_NO_EMPTY) as $w) {
                $vocab[$w] = true;
            }
        }
        $vocab = array_keys($vocab);

        $out = [];
        $changed = false;
        foreach (preg_split('/\s+/u', mb_strtolower($search), -1, PREG_SPLIT_NO_EMPTY) as $t) {
            $best = $t;
            $bestD = max(1, (int) floor(mb_strlen($t) / 4)) + 1;
            foreach ($vocab as $w) {
                if ($w === $t) { $best = $t; $bestD = 0; break; }
                $d = levenshtein($t, $w);
                if ($d < $bestD) { $bestD = $d; $best = $w; }
            }
            $changed = $changed || $best !== $t;
            $out[] = $best;
        }

        return $changed ? implode(' ', $out) : null;
    }

    /** JSON untuk saran pencarian instan di kotak pencarian. */
    public function suggest(Request $r)
    {
        $term = trim(mb_substr((string) $r->query('q', ''), 0, 60));
        if (mb_strlen($term) < 2) {
            return response()->json(['products' => [], 'categories' => [], 'did_you_mean' => null]);
        }

        $q = Product::active()->with(['images', 'category']);
        self::applySearch($q, $term);
        $products = $q->orderByDesc('sold_count')->take(6)->get()->map(fn ($p) => [
            'name' => $p->name,
            'url' => route('products.show', $p),
            'price' => 'Rp' . number_format($p->price, 0, ',', '.'),
            'image' => $p->image_url,
            'category' => $p->category?->name,
            'in_stock' => $p->stock > 0,
        ])->values();

        $like = '%' . str_replace(['%', '_'], '', $term) . '%';
        $categories = Category::active()->where('name', 'like', $like)->take(3)->get()
            ->map(fn ($c) => ['name' => $c->name, 'url' => route('products', ['kategori' => $c->slug])])->values();

        return response()->json([
            'products' => $products,
            'categories' => $categories,
            'did_you_mean' => $products->isEmpty() ? self::didYouMean($term) : null,
        ]);
    }

    public function show(Request $r, Product $product)
    {
        abort_unless($product->is_active || ($r->user() && $r->user()->isAdmin()), 404);

        $product->load(['images', 'category', 'plant']);
        $related = Product::active()->with(['images', 'category'])
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->orderByDesc('sold_count')->take(4)->get();
        $reviews = $product->reviews()->approved()->with('user')->latest()->take(10)->get();
        $questions = $product->questions()->visible()->with('user')->orderByRaw('answer is null')->latest()->take(10)->get();

        return view('products.show', [
            'product' => $product,
            'related' => $related,
            'reviews' => $reviews,
            'questions' => $questions,
            'canReview' => self::canReview($r->user(), $product),
        ]);
    }

    /** Hanya pembeli yang pesanannya sudah selesai dan belum pernah memberi ulasan. */
    public static function canReview($user, Product $product): bool
    {
        if (! $user || (! $user->isAdmin() && ! $user->isApproved())) {
            return false;
        }
        if ($product->reviews()->where('user_id', $user->id)->exists()) {
            return false;
        }

        return OrderItem::where('product_id', $product->id)
            ->whereHas('order', fn ($o) => $o->where('user_id', $user->id)->where('status', 'Selesai'))
            ->exists();
    }
}
