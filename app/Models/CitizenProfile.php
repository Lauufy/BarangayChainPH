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
    'photo_path',
])]
class CitizenProfile extends Model
{
    // True when every detail needed for verification is filled in
    public function hasCompleteDetails(): bool
    {
        $required = ['phone', 'date_of_birth', 'gender', 'civil_status', 'nationality', 'place_of_birth', 'barangay', 'city_municipality', 'province', 'zip_code'];

        return collect($required)->every(fn ($field) => filled($this->$field));
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

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
