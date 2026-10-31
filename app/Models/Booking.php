<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    public const STATUSES = ['Menunggu Konfirmasi', 'Dikonfirmasi', 'Selesai', 'Dibatalkan'];

    /** Status yang masih bisa dijadwal ulang / dibatalkan pelanggan. */
    public const ACTIVE = ['Menunggu Konfirmasi', 'Dikonfirmasi'];

    protected $guarded = [];
    protected $casts = [
        'date' => 'date', 'previous_date' => 'date',
        'rescheduled_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    /** Jam mulai slot ("08.00–10.00" -> [8, 0]). */
    public static function slotStart(string $time): array
    {
        return preg_match('/^(\d{1,2})[.:](\d{2})/u', $time, $m) ? [(int) $m[1], (int) $m[2]] : [0, 0];
    }

    /** Waktu mulai kunjungan untuk tanggal + slot tertentu, dalam zona waktu lokasi layanan. */
    public static function startsAtFor(string $date, string $time): \Illuminate\Support\Carbon
    {
        [$h, $m] = self::slotStart($time);

        return \Illuminate\Support\Carbon::parse(substr($date, 0, 10), config('clover.booking.timezone'))->setTime($h, $m);
    }

    public function startsAt(): \Illuminate\Support\Carbon
    {
        return self::startsAtFor($this->date->toDateString(), (string) $this->time);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE, true);
    }

    /** Tanggal booking ini sedang diblokir admin (libur). */
    public function dateIsBlocked(): bool
    {
        return BlockedDate::reasonFor($this->service_id, $this->date->toDateString()) !== null;
    }

    /**
     * Boleh diubah (jadwal ulang / batal) bila masih aktif dan minimal N jam sebelum kunjungan.
     * Bila tanggalnya diblokir admin, pelanggan selalu boleh menjadwal ulang atau membatalkan.
     */
    public function canChange(): bool
    {
        if (! $this->isActive()) {
            return false;
        }
        if ($this->dateIsBlocked()) {
            return true;
        }

        return now()->addHours((int) config('clover.booking.change_min_hours', 24))->lte($this->startsAt());
    }

    public function canReschedule(): bool
    {
        return $this->canChange() && (int) $this->reschedule_count < (int) config('clover.booking.max_reschedules', 2);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function canView(?User $user): bool
    {
        if (! $this->user_id) {
            return true;
        }

        return $user && ((int) $user->id === (int) $this->user_id || $user->isAdmin());
    }
}
