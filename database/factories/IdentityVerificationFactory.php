<?php

namespace Database\Factories;

use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IdentityVerification>
 */
class IdentityVerificationFactory extends Factory
{
    protected $model = IdentityVerification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'citizen']),
            'status' => IdentityVerification::PENDING,
            'document_type' => 'government_id',
            'document_path' => 'identity/test-document.jpg',
            'submitted_at' => now(),
        ];
    }
}
