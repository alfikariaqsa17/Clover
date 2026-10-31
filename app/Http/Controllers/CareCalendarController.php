<?php

namespace App\Http\Controllers;

use App\Models\CareSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CareCalendarController extends Controller
{
    public function index(Request $r)
    {
        try {
            $month = Carbon::createFromFormat('Y-m', (string) $r->query('bulan', now()->format('Y-m')))->startOfMonth();
        } catch (\Throwable $e) {
            $month = now()->startOfMonth();
        }
        $start = $month->copy();
        $end = $month->copy()->endOfMonth();

        $schedules = CareSchedule::with('userPlant')->where('is_active', true)
            ->whereHas('userPlant', fn ($q) => $q->where('user_id', $r->user()->id))->get();

        // Proyeksi kejadian berulang pada bulan yang ditampilkan.
        $events = [];
        foreach ($schedules as $s) {
            $d = $s->next_due_date->copy();
            $guard = 0;
            while ($d->lte($end) && $guard++ < 400) {
                if ($d->gte($start)) {
                    $events[$d->toDateString()][] = $s;
                }
                $d->addDays(max(1, (int) $s->every_days));
            }
        }

        $weeks = [];
        $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
        $last = $end->copy()->endOfWeek(Carbon::SUNDAY);
        while ($cursor->lte($last)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = [
                    'date' => $cursor->copy(),
                    'in_month' => $cursor->month === $month->month,
                    'events' => $events[$cursor->toDateString()] ?? [],
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return view('account.calendar', [
            'month' => $month,
            'weeks' => $weeks,
            'prev' => $month->copy()->subMonth()->format('Y-m'),
            'next' => $month->copy()->addMonth()->format('Y-m'),
            'overdue' => $schedules->filter(fn ($s) => $s->next_due_date->lt(today()))->sortBy('next_due_date'),
            'upcoming' => $schedules->filter(fn ($s) => $s->next_due_date->gte(today()) && $s->next_due_date->lte(today()->addDays(14)))->sortBy('next_due_date'),
        ]);
    }
}
