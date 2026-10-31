<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean', 'starts_at' => 'datetime', 'expires_at' => 'datetime'];

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    /** Pesan error bila kupon tidak bisa dipakai, null bila valid. */
    public function problem(int $subtotal): ?string
    {
        if (! $this->is_active) {
            return 'Kupon tidak aktif.';
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'Kupon belum berlaku.';
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'Kupon sudah kedaluwarsa.';
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return 'Kuota kupon sudah habis.';
        }
        if ($subtotal < $this->min_subtotal) {
            return 'Minimal belanja Rp' . number_format($this->min_subtotal, 0, ',', '.') . ' untuk memakai kupon ini.';
        }

        return null;
    }

    public function discountFor(int $subtotal): int
    {
        $d = $this->type === 'percent' ? intdiv($subtotal * $this->value, 100) : (int) $this->value;
        if ($this->max_discount) {
            $d = min($d, (int) $this->max_discount);
        }

        return max(0, min($d, $subtotal));
    }
}
