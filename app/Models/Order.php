<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const STATUSES = ['Menunggu Pembayaran', 'Diproses', 'Dikirim', 'Selesai', 'Dibatalkan'];

    /** Alur status yang diizinkan. */
    public const FLOW = [
        'Menunggu Pembayaran' => ['Diproses', 'Dibatalkan'],
        'Diproses' => ['Dikirim', 'Dibatalkan'],
        'Dikirim' => ['Selesai'],
        'Selesai' => [],
        'Dibatalkan' => [],
    ];

    protected $guarded = [];
    protected $casts = ['cancelled_at' => 'datetime'];

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }

    public function returns()
    {
        return $this->hasMany(OrderReturn::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    /** Nomor kuitansi diturunkan dari nomor pesanan (CLV-xxx -> KWT-xxx). */
    public function getReceiptNumberAttribute(): string
    {
        return 'KWT-' . preg_replace('/^CLV-/', '', (string) $this->number);
    }

    /** Pelanggan boleh membatalkan selama pesanan belum dikirim. */
    public function canCancel(): bool
    {
        return in_array($this->status, ['Menunggu Pembayaran', 'Diproses'], true);
    }

    /** Batas akhir pengajuan retur (null bila pesanan belum selesai). */
    public function returnDeadline(): ?\Illuminate\Support\Carbon
    {
        if ($this->status !== 'Selesai') {
            return null;
        }
        $from = $this->shipment?->delivered_at ?? $this->updated_at;

        return $from?->copy()->addDays((int) config('clover.return.days', 7));
    }

    /** Retur hanya untuk pesanan Selesai, dalam batas hari, sudah lunas, dan belum pernah diajukan. */
    public function canReturn(): bool
    {
        $deadline = $this->returnDeadline();
        if (! $deadline || $deadline->isPast()) {
            return false;
        }

        return $this->payment?->status === 'paid' && ! $this->returns()->exists();
    }

    public function nextStatuses(): array
    {
        return self::FLOW[$this->status] ?? [];
    }

    public function canView(?User $user): bool
    {
        if (! $this->user_id) {
            return true;
        }

        return $user && ((int) $user->id === (int) $this->user_id || $user->isAdmin());
    }
}
