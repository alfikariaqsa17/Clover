<?php

namespace App\Models;

use App\Support\Upload;
use Illuminate\Database\Eloquent\Model;

/** Pengembalian dana. Dibuat otomatis saat pesanan lunas dibatalkan atau retur disetujui/diterima. */
class Refund extends Model
{
    public const STATUSES = ['Menunggu Rekening', 'Diminta', 'Selesai', 'Ditolak'];

    protected $guarded = [];
    protected $casts = ['account_number' => 'encrypted', 'processed_at' => 'datetime'];

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderReturn()
    {
        return $this->belongsTo(OrderReturn::class, 'order_return_id');
    }

    public function getProofUrlAttribute(): ?string
    {
        return Upload::url($this->proof_path, 'refunds');
    }

    public function getSourceLabelAttribute(): string
    {
        return $this->source === 'cancel' ? 'Pembatalan pesanan' : 'Retur barang';
    }

    /** Nomor rekening disamarkan, hanya 4 digit terakhir. */
    public function maskedAccount(): string
    {
        $n = (string) $this->account_number;

        return $n === '' ? '-' : str_repeat('•', max(0, strlen($n) - 4)) . substr($n, -4);
    }

    public function hasBank(): bool
    {
        return $this->bank_name && $this->account_number && $this->account_holder;
    }
}
