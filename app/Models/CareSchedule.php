<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareSchedule extends Model
{
    public const TYPES = ['siram' => 'Penyiraman', 'pupuk' => 'Pemupukan', 'repot' => 'Repotting', 'pangkas' => 'Pemangkasan', 'bersihkan daun' => 'Bersihkan daun', 'lainnya' => 'Lainnya'];

    protected $guarded = [];
    protected $casts = ['next_due_date' => 'date', 'last_done_at' => 'date', 'is_active' => 'boolean'];

    public function userPlant()
    {
        return $this->belongsTo(UserPlant::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->next_due_date->lt(today());
    }
}
