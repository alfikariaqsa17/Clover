<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $guarded = [];
    protected $casts = ['shipped_at' => 'datetime', 'delivered_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
