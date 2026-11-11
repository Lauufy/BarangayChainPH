<?php

namespace Tests\Feature\Verification;

use App\Models\BlockchainRecord;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminBlockchainLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_ledger_lists_a_recorded_permit_with_its_explorer_link(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['blockchain.view'],
            'is_active' => true,
        ]);
        $citizen = User::factory()->create(['role' => 'citizen', 'is_active' => true]);

        $application = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-'.Str::upper(Str::random(12)),
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Test Applicant',
            'address' => 'Barangay San Luis',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::APPROVED,
            'permit_number' => 'PERMIT-LEDGER-1',
        ]);

        $document = IssuedDocument::create([
            'service_request_id' => $application->id,
            'document_uuid' => (string) Str::uuid(),
            'document_number' => $application->permit_number,
            'version' => 1,
            'disk' => 'local',
            'file_path' => 'issued-documents/test.pdf',
            'file_hash' => str_repeat('a', 64),
            'issued_at' => now(),
        ]);

        $txHash = '0x'.str_repeat('ab', 32);

        BlockchainRecord::create([
            'issued_document_id' => $document->id,
            'tx_hash' => $txHash,
            'recorded_at' => now(),
        ]);

        $this->actingAs($staff)
            ->get(route('admin.blockchain-ledger.index'))
            ->assertOk()
            ->assertSee('PERMIT-LEDGER-1')
            ->assertSee('/tx/'.$txHash, false);
    }
}