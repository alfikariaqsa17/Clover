<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderReturn;
use App\Support\Msg;
use App\Support\ReturnWorkflow;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function index(Request $r)
    {
        $status = (string) $r->query('status', '');
        $returns = OrderReturn::with('order', 'user')
            ->when(in_array($status, OrderReturn::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')->paginate(12)->withQueryString();

        return view('admin.returns.index', ['returns' => $returns, 'statuses' => OrderReturn::STATUSES, 'status' => $status]);
    }

    public function show(OrderReturn $orderReturn)
    {
        $orderReturn->load('order.payment', 'order.items', 'user', 'refund');

        return view('admin.returns.show', ['ret' => $orderReturn]);
    }

    public function decide(Request $r, OrderReturn $orderReturn)
    {
        $d = $r->validate([
            'decision' => 'required|in:approve,reject',
            'needs_return' => 'nullable|boolean',
            'refund_amount' => 'required_if:decision,approve|nullable|integer|min:1',
            'admin_note' => 'required_if:decision,reject|nullable|string|max:500',
        ], Msg::messages() + ['admin_note.required_if' => 'Alasan penolakan wajib diisi.', 'refund_amount.required_if' => 'Nominal refund wajib diisi.'], Msg::attributes());

        try {
            ReturnWorkflow::decide($orderReturn, $d['decision'] === 'approve', $d);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('toast', $e->getMessage());
        }

        return back()->with('toast', $d['decision'] === 'approve' ? 'Retur disetujui. Pelanggan sudah diberi tahu.' : 'Retur ditolak. Pelanggan sudah diberi tahu.');
    }

    public function receive(Request $r, OrderReturn $orderReturn)
    {
        $d = $r->validate(['restock' => 'nullable|boolean'], Msg::messages(), Msg::attributes());

        try {
            ReturnWorkflow::receive($orderReturn, (bool) ($d['restock'] ?? false));
        } catch (\RuntimeException $e) {
            return back()->with('toast', $e->getMessage());
        }

        return back()->with('toast', 'Barang retur diterima. Refund dibuat dan siap diproses.');
    }
}
