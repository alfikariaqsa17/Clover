<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Message;
use App\Models\Order;
use App\Models\Plant;
use App\Models\Product;
use App\Models\Review;
use App\Models\Service;
use App\Models\Subscriber;
use App\Support\Msg;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home()
    {
        $weekProduct = Product::active()->with(['images', 'plant', 'category'])
            ->where('is_plant_of_week', true)->first();

        return view('home', [
            'categories' => Category::active()->withCount(['products' => fn ($q) => $q->where('is_active', true)])->get(),
            'featured' => Product::active()->with(['images', 'category'])->where('is_featured', true)
                ->orderByDesc('stock')->orderBy('id')->take(8)->get(),
            'weekProduct' => $weekProduct,
            'services' => Service::active()->take(5)->get(),
            'inspirations' => Banner::live('inspiration')->take(4)->get(),
            'articles' => Article::published()->with('category')->orderByDesc('published_at')->take(3)->get(),
            'testimonials' => Review::approved()->with(['user', 'product'])->latest()->take(3)->get(),
            'promos' => Banner::live('promo')->take(2)->get(),
            'stats' => [
                'products' => Product::active()->count(),
                'services' => Service::active()->count(),
                'plants' => Plant::count(),
            ],
        ]);
    }

    public function about()
    {
        return view('about');
    }

    public function contact()
    {
        return view('contact');
    }

    public function privacy()
    {
        return view('legal.privacy');
    }

    public function terms()
    {
        return view('legal.terms');
    }

    public function contactStore(Request $r)
    {
        Msg::wa($r);
        $data = $r->validate([
            'name' => 'required|string|min:3|max:100',
            'email' => 'required|email|max:150',
            'whatsapp' => ['required', 'regex:' . Msg::WA_RULE],
            'subject' => 'required|string|min:3|max:150',
            'body' => 'required|string|min:10|max:2000',
        ], Msg::messages(), Msg::attributes());

        Message::create($data);

        return redirect()->route('contact')->with('toast', 'Pesan Anda berhasil dikirim. Terima kasih.');
    }

    public function newsletter(Request $r)
    {
        $data = $r->validate([
            'email' => 'required|email|max:150',
            'consent' => 'accepted',
        ], Msg::messages(), Msg::attributes());

        Subscriber::firstOrCreate(['email' => strtolower($data['email'])], ['consent_at' => now()]);

        return back()->with('toast', 'Terima kasih. Email Anda terdaftar di komunitas Clover.');
    }

    public function track(Request $r)
    {
        $order = null;
        $searched = $r->filled('nomor');
        if ($searched) {
            $order = Order::with(['items', 'payment', 'shipment'])->where('number', strtoupper(trim((string) $r->query('nomor'))))->first();
            if ($order && ! $order->canView($r->user())) {
                $order = null;
            }
        }

        return view('track', compact('order', 'searched'));
    }
}
