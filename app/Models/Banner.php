<?php

namespace App\Models;

use App\Support\Upload;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    public const TYPES = ['announcement' => 'Announcement Bar', 'promo' => 'Banner Promo', 'inspiration' => 'Galeri Inspirasi Ruangan'];

    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function scopeLive($q, string $type)
    {
        return $q->where('type', $type)->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('sort_order')->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return Upload::url($this->image, 'banners');
    }
}
