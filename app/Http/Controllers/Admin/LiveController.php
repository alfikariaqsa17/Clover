<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\ProductQuestion;
use App\Models\Refund;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Data ringan untuk pembaruan otomatis (polling) di panel admin: badge menu, kejadian baru (untuk toast),
 * dan "sig" (tanda tangan data) agar halaman hanya memuat ulang area daftar/dashboard saat ada perubahan.
 * Hanya MEMBACA data; tidak mengubah apa pun.
 */
class LiveController extends Controller
{
    public function __invoke(Request $r)
    {
        // Pemanggilan pertama (tanpa "since"): hanya mengambil posisi terakhir, tanpa memicu toast.
        $since = $r->query('since');
        $since = is_array($since) ? $since : null;

        $feeds = [
            'orders' => [Order::query(), 'Pesanan baru',
                fn ($m) => $m->number . ' · ' . $m->name . ' · Rp' . number_format((int) $m->total, 0, ',', '.'),
                fn ($m) => route('admin.orders.show', $m)],
            'bookings' => [Booking::query(), 'Booking jasa baru',
                fn ($m) => $m->service_name . ' · ' . $m->name,
                fn ($m) => route('admin.bookings.index')],
            'returns' => [OrderReturn::query(), 'Pengajuan retur baru',
                fn ($m) => $m->number . ' · ' . $m->reason_label,
                fn ($m) => route('admin.returns.show', $m)],
            'refunds' => [Refund::query(), 'Refund baru',
                fn ($m) => $m->number . ' · Rp' . number_format((int) $m->amount, 0, ',', '.'),
                fn ($m) => route('admin.refunds.show', $m)],
            'questions' => [ProductQuestion::query(), 'Pertanyaan produk baru',
                fn ($m) => Str::limit((string) $m->question, 70),
                fn ($m) => route('admin.questions.index')],
            'messages' => [Message::query(), 'Pesan masuk baru',
                fn ($m) => (string) $m->name,
                fn ($m) => route('admin.messages.index')],
            'reviews' => [Review::query(), 'Ulasan baru',
                fn ($m) => 'Menunggu moderasi',
                fn ($m) => route('admin.reviews.index')],
            'accounts' => [User::query()->where('account_status', 'pending'), 'Pendaftar baru',
                fn ($m) => (string) $m->name,
                fn ($m) => route('admin.users.index', ['status' => 'pending'])],
        ];

        $max = [];
        $new = [];
        foreach ($feeds as $key => [$query, $title, $text, $url]) {
            $max[$key] = (int) (clone $query)->max('id');
            if ($since !== null && isset($since[$key])) {
                $fresh = (clone $query)->where('id', '>', (int) $since[$key]);
                $count = (clone $fresh)->count();
                if ($count > 0) {
                    $last = $fresh->orderByDesc('id')->first();
                    $new[$key] = ['count' => $count, 'title' => $title, 'text' => $text($last), 'url' => $url($last)];
                }
            }
        }

        // Sama persis dengan badge menu samping di layouts.admin (AppServiceProvider).
        $badges = [
            'orders' => Order::where('status', 'Menunggu Pembayaran')->count(),
            'bookings' => Booking::where('status', 'Menunggu Konfirmasi')->count(),
            'questions' => ProductQuestion::whereNull('answer')->count(),
            'returns' => OrderReturn::whereIn('status', ['Diajukan', 'Dikirim Balik'])->count(),
            'refunds' => Refund::where('status', 'Diminta')->count(),
            'payments' => Payment::where('status', 'awaiting_verification')->count(),
            'messages' => Message::where('is_read', false)->count(),
            'reviews' => Review::where('status', 'pending')->count(),
            'accounts' => User::where('account_status', 'pending')->count(),
        ];

        // Bukti transfer yang menunggu verifikasi (untuk toast saat pelanggan mengunggah bukti).
        $proofs = Payment::where('status', 'pending')->whereNotNull('proof_path')->count() + $badges['payments'];

        // Tanda tangan data: berubah bila ada pesanan/pembayaran/booking/retur/refund/pertanyaan yang baru atau berubah.
        $parts = [];
        foreach ([Order::class, Booking::class, OrderReturn::class, Refund::class, ProductQuestion::class, Payment::class] as $cls) {
            $a = $cls::query()->selectRaw('count(*) as c, max(id) as m, max(updated_at) as u')->first();
            $parts[] = $a->c . '|' . $a->m . '|' . $a->u;
        }
        $parts[] = json_encode($badges);
        $parts[] = $proofs;
        $parts[] = json_encode($max);

        return response()->json([
            'max' => $max,
            'new' => (object) $new,
            'badges' => $badges,
            'counts' => ['proofs' => $proofs],
            'proof_url' => $badges['payments'] > 0 ? route('admin.payments.index') : route('admin.orders.index', ['status' => 'Menunggu Pembayaran']),
            'sig' => md5(implode(';', $parts)),
        ])->header('Cache-Control', 'no-store');
    }
}
