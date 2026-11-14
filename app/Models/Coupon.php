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
    public function problem(int $subtotal, ?int $userId = null): ?string
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
        // Sekali pakai per pelanggan: kupon yang sudah dipakai di pesanan (belum dibatalkan) tidak bisa dipakai lagi.
        if ($userId !== null && $this->usedBy($userId)) {
            return 'Kupon ini sudah pernah Anda gunakan.';
        }
        if ($subtotal < $this->min_subtotal) {
            return 'Minimal belanja Rp' . number_format($this->min_subtotal, 0, ',', '.') . ' untuk memakai kupon ini.';
        }

        return null;
    }

    public function usedBy(int $userId): bool
    {
        return $this->usages()->where('user_id', $userId)->exists();
    }

    /** Kupon yang masih bisa dipakai pelanggan ini (kupon yang sudah dipakai otomatis tidak muncul). */
    public function scopeAvailableFor($q, int $userId)
    {
        return $q->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($w) => $w->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->whereDoesntHave('usages', fn ($u) => $u->where('user_id', $userId))
            ->orderBy('min_subtotal');
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
