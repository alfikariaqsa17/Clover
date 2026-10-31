<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Support\Msg;
use App\Support\ReturnWorkflow;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index(Request $r)
    {
        $status = (string) $r->query('status', '');
        $refunds = Refund::with('order')
            ->when(in_array($status, Refund::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->orderByRaw("case status when 'Diminta' then 0 when 'Menunggu Rekening' then 1 else 2 end")
            ->orderByDesc('id')->paginate(12)->withQueryString();

        return view('admin.refunds.index', ['refunds' => $refunds, 'statuses' => Refund::STATUSES, 'status' => $status]);
    }

    public function show(Refund $refund)
    {
        $refund->load('order.payment', 'order.user', 'orderReturn');

        return view('admin.refunds.show', compact('refund'));
    }

    public function complete(Request $r, Refund $refund)
    {
        $d = $r->validate([
            'transfer_reference' => 'required|string|max:120',
            'admin_note' => 'nullable|string|max:500',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:3072',
        ], Msg::messages(), Msg::attributes());

        try {
            ReturnWorkflow::completeRefund($refund, $d, $r->file('proof'));
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('toast', $e->getMessage());
        }

        return back()->with('toast', 'Refund ditandai selesai. Pelanggan sudah diberi tahu.');
    }

    public function reject(Request $r, Refund $refund)
    {
        $d = $r->validate(['admin_note' => 'required|string|min:5|max:500'], Msg::messages(), Msg::attributes());

        try {
            ReturnWorkflow::rejectRefund($refund, $d['admin_note']);
        } catch (\RuntimeException $e) {
            return back()->with('toast', $e->getMessage());
        }

        return back()->with('toast', 'Refund ditolak. Pelanggan sudah diberi tahu.');
    }
}
