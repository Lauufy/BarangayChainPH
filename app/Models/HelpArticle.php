<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpArticle extends Model
{
    protected $fillable = [
        'help_category_id', 'title', 'slug', 'summary', 'body', 'is_popular', 'is_published',
    ];

    protected function casts(): array
    {
        return ['is_popular' => 'boolean', 'is_published' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(HelpCategory::class, 'help_category_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(fn ($q) => $q
            ->whereLike('title', $like)
            ->orWhereLike('summary', $like)
            ->orWhereLike('body', $like));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}