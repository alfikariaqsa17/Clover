<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $r)
    {
        $days = in_array((int) $r->query('periode'), [7, 30, 90], true) ? (int) $r->query('periode') : 14;
        $start = today()->subDays($days - 1);

        $rows = Order::where('status', '!=', 'Dibatalkan')
            ->where('created_at', '>=', $start->startOfDay())
            ->selectRaw('date(created_at) as d, sum(total) as t, count(*) as c')
            ->groupBy('d')->get()->keyBy('d');

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $start->copy()->addDays($i);
            $row = $rows->get($d->toDateString());
            $series[] = ['label' => $d->format('d/m'), 'total' => (int) ($row->t ?? 0), 'count' => (int) ($row->c ?? 0)];
        }

        return view('admin.dashboard', [
            'days' => $days,
            'series' => $series,
            'max' => max(1, max(array_column($series, 'total'))),
            'stats' => [
                'sales' => (int) Order::where('status', '!=', 'Dibatalkan')->sum('total'),
                'orders' => Order::count(),
                'pending' => Order::where('status', 'Menunggu Pembayaran')->count(),
                'products' => Product::count(),
                'services' => Service::count(),
                'accounts' => User::where('account_status', 'pending')->count(),
                'users' => User::where('role', 'user')->count(),
                'bookings' => Booking::where('status', 'Menunggu Konfirmasi')->count(),
                'messages' => Message::where('is_read', false)->count(),
                'reviews' => Review::where('status', 'pending')->count(),
            ],
            'recent' => Order::with('payment')->latest()->take(6)->get(),
            'lowStock' => Product::where('stock', '<=', 5)->where('is_active', true)->orderBy('stock')->take(6)->get(),
            'recentBookings' => Booking::latest()->take(5)->get(),
        ]);
    }
}
