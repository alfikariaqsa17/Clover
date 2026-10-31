<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu bukti transaksi QRIS yang diunggah pelanggan. Filenya PRIVAT (disk "local"), hanya diakses lewat controller berotorisasi. */
class PaymentProof extends Model
{
    public const STATUSES = ['submitted' => 'Menunggu verifikasi', 'verified' => 'Diverifikasi', 'rejected' => 'Ditolak'];

    protected $guarded = [];
    protected $casts = ['reviewed_at' => 'datetime'];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
