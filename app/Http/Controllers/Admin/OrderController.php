<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Msg;
use App\Support\Notify;
use App\Support\XlsxExport;
use App\Support\OrderWorkflow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    private function filtered(Request $r)
    {
        $status = (string) $r->query('status', '');
        $q = trim((string) $r->query('q', ''));

        return Order::query()->with('payment')
            ->when(in_array($status, Order::STATUSES, true), fn ($x) => $x->where('status', $status))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('number', 'like', '%' . $q . '%')->orWhere('name', 'like', '%' . $q . '%')))
            ->orderByDesc('id');
    }

    public function index(Request $r)
    {
        return view('admin.orders.index', [
            'orders' => $this->filtered($r)->paginate(12)->withQueryString(),
            'statuses' => Order::STATUSES,
            'status' => (string) $r->query('status', ''),
            'q' => trim((string) $r->query('q', '')),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items', 'user', 'payment', 'shipment', 'returns', 'refunds']);

        return view('admin.orders.show', compact('order'));
    }

    public function status(Request $r, Order $order)
    {
        $d = $r->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
            'reason' => 'nullable|string|max:250',
        ], Msg::messages(), Msg::attributes());
        $err = OrderWorkflow::transition($order, $d['status'], ['reason' => $d['reason'] ?? 'Dibatalkan oleh admin', 'by' => 'admin']);

        return back()->with('toast', $err ?? 'Status pesanan diperbarui.');
    }

    public function receipt(Order $order)
    {
        $order->load(['items', 'payment', 'shipment']);
        abort_unless(in_array($order->payment?->status, ['paid', 'refunded'], true), 404);

        return view('account.receipt', ['order' => $order, 'back' => route('admin.orders.show', $order)]);
    }

    public function confirmPayment(Order $order)
    {
        $err = OrderWorkflow::confirmPayment($order);

        return back()->with('toast', $err ?? 'Pembayaran diverifikasi. Pesanan diproses.');
    }

    public function shipment(Request $r, Order $order)
    {
        $d = $r->validate([
            'courier' => 'required|string|max:30',
            'tracking_number' => 'nullable|string|max:80',
        ], Msg::messages(), ['courier' => 'Kurir', 'tracking_number' => 'Nomor resi']);

        if (! $order->shipment) {
            return back()->with('toast', 'Data pengiriman tidak ditemukan.');
        }
        $order->shipment->update($d);
        if (! empty($d['tracking_number'])) {
            Notify::to($order->user_id, 'order', 'Nomor resi pesanan ' . $order->number, 'Resi: ' . $d['tracking_number'], route('account.orders.show', $order));
        }

        return back()->with('toast', 'Data pengiriman disimpan.');
    }

    public function export(Request $r)
    {
        $rows = $this->filtered($r)->get();
        $status = (string) $r->query('status', '');
        $q = trim((string) $r->query('q', ''));
        $meta = 'Dicetak ' . now()->format('d/m/Y H:i')
            . ($status !== '' ? '  ·  Status: ' . $status : '')
            . ($q !== '' ? '  ·  Pencarian: "' . $q . '"' : '');
        $active = $rows->where('status', '!=', 'Dibatalkan');

        return XlsxExport::make('Pesanan')
            ->banner('Daftar Pesanan', $meta)
            ->summary([
                ['Jumlah pesanan', $rows->count(), 'int'],
                ['Nilai pesanan aktif', (int) $active->sum('total'), 'money'],
            ])
            ->table([
                ['label' => 'Nomor Pesanan', 'width' => 22],
                ['label' => 'Tanggal', 'width' => 20, 'type' => 'datetime'],
                ['label' => 'Nama', 'width' => 26],
                ['label' => 'WhatsApp', 'width' => 18],
                ['label' => 'Kota', 'width' => 18],
                ['label' => 'Subtotal', 'width' => 16, 'type' => 'money', 'sum' => true],
                ['label' => 'Diskon', 'width' => 14, 'type' => 'money', 'sum' => true],
                ['label' => 'Ongkir', 'width' => 14, 'type' => 'money', 'sum' => true],
                ['label' => 'Total', 'width' => 16, 'type' => 'money', 'sum' => true],
                ['label' => 'Status', 'width' => 22, 'badge' => ReportController::STATUS_BADGE],
                ['label' => 'Pembayaran', 'width' => 24],
            ], $rows->map(fn ($o) => [$o->number, $o->created_at, $o->name, $o->whatsapp, $o->city, $o->subtotal, $o->discount, $o->shipping, $o->total, $o->status, $o->payment?->status_label])->all(), false)
            ->download('pesanan-clover-' . now()->format('Ymd-His'), $r->string('format')->toString());
    }
}
