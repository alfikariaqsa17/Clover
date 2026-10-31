<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Support\Msg;
use App\Support\ReturnWorkflow;
use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Retur barang oleh pelanggan (pesanan sudah selesai). */
class ReturnController extends Controller
{
    public function index(Request $r)
    {
        return view('account.returns.index', [
            'returns' => OrderReturn::with('order', 'refund')->where('user_id', $r->user()->id)->latest()->paginate(8),
        ]);
    }

    private function ownOrder(Request $r, Order $order): Order
    {
        abort_unless((int) $order->user_id === (int) $r->user()->id, 404);

        return $order->load(['payment', 'shipment', 'items']);
    }

    public function create(Request $r, Order $order)
    {
        $order = $this->ownOrder($r, $order);
        if (! $order->canReturn()) {
            return redirect()->route('account.orders.show', $order)
                ->with('toast', 'Pesanan ini tidak dapat diretur (belum selesai, melewati batas ' . config('clover.return.days') . ' hari, atau sudah pernah diajukan).');
        }

        return view('account.returns.create', ['order' => $order, 'reasons' => config('clover.return.reasons'), 'deadline' => $order->returnDeadline()]);
    }

    public function store(Request $r, Order $order)
    {
        $order = $this->ownOrder($r, $order);
        if (! $order->canReturn()) {
            return redirect()->route('account.orders.show', $order)->with('toast', 'Pesanan ini tidak dapat diretur.');
        }

        Msg::digits($r, 'account_number');
        $needEvidence = in_array($r->input('reason_code'), config('clover.return.evidence_required'), true);
        $rules = [
            'reason_code' => ['required', Rule::in(array_keys(config('clover.return.reasons')))],
            'description' => 'required|string|min:10|max:1000',
            'evidence' => [$needEvidence ? 'required' : 'nullable', 'array', 'max:3'],
            'evidence.*' => 'file|mimes:jpg,jpeg,png,webp|max:3072',
        ] + Msg::bankRules();
        $d = $r->validate($rules, Msg::messages() + [
            'evidence.required' => 'Foto bukti wajib dilampirkan untuk alasan ini.',
            'evidence.max' => 'Maksimal 3 foto bukti.',
            'description.min' => 'Jelaskan masalahnya minimal :min karakter.',
        ], Msg::attributes());

        $paths = [];
        foreach ($r->file('evidence', []) as $f) {
            $paths[] = Upload::save($f, 'returns');
        }

        try {
            $ret = ReturnWorkflow::request($order, $r->user(), $d, $paths);
        } catch (\RuntimeException $e) {
            foreach ($paths as $p) {
                Upload::delete($p, 'returns');
            }

            return back()->withInput()->with('toast', $e->getMessage());
        }

        return redirect()->route('account.returns.show', $ret)->with('toast', 'Pengajuan retur terkirim. Admin akan meninjaunya.');
    }

    public function show(Request $r, OrderReturn $orderReturn)
    {
        abort_unless((int) $orderReturn->user_id === (int) $r->user()->id, 404);
        $orderReturn->load('order', 'refund');

        return view('account.returns.show', ['ret' => $orderReturn]);
    }

    /** Pelanggan mengisi kurir + resi pengiriman balik barang. */
    public function ship(Request $r, OrderReturn $orderReturn)
    {
        abort_unless((int) $orderReturn->user_id === (int) $r->user()->id, 404);

        $d = $r->validate([
            'return_courier' => 'required|string|max:40',
            'return_tracking' => 'required|string|max:80',
        ], Msg::messages(), Msg::attributes());

        try {
            ReturnWorkflow::shipBack($orderReturn, $d);
        } catch (\RuntimeException $e) {
            return back()->with('toast', $e->getMessage());
        }

        return back()->with('toast', 'Data pengiriman balik disimpan. Admin akan mengonfirmasi saat barang diterima.');
    }
}
