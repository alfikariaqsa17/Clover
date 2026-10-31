<?php

namespace App\Models;

use App\Support\Upload;
use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    public const LIGHT = ['rendah' => 'Cahaya rendah', 'sedang' => 'Cahaya sedang', 'tinggi' => 'Cahaya tinggi'];
    public const CARE = ['mudah' => 'Mudah', 'sedang' => 'Sedang', 'sulit' => 'Menantang'];
    public const PLACEMENTS = ['ruang tamu', 'kamar', 'kantor', 'teras', 'dapur', 'kamar mandi'];

    protected $guarded = [];
    protected $casts = ['pet_safe' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function getLightLabelAttribute(): string
    {
        return self::LIGHT[$this->light_level] ?? ucfirst($this->light_level);
    }

    public function getCareLabelAttribute(): string
    {
        return self::CARE[$this->care_level] ?? ucfirst($this->care_level);
    }

    public function getPlacementListAttribute(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->placements))));
    }

    public function getImageUrlAttribute(): ?string
    {
        return Upload::url($this->image, 'plants') ?: Upload::bundled('images/plants/' . $this->slug . '.jpg');
    }
}
