<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Service;
use App\Support\Msg;
use App\Support\SafeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/** Tanggal libur / diblokir untuk booking jasa (semua layanan atau satu layanan). */
class BlockedDateController extends Controller
{
    public function index()
    {
        $rows = BlockedDate::with('service')->whereDate('date', '>=', today()->toDateString())
            ->orderBy('date')->orderBy('service_id')->paginate(20);

        // Jumlah booking aktif yang bertabrakan dengan tiap tanggal blokir.
        $affected = [];
        foreach ($rows as $row) {
            $affected[$row->id] = Booking::whereIn('status', Booking::ACTIVE)
                ->whereDate('date', $row->date->toDateString())
                ->when($row->service_id, fn ($q) => $q->where('service_id', $row->service_id))
                ->count();
        }

        return view('admin.blocked-dates.index', [
            'rows' => $rows,
            'affected' => $affected,
            'services' => Service::orderBy('name')->get(),
        ]);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'service_id' => 'nullable|integer|exists:services,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:150',
        ], Msg::messages() + ['end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.'], Msg::attributes());

        $start = Carbon::parse($d['start_date'])->startOfDay();
        $end = Carbon::parse($d['end_date'] ?? $d['start_date'])->startOfDay();
        if ($start->copy()->addDays(120)->lt($end)) {
            throw ValidationException::withMessages(['end_date' => 'Rentang maksimal 120 hari per input.']);
        }
        $serviceId = $d['service_id'] ?? null;

        $created = 0;
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $exists = BlockedDate::whereDate('date', $day->toDateString())
                ->when($serviceId, fn ($q) => $q->where('service_id', $serviceId), fn ($q) => $q->whereNull('service_id'))
                ->exists();
            if (! $exists) {
                BlockedDate::create(['date' => $day->toDateString(), 'service_id' => $serviceId, 'reason' => $d['reason'] ?? null]);
                $created++;
            }
        }

        // Pelanggan yang jadwalnya bertabrakan diberi tahu dan diarahkan menjadwal ulang.
        $hit = Booking::with('user')->whereIn('status', Booking::ACTIVE)
            ->whereDate('date', '>=', $start->toDateString())->whereDate('date', '<=', $end->toDateString())
            ->when($serviceId, fn ($q) => $q->where('service_id', $serviceId))->get();
        foreach ($hit as $b) {
            $url = route('booking.done', $b);
            $why = ($d['reason'] ?? null) ? ' (' . $d['reason'] . ')' : '';
            \App\Support\Notify::to($b->user_id, 'booking', 'Jadwal ' . $b->code . ' bertabrakan dengan hari libur', 'Mohon jadwalkan ulang.', $url);
            SafeMail::notice($b->user, 'Jadwal jasa ' . $b->code . ' perlu dijadwalkan ulang', 'Jadwal kunjungan bertepatan dengan hari libur',
                ['Mohon maaf, tanggal ' . $b->date->translatedFormat('j F Y') . ' untuk pemesanan ' . $b->code . ' (' . $b->service_name . ') kini libur' . $why . '.', 'Silakan pilih tanggal lain lewat halaman pemesanan. Penjadwalan ulang untuk kasus ini tidak dibatasi aturan jam minimal.'],
                'Jadwalkan ulang', $url);
        }

        $msg = $created . ' tanggal diblokir.';
        if ($hit->count()) {
            $msg .= ' ' . $hit->count() . ' pemesanan aktif bertabrakan; pelanggan sudah diberi tahu untuk menjadwal ulang.';
        }

        return back()->with('toast', $msg);
    }

    public function destroy(BlockedDate $blockedDate)
    {
        $blockedDate->delete();

        return back()->with('toast', 'Tanggal dibuka kembali.');
    }
}
