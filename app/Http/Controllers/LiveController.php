<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;

/**
 * Data ringan untuk pembaruan otomatis (polling) di sisi pelanggan: jumlah notifikasi belum dibaca
 * dan notifikasi baru (untuk toast). Hanya membaca notifikasi milik pengguna yang sedang login.
 */
class LiveController extends Controller
{
    public function __invoke(Request $r)
    {
        $base = UserNotification::where('user_id', $r->user()->id);
        $max = (int) (clone $base)->max('id');
        $unread = (clone $base)->whereNull('read_at')->count();

        $new = [];
        if ($r->has('since')) {
            $new = (clone $base)->where('id', '>', (int) $r->query('since'))->orderBy('id')->take(5)->get()
                ->map(fn ($n) => ['id' => $n->id, 'title' => (string) $n->title, 'body' => (string) $n->body, 'url' => $n->url])
                ->values()->all();
        }

        return response()->json([
            'max' => $max,
            'unread' => $unread,
            'new' => $new,
            'sig' => $max . '|' . $unread,
        ])->header('Cache-Control', 'no-store');
    }
}
