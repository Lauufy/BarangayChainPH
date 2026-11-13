<?php

namespace Tests\Feature\Permit;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\DocumentIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Opening a pending request moves it to "processing", and processing requests can still be decided. */
class ProcessingStatusTest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(string $status = ServiceRequest::PENDING): ServiceRequest
    {
        $citizen = User::factory()->create(['role' => 'citizen']);

        return ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-TEST-'.random_int(1000, 9999),
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Barangay San Luis, Antipolo City',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => $status,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** Opens the review page as an admin. */
    private function openAsAdmin(ServiceRequest $application): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.permits.show', $application))
            ->assertOk();
    }

    public function test_admin_opening_a_pending_request_marks_it_processing(): void
    {
        $application = $this->makeRequest();

        $this->openAsAdmin($application);

        $this->assertSame(ServiceRequest::PROCESSING, $application->fresh()->status);
    }

    public function test_opening_a_processing_request_again_changes_nothing(): void
    {
        $application = $this->makeRequest(ServiceRequest::PROCESSING);

        $this->openAsAdmin($application);

        $this->assertSame(ServiceRequest::PROCESSING, $application->fresh()->status);
    }

    public function test_opening_an_approved_request_does_not_change_its_status(): void
    {
        $application = $this->makeRequest(ServiceRequest::APPROVED);

        $this->openAsAdmin($application);

        $this->assertSame(ServiceRequest::APPROVED, $application->fresh()->status);
    }

    public function test_a_processing_request_can_be_rejected(): void
    {
        $application = $this->makeRequest(ServiceRequest::PROCESSING);

        $this->actingAs($this->admin())
            ->post(route('admin.permits.reject', $application), ['rejection_reason' => 'Documents are unreadable.'])
            ->assertRedirect();

        $this->assertSame(ServiceRequest::REJECTED, $application->fresh()->status);
    }

    public function test_a_processing_request_can_be_approved_and_issued(): void
    {
        Storage::fake('supabase');
        $application = $this->makeRequest(ServiceRequest::PROCESSING);

        $document = app(DocumentIssuer::class)->issue($application);

        $this->assertSame(ServiceRequest::APPROVED, $application->fresh()->status);
        $this->assertTrue(Storage::disk('supabase')->exists($document->file_path));
    }
}