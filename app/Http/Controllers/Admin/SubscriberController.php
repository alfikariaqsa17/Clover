<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;

class SubscriberController extends Controller
{
    public function index()
    {
        return view('admin.subscribers.index', ['subscribers' => Subscriber::orderByDesc('id')->paginate(20)]);
    }

    public function destroy(Subscriber $subscriber)
    {
        $subscriber->delete();

        return back()->with('toast', 'Email dihapus dari daftar.');
    }

    public function export()
    {
        $rows = Subscriber::orderBy('id')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Email', 'Terdaftar', 'Persetujuan']);
            foreach ($rows as $s) {
                fputcsv($out, [$s->email, $s->created_at->format('Y-m-d H:i'), $s->consent_at?->format('Y-m-d H:i')]);
            }
            fclose($out);
        }, 'subscriber-clover.csv', ['Content-Type' => 'text/csv']);
    }
}
