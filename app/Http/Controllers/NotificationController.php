<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $r)
    {
        return view('account.notifications', [
            'items' => UserNotification::where('user_id', $r->user()->id)->latest()->paginate(15),
        ]);
    }

    public function read(Request $r, UserNotification $notification)
    {
        abort_unless((int) $notification->user_id === (int) $r->user()->id, 404);
        $notification->update(['read_at' => now()]);

        return $notification->url ? redirect($notification->url) : back();
    }

    public function readAll(Request $r)
    {
        UserNotification::where('user_id', $r->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('toast', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
