<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareLog extends Model
{
    protected $guarded = [];
    protected $casts = ['done_at' => 'date'];

    public function userPlant()
    {
        return $this->belongsTo(UserPlant::class);
    }
}
