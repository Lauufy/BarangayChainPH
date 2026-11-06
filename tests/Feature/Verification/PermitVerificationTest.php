<?php

namespace Tests\Feature\Verification;

use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Covers certificate verification states and holder-data privacy. */
class PermitVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_document_displays_genuine_page_with_masked_name(): void
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

        $filePath = 'issued-documents/valid-document.pdf';
        $fileContents = 'Valid PDF document content';

        Storage::disk('local')->put($filePath, $fileContents);

        $documentUuid = (string) Str::uuid();

        IssuedDocument::create([
            'service_request_id' => $application->id,
            'document_uuid' => $documentUuid,
            'document_number' => 'PERMIT-20261030-ABCDEFGH',
            'version' => 1,
            'disk' => 'local',
            'file_path' => $filePath,
            'file_hash' => hash('sha256', $fileContents),
            'issued_at' => '2026-10-30 12:00:00',
        ]);

        $this->get(route('permits.verify', [
            'documentUuid' => $documentUuid,
        ]))
            ->assertOk()
            ->assertViewIs('permits.verify')
            ->assertSee('This certificate is valid.')
            ->assertSee('Barangay Clearance')
            ->assertSee('C*** M***')
            ->assertSee('BRGY-20261030-0001')
            ->assertSee('October 30, 2026')
            ->assertSee('Barangay San Luis')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertDontSee('Carlo Mamaril')
            ->assertDontSee('Private address')
            ->assertDontSee('Employment')
            ->assertDontSee('PERMIT-20261030-ABCDEFGH')
            ->assertDontSee($filePath);
    }

    public function test_revoked_document_hides_holder_details(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0002',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Private address',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::APPROVED,
            'permit_number' => 'PERMIT-20261030-REVOKED1',
        ]);

        $filePath = 'issued-documents/revoked-document.pdf';
        $fileContents = 'Revoked PDF document content';

        Storage::disk('local')->put($filePath, $fileContents);

        $documentUuid = (string) Str::uuid();
        $revokedAt = now();

        IssuedDocument::create([
            'service_request_id' => $application->id,
            'document_uuid' => $documentUuid,
            'document_number' => 'PERMIT-20261030-REVOKED1',
            'version' => 1,
            'disk' => 'local',
            'file_path' => $filePath,
            'file_hash' => hash('sha256', $fileContents),
            'issued_at' => $revokedAt,
            'revoked_at' => $revokedAt,
        ]);

        $this->get(route('permits.verify', [
            'documentUuid' => $documentUuid,
        ]))
            ->assertOk()
            ->assertSee('This certificate has been revoked.')
            ->assertSee('Revoked on')
            ->assertSee($revokedAt->format('F j, Y'))
            ->assertSee('Barangay Clearance')
            ->assertDontSee('Carlo M.')
            ->assertDontSee('Carlo Mamaril')
            ->assertDontSee('PERMIT-20261030-REVOKED1')
            ->assertDontSee('BRGY-20261030-0002');
    }

    public function test_unknown_document_uuid_displays_no_record_found(): void
    {
        $this->get(route('permits.verify', [
            'documentUuid' => (string) Str::uuid(),
        ]))
            ->assertOk()
            ->assertViewIs('permits.verify')
            ->assertSee('Not found')
            ->assertDontSee('Carlo Mamaril');
    }

    public function test_document_with_tampered_file_displays_cannot_be_confirmed(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0003',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Private address',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::APPROVED,
            'permit_number' => 'PERMIT-20261030-TAMPERED',
        ]);

        $filePath = 'issued-documents/tampered-document.pdf';

        // The stored file no longer matches the hash recorded at issuance.
        Storage::disk('local')->put($filePath, 'Edited PDF document content');

        $documentUuid = (string) Str::uuid();

        IssuedDocument::create([
            'service_request_id' => $application->id,
            'document_uuid' => $documentUuid,
            'document_number' => 'PERMIT-20261030-TAMPERED',
            'version' => 1,
            'disk' => 'local',
            'file_path' => $filePath,
            'file_hash' => hash('sha256', 'Original PDF document content'),
            'issued_at' => now(),
        ]);

        $this->get(route('permits.verify', [
            'documentUuid' => $documentUuid,
        ]))
            ->assertOk()
            ->assertSee('This certificate could not be verified.')
            ->assertDontSee('Carlo M.')
            ->assertDontSee('PERMIT-20261030-TAMPERED')
            ->assertDontSee('BRGY-20261030-0003');
    }

    public function test_unapproved_application_displays_cannot_be_confirmed(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0004',
            'service_type' => ServiceRequest::CERTIFICATE_OF_RESIDENCY,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Private address',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
            'permit_number' => 'PERMIT-20261030-PENDING1',
        ]);

        $filePath = 'issued-documents/pending-document.pdf';
        $fileContents = 'Pending PDF document content';

        // File and hash are intact, so the only failing condition is the request status.
        Storage::disk('local')->put($filePath, $fileContents);

        $documentUuid = (string) Str::uuid();

        IssuedDocument::create([
            'service_request_id' => $application->id,
            'document_uuid' => $documentUuid,
            'document_number' => 'PERMIT-20261030-PENDING1',
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
            ->assertSee('This certificate could not be verified.')
            ->assertDontSee('Carlo M.')
            ->assertDontSee('PERMIT-20261030-PENDING1')
            ->assertDontSee('BRGY-20261030-0004');
    }

    public function test_missing_stored_file_displays_cannot_be_confirmed(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0005',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Private address',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::APPROVED,
            'permit_number' => 'PERMIT-20261030-MISSING1',
        ]);

        $documentUuid = (string) Str::uuid();

        // The PDF is never stored on the disk, so the record points at nothing.
        IssuedDocument::create([
            'service_request_id' => $application->id,
            'document_uuid' => $documentUuid,
            'document_number' => 'PERMIT-20261030-MISSING1',
            'version' => 1,
            'disk' => 'local',
            'file_path' => 'issued-documents/missing-document.pdf',
            'file_hash' => hash('sha256', 'Missing PDF document content'),
            'issued_at' => now(),
        ]);

        $this->get(route('permits.verify', [
            'documentUuid' => $documentUuid,
        ]))
            ->assertOk()
            ->assertSee('This certificate could not be verified.')
            ->assertDontSee('Carlo M.')
            ->assertDontSee('PERMIT-20261030-MISSING1')
            ->assertDontSee('BRGY-20261030-0005');
    }

    public function test_storage_failure_displays_could_not_be_checked(): void
    {
        $disk = \Mockery::mock(FilesystemAdapter::class);

        $disk->shouldReceive('exists')
            ->once()
            ->andThrow(new \RuntimeException('Storage unavailable'));

        Storage::shouldReceive('disk')
            ->with('supabase')
            ->andReturn($disk);

        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0006',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Private address',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::APPROVED,
            'permit_number' => 'PERMIT-20261030-OFFLINE1',
        ]);

        $documentUuid = (string) Str::uuid();

        IssuedDocument::create([
            'service_request_id' => $application->id,
            'document_uuid' => $documentUuid,
            'document_number' => 'PERMIT-20261030-OFFLINE1',
            'version' => 1,
            'disk' => 'supabase',
            'file_path' => 'issued-documents/offline-document.pdf',
            'file_hash' => hash('sha256', 'Offline PDF document content'),
            'issued_at' => now(),
        ]);

        $this->get(route('permits.verify', [
            'documentUuid' => $documentUuid,
        ]))
            ->assertOk()
            ->assertSee('Verification could not be completed right now.')
            ->assertDontSee('Carlo M.')
            ->assertDontSee('PERMIT-20261030-OFFLINE1');
    }

    public function test_whitespace_only_applicant_name_is_masked_as_applicant(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0007',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => '   ',
            'address' => 'Private address',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::APPROVED,
            'permit_number' => 'PERMIT-20261030-BLANKNM1',
        ]);

        $filePath = 'issued-documents/blank-name-document.pdf';
        $fileContents = 'Blank name PDF document content';

        Storage::disk('local')->put($filePath, $fileContents);

        $documentUuid = (string) Str::uuid();

        IssuedDocument::create([
            'service_request_id' => $application->id,
            'document_uuid' => $documentUuid,
            'document_number' => 'PERMIT-20261030-BLANKNM1',
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
            ->assertSee('This certificate is valid.')
            ->assertSee('Applicant')
            ->assertViewHas('maskedApplicantName', 'Applicant');
    }
}
