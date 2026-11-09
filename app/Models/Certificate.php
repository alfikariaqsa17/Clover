<?php

namespace App\Models;

use App\Support\Upload;
use Illuminate\Database\Eloquent\Model;

/** Foto sertifikat / legalitas yang ditampilkan di situs untuk membangun kepercayaan. */
class Certificate extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];

    /** Hanya yang aktif dan punya foto. Urut: urutan admin, lalu yang terbaru diunggah. */
    public function scopeLive($q)
    {
        return $q->where('is_active', true)->whereNotNull('image')
            ->orderBy('sort_order')->orderByDesc('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return Upload::url($this->image, 'certificates');
    }

    /** Foto kecil untuk galeri (lebih ringan); foto penuh tetap di image_url untuk diperbesar. */
    public function getThumbUrlAttribute(): ?string
    {
        return Upload::thumbUrl($this->image, 'certificates');
    }
}
