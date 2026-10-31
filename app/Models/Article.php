<?php

namespace App\Models;

use App\Support\Upload;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $guarded = [];
    protected $casts = ['published_at' => 'date', 'is_published' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category()
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function scopePublished($q)
    {
        return $q->where('is_published', true)->whereDate('published_at', '<=', now()->toDateString());
    }

    public function getImageUrlAttribute(): ?string
    {
        return Upload::url($this->image, 'articles') ?: Upload::bundled('images/tutorials/' . $this->slug . '.jpg');
    }

    public function getReadMinutesAttribute(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->body)) / 200));
    }
}
