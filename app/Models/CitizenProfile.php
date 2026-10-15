<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
    private const REQUIRED_FIELDS = [
        'phone',
        'date_of_birth',
        'gender',
        'civil_status',
        'nationality',
        'place_of_birth',
        'barangay',
        'city_municipality',
        'province',
        'zip_code',
    ];

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

    public function hasCompleteDetails(): bool
    {
        return collect(self::REQUIRED_FIELDS)->every(fn($field) => filled($this->$field));
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    protected function fullAddress(): Attribute
    {
        return Attribute::get(fn () => implode(', ', array_filter([
            $this->house_number,
            $this->street,
            $this->barangay,
            $this->city_municipality,
            $this->province,
        ])));
    }
}