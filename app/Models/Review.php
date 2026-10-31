<?php

namespace App\Models;

use App\Support\Upload;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    public const STATUSES = ['pending' => 'Menunggu', 'approved' => 'Tampil', 'rejected' => 'Disembunyikan'];

    protected $guarded = [];
    protected $casts = ['is_verified' => 'boolean', 'photos' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** @return array<int,string> */
    public function photoUrls(): array
    {
        return array_values(array_filter(array_map(fn ($p) => Upload::url($p, 'reviews'), $this->photos ?? [])));
    }

    public function scopeApproved($q)
    {
        return $q->where('status', 'approved')->where('is_verified', true);
    }
}
