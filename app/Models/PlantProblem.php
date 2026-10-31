<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantProblem extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
