<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function range(Request $r): array
    {
        try {
            $from = Carbon::parse($r->query('dari', now()->subDays(29)->toDateString()))->startOfDay();
            $to = Carbon::parse($r->query('sampai', now()->toDateString()))->endOfDay();
        } catch (\Throwable $e) {
            $from = now()->subDays(29)->startOfDay();
            $to = now()->endOfDay();
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function orders(Carbon $from, Carbon $to)
    {
        return Order::where('status', '!=', 'Dibatalkan')->whereBetween('created_at', [$from, $to]);
    }

    public function index(Request $r)
    {
        [$from, $to] = $this->range($r);
        $base = $this->orders($from, $to);

        $summary = [
            'orders' => (clone $base)->count(),
            'subtotal' => (int) (clone $base)->sum('subtotal'),
            'discount' => (int) (clone $base)->sum('discount'),
            'shipping' => (int) (clone $base)->sum('shipping'),
            'total' => (int) (clone $base)->sum('total'),
            'completed' => Order::where('status', 'Selesai')->whereBetween('created_at', [$from, $to])->count(),
            'cancelled' => Order::where('status', 'Dibatalkan')->whereBetween('created_at', [$from, $to])->count(),
        ];
        $summary['avg'] = $summary['orders'] ? intdiv($summary['total'], $summary['orders']) : 0;

        $daily = (clone $base)->selectRaw('date(created_at) as d, count(*) as c, sum(total) as t')
            ->groupBy('d')->orderBy('d')->get();

        $top = OrderItem::selectRaw('order_items.name as name, sum(order_items.qty) as qty, sum(order_items.qty * order_items.price) as revenue')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', 'Dibatalkan')
            ->whereBetween('orders.created_at', [$from, $to])
            ->groupBy('order_items.name')->orderByDesc('qty')->take(8)->get();

        return view('admin.reports', [
            'from' => $from, 'to' => $to, 'summary' => $summary, 'daily' => $daily, 'top' => $top,
        ]);
    }

    public function export(Request $r)
    {
        [$from, $to] = $this->range($r);
        $rows = $this->orders($from, $to)->orderBy('created_at')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Nomor', 'Tanggal', 'Pelanggan', 'Subtotal', 'Diskon', 'Ongkir', 'Total', 'Status']);
            foreach ($rows as $o) {
                fputcsv($out, [$o->number, $o->created_at->format('Y-m-d H:i'), $o->name, $o->subtotal, $o->discount, $o->shipping, $o->total, $o->status]);
            }
            fclose($out);
        }, 'laporan-penjualan-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
