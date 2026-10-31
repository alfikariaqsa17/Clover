<?php

namespace App\Http\Controllers;

use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Service;
use App\Support\Msg;
use App\Support\Notify;
use App\Support\SafeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    public function index()
    {
        return view('services.index', ['services' => Service::active()->get()]);
    }

    public function show(Service $service)
    {
        abort_unless($service->is_active, 404);

        return view('services.show', [
            'service' => $service,
            'times' => Service::TIMES,
            'blocked' => BlockedDate::upcoming($service->id, (int) config('clover.booking.max_days_ahead', 90)),
            'others' => Service::active()->where('id', '!=', $service->id)->take(3)->get(),
        ]);
    }

    public function availability(Request $r, Service $service)
    {
        $r->validate(['date' => 'required|date|after_or_equal:today']);
        $date = $r->query('date');

        // Saat jadwal ulang, booking milik pengguna sendiri tidak dihitung memakai kuota.
        $except = null;
        if ($code = $r->query('except')) {
            $except = $r->user() ? Booking::where('code', $code)->where('user_id', $r->user()->id)->value('id') : null;
        }

        $slots = [];
        foreach ($service->availability($date, $except) as $time => $left) {
            $slots[] = ['time' => $time, 'left' => $left];
        }

        return response()->json(['date' => $date, 'blocked' => $service->blockedReason($date), 'slots' => $slots]);
    }

    public function book(Request $r, Service $service)
    {
        abort_unless($service->is_active, 404);
        Msg::wa($r);

        $data = $r->validate([
            'name' => 'required|string|min:3|max:100',
            'whatsapp' => ['required', 'regex:' . Msg::WA_RULE],
            'address' => 'required|string|min:5|max:255',
            'date' => 'required|date|after_or_equal:today|before_or_equal:' . now()->addDays((int) config('clover.booking.max_days_ahead', 90))->toDateString(),
            'time' => ['required', Rule::in(Service::TIMES)],
            'area' => 'required|integer|min:1|max:100000',
            'notes' => 'nullable|string|max:1000',
        ], Msg::messages(), Msg::attributes());

        if ($reason = $service->blockedReason($data['date'])) {
            return back()->withInput()->withErrors(['date' => 'Tanggal ini tidak tersedia (' . $reason . '). Pilih tanggal lain.']);
        }

        try {
            $booking = DB::transaction(function () use ($r, $service, $data) {
                $locked = Service::whereKey($service->id)->lockForUpdate()->first();
                $left = $locked->availability($data['date'])[$data['time']] ?? 0;
                if ($left < 1) {
                    throw new \RuntimeException('Jadwal ' . $data['time'] . ' pada tanggal tersebut sudah penuh. Pilih jam atau tanggal lain.');
                }

                return Booking::create($data + [
                    'user_id' => $r->user()->id,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'code' => 'JSA-' . now()->format('ymd') . '-' . strtoupper(Str::random(4)),
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['time' => $e->getMessage()]);
        }

        Notify::to($r->user()->id, 'booking', 'Pemesanan ' . $booking->code . ' diterima',
            'Menunggu konfirmasi tim Clover.', route('booking.done', $booking));

        return redirect()->route('booking.done', $booking);
    }

    public function done(Booking $booking)
    {
        abort_unless($booking->canView(auth()->user()), 403);
        $booking->load('service');

        return view('services.done', [
            'booking' => $booking,
            'times' => Service::TIMES,
            'blockedNow' => $booking->isActive() && $booking->dateIsBlocked(),
        ]);
    }

    private function ownBooking(Request $r, Booking $booking): Booking
    {
        abort_unless($booking->user_id && (int) $booking->user_id === (int) $r->user()->id, 404);

        return $booking;
    }

    /** Alasan mengapa booking tidak boleh diubah (null bila boleh). */
    private function changeProblem(Booking $b, bool $forReschedule): ?string
    {
        if (! $b->isActive()) {
            return 'Pemesanan ini sudah ' . mb_strtolower($b->status) . ' dan tidak dapat diubah.';
        }
        if (! $b->canChange()) {
            return 'Perubahan hanya bisa dilakukan minimal ' . config('clover.booking.change_min_hours') . ' jam sebelum jam kunjungan. Hubungi kami lewat WhatsApp untuk bantuan.';
        }
        if ($forReschedule && (int) $b->reschedule_count >= (int) config('clover.booking.max_reschedules')) {
            return 'Jadwal sudah diubah ' . $b->reschedule_count . 'x (batas maksimal). Hubungi kami lewat WhatsApp bila perlu mengubah lagi.';
        }

        return null;
    }

    /** Pelanggan menjadwal ulang booking: tanggal baru wajib tersedia, tidak diblokir, dan masih ada kuota. */
    public function reschedule(Request $r, Booking $booking)
    {
        $this->ownBooking($r, $booking);

        $data = $r->validate([
            'date' => 'required|date|after_or_equal:today|before_or_equal:' . now()->addDays((int) config('clover.booking.max_days_ahead', 90))->toDateString(),
            'time' => ['required', Rule::in(Service::TIMES)],
        ], Msg::messages(), Msg::attributes());

        DB::transaction(function () use ($booking, $data) {
            $b = Booking::whereKey($booking->id)->lockForUpdate()->first();

            if ($problem = $this->changeProblem($b, true)) {
                throw ValidationException::withMessages(['date' => $problem]);
            }
            if ($b->date->toDateString() === $data['date'] && $b->time === $data['time']) {
                throw ValidationException::withMessages(['time' => 'Pilih tanggal atau jam yang berbeda dari jadwal saat ini.']);
            }
            $minHours = (int) config('clover.booking.change_min_hours');
            if (now()->addHours($minHours)->gt(Booking::startsAtFor($data['date'], $data['time']))) {
                throw ValidationException::withMessages(['time' => 'Jadwal baru minimal ' . $minHours . ' jam dari sekarang.']);
            }

            $service = $b->service_id ? Service::whereKey($b->service_id)->lockForUpdate()->first() : null;
            if (! $service || ! $service->is_active) {
                throw ValidationException::withMessages(['date' => 'Layanan ini sudah tidak tersedia. Hubungi kami lewat WhatsApp.']);
            }
            if ($reason = $service->blockedReason($data['date'])) {
                throw ValidationException::withMessages(['date' => 'Tanggal ini tidak tersedia (' . $reason . '). Pilih tanggal lain.']);
            }
            if (($service->availability($data['date'], $b->id)[$data['time']] ?? 0) < 1) {
                throw ValidationException::withMessages(['time' => 'Jadwal ' . $data['time'] . ' pada tanggal tersebut sudah penuh. Pilih jam atau tanggal lain.']);
            }

            $b->update([
                'previous_date' => $b->date->toDateString(), 'previous_time' => $b->time,
                'date' => $data['date'], 'time' => $data['time'],
                'reschedule_count' => (int) $b->reschedule_count + 1, 'rescheduled_at' => now(),
                'status' => 'Menunggu Konfirmasi',   // jadwal baru perlu dikonfirmasi ulang oleh tim
            ]);
        });

        $booking->refresh();
        $when = $booking->date->translatedFormat('l, j F Y') . ' · ' . $booking->time;
        Notify::to($booking->user_id, 'booking', 'Jadwal ' . $booking->code . ' diubah', 'Jadwal baru: ' . $when . '. Menunggu konfirmasi tim.', route('booking.done', $booking));
        SafeMail::notice($r->user(), 'Jadwal jasa ' . $booking->code . ' diubah', 'Jadwal berhasil diubah',
            ['Pemesanan ' . $booking->code . ' (' . $booking->service_name . ') dijadwalkan ulang ke ' . $when . '.', 'Tim Clover akan mengonfirmasi jadwal baru ini lewat WhatsApp.'],
            'Lihat pemesanan', route('booking.done', $booking));

        return redirect()->route('booking.done', $booking)->with('toast', 'Jadwal diubah ke ' . $when . '. Menunggu konfirmasi tim.');
    }

    public function cancel(Request $r, Booking $booking)
    {
        $this->ownBooking($r, $booking);
        $d = $r->validate(['reason' => 'nullable|string|max:200'], Msg::messages(), Msg::attributes());

        DB::transaction(function () use ($booking, $d) {
            $b = Booking::whereKey($booking->id)->lockForUpdate()->first();
            if ($problem = $this->changeProblem($b, false)) {
                throw ValidationException::withMessages(['reason' => $problem]);
            }
            $b->update(['status' => 'Dibatalkan', 'cancelled_at' => now(), 'cancel_reason' => $d['reason'] ?? null]);
        });

        $booking->refresh();
        Notify::to($booking->user_id, 'booking', 'Pemesanan ' . $booking->code . ' dibatalkan', null, route('booking.done', $booking));
        SafeMail::notice($r->user(), 'Pemesanan jasa ' . $booking->code . ' dibatalkan', 'Pemesanan dibatalkan',
            ['Pemesanan ' . $booking->code . ' (' . $booking->service_name . ') pada ' . $booking->date->translatedFormat('j F Y') . ' telah dibatalkan sesuai permintaan Anda.', 'Anda dapat memesan lagi kapan saja.'],
            'Pesan jasa lagi', route('services'));

        return redirect()->route('booking.done', $booking)->with('toast', 'Pemesanan dibatalkan.');
    }
}
