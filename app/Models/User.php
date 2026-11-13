<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Services\SupabaseStorage;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'email_verified_at',
    'consent_given_at',
    'privacy_policy_version',
    'consent_ip',
    'phone',
    'phone_verified_at',
    'password',
    'google_id',
    'role',
])]
#[Hidden([
    'password',
    'remember_token',
])]

/** Represents an authenticated citizen or staff account. */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'consent_given_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'google_password_setup_required' => 'boolean',
            'permissions' => 'array',
            'two_factor_enabled' => 'boolean',
        ];
    }

    public function isCitizen(): bool
    {
        return $this->role === 'citizen';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // Admins can do everything. Staff can only do what was granted to them.
    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->isStaff() && in_array($permission, $this->permissions ?? [], true);
    }

    public function citizenProfile(): HasOne
    {
        return $this->hasOne(CitizenProfile::class);
    }

    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    // All identity verification applications of this user
    public function identityVerifications(): HasMany
    {
        return $this->hasMany(IdentityVerification::class);
    }

    // The most recent application (or null if the citizen never submitted)
    public function latestIdentityVerification(): HasOne
    {
        return $this->hasOne(IdentityVerification::class)->latestOfMany();
    }

    // Returns: unverified, pending, verified, or rejected
    public function identityStatus(): string
    {
        return $this->latestIdentityVerification?->status ?? 'unverified';
    }

    // True only after staff or admin approved the application
    public function isIdentityVerified(): bool
    {
        return $this->identityStatus() === IdentityVerification::VERIFIED;
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function () {
            $path = $this->employeeProfile?->photo_path ?? $this->citizenProfile?->photo_path;

            return app(SupabaseStorage::class)->url($path);
        });
    }
}
