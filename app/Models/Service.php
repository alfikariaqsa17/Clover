<?php

namespace App\Models;

use App\Support\Upload;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    public const TIMES = ['08.00–10.00', '10.00–12.00', '13.00–15.00', '15.00–17.00'];

    protected $guarded = [];
    protected $casts = ['includes' => 'array', 'is_active' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return Upload::url($this->image, 'services') ?: Upload::bundled('images/services/' . $this->slug . '.jpg');
    }

    public function getPriceFormatAttribute(): string
    {
        return 'Rp' . number_format($this->price, 0, ',', '.');
    }

    /** Alasan bila tanggal diblokir (libur) untuk layanan ini, null bila tersedia. */
    public function blockedReason(string $date): ?string
    {
        return BlockedDate::reasonFor($this->id, $date);
    }

    /**
     * Sisa kuota per jam untuk satu tanggal: [slot => sisa]. Tanggal yang diblokir selalu 0.
     * $exceptBookingId = abaikan booking itu sendiri (dipakai saat jadwal ulang).
     */
    public function availability(string $date, ?int $exceptBookingId = null): array
    {
        if ($this->blockedReason($date) !== null) {
            return array_fill_keys(self::TIMES, 0);
        }

        $taken = Booking::where('service_id', $this->id)
            ->whereDate('date', $date)
            ->where('status', '!=', 'Dibatalkan')
            ->when($exceptBookingId, fn ($q) => $q->where('id', '!=', $exceptBookingId))
            ->selectRaw('time, count(*) as c')->groupBy('time')->pluck('c', 'time');

        $out = [];
        foreach (self::TIMES as $slot) {
            $out[$slot] = max(0, (int) $this->capacity_per_slot - (int) ($taken[$slot] ?? 0));
        }

        return $out;
    }
}
