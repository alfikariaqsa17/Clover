<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tanggal libur / diblokir untuk booking jasa. service_id null = berlaku untuk semua layanan. */
class BlockedDate extends Model
{
    protected $guarded = [];
    protected $casts = ['date' => 'date'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    private static function forService($q, ?int $serviceId)
    {
        return $q->where(function ($w) use ($serviceId) {
            $w->whereNull('service_id');
            if ($serviceId) {
                $w->orWhere('service_id', $serviceId);
            }
        });
    }

    /** Alasan pemblokiran bila tanggal itu diblokir, null bila tidak. */
    public static function reasonFor(?int $serviceId, string $date): ?string
    {
        $row = self::forService(self::query()->whereDate('date', substr($date, 0, 10)), $serviceId)->first();

        return $row ? ($row->reason ?: 'Libur') : null;
    }

    /** Tanggal-tanggal diblokir mendatang untuk satu layanan: [Y-m-d => alasan]. */
    public static function upcoming(?int $serviceId, int $days = 90): array
    {
        $rows = self::forService(
            self::query()->whereDate('date', '>=', today()->toDateString())->whereDate('date', '<=', today()->addDays($days)->toDateString()),
            $serviceId
        )->orderBy('date')->get();

        $out = [];
        foreach ($rows as $r) {
            $out[$r->date->toDateString()] = $r->reason ?: 'Libur';
        }

        return $out;
    }
}
