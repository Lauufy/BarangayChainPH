<?php

namespace Tests\Feature;

use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PermitVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_issued_document_displays_masked_applicant_name(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0001',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Private address',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::APPROVED,
            'permit_number' => 'PERMIT-20261030-ABCDEFGH',
        ]);

        $filePath = 'issued-documents/test-document.pdf';
        $fileContents = 'Test PDF document content';
        $documentUuid = (string) Str::uuid();

        Storage::disk('local')->put($filePath, $fileContents);

        IssuedDocument::create([
            'service_request_id' => $application->id,
            'document_uuid' => $documentUuid,
            'document_number' => 'PERMIT-20261030-ABCDEFGH',
            'version' => 1,
            'disk' => 'local',
            'file_path' => $filePath,
            'file_hash' => hash('sha256', $fileContents),
            'issued_at' => now(),
        ]);

        $this->get(route('permits.verify', [
            'documentUuid' => $documentUuid,
        ]))
            ->assertOk()
            ->assertViewIs('permits.verify')
            ->assertSee('Valid document')
            ->assertSee('Barangay Clearance')
            ->assertSee('Carlo M.')
            ->assertSee('PERMIT-20261030-ABCDEFGH')
            ->assertDontSee('Carlo Mamaril')
            ->assertDontSee('Private address')
            ->assertDontSee('Employment');
    }

    public function test_verification_does_not_show_personal_details_for_unapproved_application(): void
    {
        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0002',
            'service_type' => ServiceRequest::CERTIFICATE_OF_RESIDENCY,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Private address',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);

        $documentUuid = (string) Str::uuid();

        IssuedDocument::create([
            'service_request_id' => $application->id,
            'document_uuid' => $documentUuid,
            'document_number' => 'PERMIT-20261030-0002',
            'version' => 1,
            'disk' => 'local',
            'file_path' => 'issued-documents/pending-document.pdf',
            'file_hash' => hash('sha256', 'Pending document'),
            'issued_at' => now(),
        ]);

        $this->get(route('permits.verify', [
            'documentUuid' => $documentUuid,
        ]))
            ->assertOk()
            ->assertSee('Invalid or unapproved document')
            ->assertDontSee('Carlo M.')
            ->assertDontSee('Carlo Mamaril')
            ->assertDontSee('Private address')
            ->assertDontSee('BRGY-20261030-0002');
    }

    public function test_unknown_document_uuid_displays_invalid_document(): void
    {
        $this->get(route('permits.verify', [
            'documentUuid' => (string) Str::uuid(),
        ]))
            ->assertOk()
            ->assertSee('Invalid or unapproved document')
            ->assertDontSee('Carlo Mamaril');
    }
}
