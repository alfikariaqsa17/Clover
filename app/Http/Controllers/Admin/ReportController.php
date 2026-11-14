<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\XlsxExport;
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
        $cancelled = Order::where('status', 'Dibatalkan')->whereBetween('created_at', [$from, $to])->count();
        $total = (int) $rows->sum('total');

        $name = 'laporan-penjualan-clover-' . $from->format('Ymd') . '-' . $to->format('Ymd');

        return XlsxExport::make('Laporan Penjualan')
            ->banner('Laporan Penjualan', 'Periode ' . $from->format('d/m/Y') . ' – ' . $to->format('d/m/Y') . '  ·  pesanan dibatalkan tidak dihitung')
            ->summary([
                ['Total pesanan', $rows->count(), 'int'],
                ['Total pendapatan', $total, 'money'],
                ['Rata-rata / pesanan', $rows->count() ? intdiv($total, $rows->count()) : 0, 'money'],
                ['Pesanan selesai', $rows->where('status', 'Selesai')->count(), 'int'],
                ['Pesanan dibatalkan', $cancelled, 'int'],
            ])
            ->table([
                ['label' => 'Nomor Pesanan', 'width' => 22],
                ['label' => 'Tanggal', 'width' => 20, 'type' => 'datetime'],
                ['label' => 'Pelanggan', 'width' => 28],
                ['label' => 'Subtotal', 'width' => 18, 'type' => 'money', 'sum' => true],
                ['label' => 'Diskon', 'width' => 16, 'type' => 'money', 'sum' => true],
                ['label' => 'Ongkir', 'width' => 16, 'type' => 'money', 'sum' => true],
                ['label' => 'Total', 'width' => 18, 'type' => 'money', 'sum' => true],
                ['label' => 'Status', 'width' => 22, 'badge' => self::STATUS_BADGE],
            ], $rows->map(fn ($o) => [$o->number, $o->created_at, $o->name, $o->subtotal, $o->discount, $o->shipping, $o->total, $o->status])->all())
            ->download($name, $r->string('format')->toString());
    }

    public const STATUS_BADGE = [
        'Menunggu Pembayaran' => ['fg' => '946200', 'bg' => 'FBEFC9'],
        'Diproses' => ['fg' => '1F5F99', 'bg' => 'DCEBFA'],
        'Dikirim' => ['fg' => '5B3FA0', 'bg' => 'E8E0F7'],
        'Selesai' => ['fg' => '1B7A43', 'bg' => 'D3F0DD'],
        'Dibatalkan' => ['fg' => 'A3322B', 'bg' => 'F8DAD7'],
    ];
}
