<?php

namespace App\Models;

use App\Support\Upload;
use Illuminate\Database\Eloquent\Model;

class UserPlant extends Model
{
    protected $guarded = [];
    protected $casts = ['acquired_at' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function schedules()
    {
        return $this->hasMany(CareSchedule::class)->orderBy('next_due_date');
    }

    public function logs()
    {
        return $this->hasMany(CareLog::class)->orderByDesc('done_at')->orderByDesc('id');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return Upload::url($this->photo, 'my-plants');
    }
}
