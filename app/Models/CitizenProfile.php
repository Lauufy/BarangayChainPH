<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'phone',
    'date_of_birth',
    'address',
    'barangay',
    'civil_status',
    'nationality',
    'religion',
    'gender',
    'house_number',
    'street',
    'city_municipality',
    'province',
    'zip_code',
    'place_of_birth',
    'occupation',
])]
class CitizenProfile extends Model
{
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'religion' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
