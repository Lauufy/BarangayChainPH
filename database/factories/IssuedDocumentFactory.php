<?php

namespace Database\Factories;

use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IssuedDocument>
 */
class IssuedDocumentFactory extends Factory
{
    protected $model = IssuedDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'service_request_id' => ServiceRequest::factory()->state(['status' => ServiceRequest::APPROVED]),
            'document_uuid' => $uuid,
            'document_number' => 'PERMIT-'.$this->faker->unique()->numerify('########'),
            'version' => 1,
            'disk' => 'local',
            'file_path' => "issued-documents/{$uuid}.pdf",
            'file_hash' => hash('sha256', $uuid),
            'issued_at' => now(),
        ];
    }
}
