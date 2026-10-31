<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\Msg;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function index(Request $r)
    {
        $status = (string) $r->query('status', '');
        $date = (string) $r->query('tanggal', '');
        $bookings = Booking::with('service')
            ->when(in_array($status, Booking::STATUSES, true), fn ($x) => $x->where('status', $status))
            ->when($date !== '', fn ($x) => $x->whereDate('date', $date))
            ->orderByDesc('date')->orderByDesc('id')->paginate(12)->withQueryString();

        return view('admin.bookings.index', ['bookings' => $bookings, 'statuses' => Booking::STATUSES, 'status' => $status, 'date' => $date]);
    }

    public function status(Request $r, Booking $booking)
    {
        $d = $r->validate(['status' => ['required', Rule::in(Booking::STATUSES)]], Msg::messages(), Msg::attributes());
        if ($booking->status !== $d['status']) {
            $booking->update($d + ($d['status'] === 'Dibatalkan' ? ['cancelled_at' => now(), 'cancel_reason' => 'Dibatalkan oleh admin'] : []));
            Notify::to($booking->user_id, 'booking', 'Pemesanan ' . $booking->code . ': ' . $d['status'], null, route('booking.done', $booking));
        }

        return back()->with('toast', 'Status pemesanan jasa diperbarui.');
    }

    public function destroy(Booking $booking)
    {
        $booking->delete();

        return back()->with('toast', 'Pemesanan jasa dihapus.');
    }
}
