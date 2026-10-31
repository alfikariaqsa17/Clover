<?php

namespace App\Models;

use App\Support\Upload;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];
    protected $casts = [
        'rating_avg' => 'float',
        'is_featured' => 'boolean',
        'is_plant_of_week' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function questions()
    {
        return $this->hasMany(ProductQuestion::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /** Hitung ulang rating dari ulasan yang disetujui (satu-satunya sumber rating). */
    public function refreshRating(): void
    {
        $q = $this->approvedReviews();
        $this->update([
            'reviews_count' => (int) $q->count(),
            'rating_avg' => round((float) $q->avg('rating'), 1),
        ]);
    }

    public function getImageUrlAttribute(): ?string
    {
        $img = $this->relationLoaded('images') ? $this->images->first() : $this->images()->first();
        if ($img) {
            return Upload::url($img->path, 'products');
        }

        return Upload::bundled('images/products/' . $this->slug . '.jpg');
    }

    /** @return array<int, array{url: string, alt: string}> */
    public function getGalleryAttribute(): array
    {
        $out = [];
        foreach ($this->images as $img) {
            $out[] = ['url' => Upload::url($img->path, 'products'), 'alt' => $img->alt ?: $this->name];
        }
        if (! $out && ($u = Upload::bundled('images/products/' . $this->slug . '.jpg'))) {
            $out[] = ['url' => $u, 'alt' => $this->name];
        }

        return $out;
    }

    public function getPriceFormatAttribute(): string
    {
        return 'Rp' . number_format($this->price, 0, ',', '.');
    }

    public function getInStockAttribute(): bool
    {
        return $this->stock > 0;
    }

    public function getHasRatingAttribute(): bool
    {
        return $this->reviews_count > 0;
    }
}
