<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Support\XlsxExport;
use Illuminate\Http\Request;

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

    public function export(Request $r)
    {
        $rows = Subscriber::orderBy('id')->get();

        return XlsxExport::make('Subscriber')
            ->banner('Subscriber Newsletter', 'Dicetak ' . now()->format('d/m/Y H:i'))
            ->summary([['Total subscriber', $rows->count(), 'int']])
            ->table([
                ['label' => 'No', 'width' => 18, 'type' => 'int'],
                ['label' => 'Email', 'width' => 40],
                ['label' => 'Terdaftar', 'width' => 22, 'type' => 'datetime'],
                ['label' => 'Persetujuan', 'width' => 22, 'type' => 'datetime'],
            ], $rows->values()->map(fn ($s, $i) => [$i + 1, $s->email, $s->created_at, $s->consent_at])->all(), false)
            ->download('subscriber-clover-' . now()->format('Ymd-His'), $r->string('format')->toString());
    }
}
