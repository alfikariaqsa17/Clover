<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Pertanyaan calon pembeli pada halaman produk; dijawab oleh admin. */
class ProductQuestion extends Model
{
    protected $guarded = [];
    protected $casts = ['is_visible' => 'boolean', 'answered_at' => 'datetime'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisible($q)
    {
        return $q->where('is_visible', true);
    }
}
