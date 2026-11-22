<?php

namespace Database\Factories;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    protected $model = ServiceRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reference_number' => 'BRGY-'.$this->faker->unique()->numerify('########'),
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => $this->faker->name(),
            'address' => $this->faker->address(),
            'age' => $this->faker->numberBetween(18, 90),
            'purpose' => $this->faker->sentence(),
            'status' => ServiceRequest::PENDING,
        ];
    }
}
