<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['requested_by', 'reviewer_name', 'items', 'message', 'submitted_at'])]
class PermitRevision extends Model
{
    protected function casts(): array
    {
        return ['items' => 'array', 'submitted_at' => 'datetime'];
    }
}
