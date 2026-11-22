<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\BlockchainRecord;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_downloads_a_timestamped_formula_safe_csv_and_export_is_audited(): void
    {
        $this->travelTo('2026-11-16 19:03:02');
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create([
            'role' => 'citizen',
            'name' => '=HYPERLINK("https://example.test","open")',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.exports.show', ['module' => 'users']));

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertMatchesRegularExpression(
            '/attachment; filename="?users-2026-11-16-190302\.csv"?/',
            (string) $response->headers->get('content-disposition')
        );
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $headings = str_getcsv(ltrim(explode("\n", $csv)[0], "\xEF\xBB\xBF"));
        $this->assertSame('ID', $headings[0]);
        $this->assertSame('Name', $headings[1]);
        $this->assertContains('Date Created', $headings);
        $this->assertStringContainsString("'=HYPERLINK", $csv);

        $audit = AuditLog::query()->where('action', 'admin_users_csv_exported')->sole();
        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame([
            'module' => 'users',
            'format' => 'csv',
            'filters' => [],
        ], $audit->new_values);
        $this->assertNotNull($audit->created_at);
    }

    public function test_users_export_respects_the_selected_tab(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'staff', 'name' => 'Staff Only Record']);
        User::factory()->create(['role' => 'citizen', 'name' => 'Citizen Export Record']);

        $csv = $this->actingAs($admin)
            ->get(route('admin.exports.show', ['module' => 'users', 'users_tab' => 'citizens']))
            ->streamedContent();

        $this->assertStringContainsString('Citizen Export Record', $csv);
        $this->assertStringNotContainsString('Staff Only Record', $csv);
    }

    public function test_users_export_ignores_the_client_side_search_parameter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'citizen', 'name' => 'Matching Search Record']);
        User::factory()->create(['role' => 'citizen', 'name' => 'Unrelated Search Record']);

        $csv = $this->actingAs($admin)
            ->get(route('admin.exports.show', ['module' => 'users', 'search' => 'Matching Search']))
            ->streamedContent();

        $this->assertStringContainsString('Matching Search Record', $csv);
        $this->assertStringContainsString('Unrelated Search Record', $csv);
    }

    public function test_audit_log_export_respects_user_action_and_date_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create(['role' => 'staff']);
        AuditLog::create([
            'user_id' => $admin->id,
            'user_role' => 'admin',
            'action' => 'service_requests.updated',
            'subject_type' => ServiceRequest::class,
            'subject_id' => '101',
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => 'approved'],
            'created_at' => '2026-11-16 10:00:00',
        ]);
        AuditLog::create([
            'user_id' => $otherUser->id,
            'user_role' => 'staff',
            'action' => 'service_requests.updated',
            'subject_type' => ServiceRequest::class,
            'subject_id' => '102',
            'created_at' => '2026-11-15 10:00:00',
        ]);

        $csv = $this->actingAs($admin)->get(route('admin.exports.show', [
            'module' => 'audit-logs',
            'user_id' => $admin->id,
            'action' => 'service_requests.updated',
            'date' => '2026-11-16',
            'search' => 'not-in-record',
        ]))->streamedContent();

        $this->assertStringContainsString('Service request #101', $csv);
        $this->assertStringNotContainsString('Service request #102', $csv);
    }

    public function test_blockchain_export_ignores_the_client_side_search_parameter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $document = IssuedDocument::factory()->create(['document_number' => 'MATCH-LEDGER-001']);
        BlockchainRecord::factory()->for($document, 'issuedDocument')->create(['tx_hash' => '0xmatch']);
        $otherDocument = IssuedDocument::factory()->create(['document_number' => 'OTHER-LEDGER-002']);
        BlockchainRecord::factory()->for($otherDocument, 'issuedDocument')->create(['tx_hash' => '0xother']);

        $csv = $this->actingAs($admin)
            ->get(route('admin.exports.show', ['module' => 'blockchain-ledger', 'search' => 'MATCH-LEDGER']))
            ->streamedContent();

        $this->assertStringContainsString('MATCH-LEDGER-001', $csv);
        $this->assertStringContainsString('OTHER-LEDGER-002', $csv);
    }

    public function test_document_authentication_export_ignores_the_client_side_search_parameter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $matching = ServiceRequest::factory()->create(['applicant_name' => 'Matched Export Applicant']);
        IssuedDocument::factory()->for($matching)->create(['document_number' => 'DOC-MATCH-001']);
        $other = ServiceRequest::factory()->create(['applicant_name' => 'Other Export Applicant']);
        IssuedDocument::factory()->for($other)->create(['document_number' => 'DOC-OTHER-002']);

        $csv = $this->actingAs($admin)
            ->get(route('admin.exports.show', ['module' => 'document-authentication', 'search' => 'Matched Export']))
            ->streamedContent();

        $this->assertStringContainsString('DOC-MATCH-001', $csv);
        $this->assertStringContainsString('DOC-OTHER-002', $csv);
    }

    public function test_staff_without_the_module_permission_gets_forbidden_and_unknown_module_gets_not_found(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'permissions' => []]);
        $this->actingAs($staff)
            ->get(route('admin.exports.show', ['module' => 'permits']))
            ->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('admin.exports.show', ['module' => 'unknown']))
            ->assertNotFound();
    }

    public function test_permitted_staff_can_download_their_module_export_as_a_pdf(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['permits.review'],
        ]);
        ServiceRequest::factory()->create(['reference_number' => 'PDF-PERMIT-001']);

        $response = $this->actingAs($staff)
            ->get(route('admin.exports.show', ['module' => 'permits', 'format' => 'pdf']));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('.pdf', (string) $response->headers->get('content-disposition'));
    }

    public function test_legacy_export_routes_redirect_to_the_shared_endpoint(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.users.export', ['users_tab' => 'citizens']))
            ->assertRedirect(route('admin.exports.show', [
                'module' => 'users',
                'format' => 'csv',
                'users_tab' => 'citizens',
            ]));

        $this->get(route('admin.reports.export.csv', ['from' => '2026-11-01']))
            ->assertRedirect(route('admin.exports.show', [
                'module' => 'reports',
                'format' => 'csv',
                'from' => '2026-11-01',
            ]));

        $this->get(route('admin.reports.export.pdf', ['from' => '2026-11-01']))
            ->assertRedirect(route('admin.exports.show', [
                'module' => 'reports',
                'format' => 'pdf',
                'from' => '2026-11-01',
            ]));
    }

    public function test_unknown_export_format_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.exports.show', ['module' => 'users', 'format' => 'xlsx']))
            ->assertSessionHasErrors('format');
    }
}
