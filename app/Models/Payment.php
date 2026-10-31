<?php

namespace App\Models;

use App\Support\Upload;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public const STATUSES = [
        'pending' => 'Menunggu', 'paid' => 'Lunas', 'failed' => 'Gagal', 'refunded' => 'Dikembalikan',
        'awaiting_verification' => 'Menunggu Verifikasi', 'rejected' => 'Bukti Ditolak',
    ];

    protected $guarded = [];
    protected $casts = ['paid_at' => 'datetime', 'refunded_at' => 'datetime', 'verified_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function proofs()
    {
        return $this->hasMany(PaymentProof::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isQris(): bool
    {
        return $this->method === 'qris_gopay';
    }

    /** Label metode untuk tampilan. Metode lama tetap tampil seperti sebelumnya (huruf besar). */
    public function getMethodLabelAttribute(): string
    {
        return $this->isQris() ? 'QRIS GoPay' : strtoupper((string) $this->method);
    }

    public function getProofUrlAttribute(): ?string
    {
        return Upload::url($this->proof_path, 'proofs');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
