<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpCategory extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'sort_order'];

    public function articles(): HasMany
    {
        return $this->hasMany(HelpArticle::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}