<?php

namespace App\Models;

use App\Support\Upload;
use Illuminate\Database\Eloquent\Model;

/** Pengajuan retur barang untuk pesanan yang sudah selesai. */
class OrderReturn extends Model
{
    public const STATUSES = ['Diajukan', 'Disetujui', 'Ditolak', 'Dikirim Balik', 'Diterima', 'Selesai'];

    protected $guarded = [];
    protected $casts = [
        'evidence' => 'array', 'needs_return' => 'boolean', 'restock' => 'boolean',
        'account_number' => 'encrypted',
        'decided_at' => 'datetime', 'shipped_back_at' => 'datetime', 'received_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function refund()
    {
        return $this->hasOne(Refund::class, 'order_return_id');
    }

    public function getReasonLabelAttribute(): string
    {
        return config('clover.return.reasons.' . $this->reason_code, $this->reason_code);
    }

    /** @return array<int,string> URL foto bukti */
    public function evidenceUrls(): array
    {
        return array_values(array_filter(array_map(fn ($p) => Upload::url($p, 'returns'), $this->evidence ?? [])));
    }

    public function canShipBack(): bool
    {
        return $this->status === 'Disetujui' && $this->needs_return;
    }
}
