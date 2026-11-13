<?php

namespace Tests\Feature\Reports;

use App\Models\AuditLog;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportAccessExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-13 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_citizens_are_denied_and_permitted_staff_only_see_self_service_metrics(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen']);
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['reports.view'],
        ]);
        $otherStaff = User::factory()->create([
            'role' => 'staff',
            'name' => 'Other Staff Member',
            'permissions' => ['reports.view'],
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        ServiceRequest::factory()->create([
            'status' => ServiceRequest::APPROVED,
            'processed_by' => $otherStaff->id,
            'processed_at' => now(),
        ]);

        $this->actingAs($citizen)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($staff)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('My workload')
            ->assertSee('Requests awaiting action')
            ->assertSee('Identity reviews in queue')
            ->assertSee('Oldest pending request age')
            ->assertDontSee('Other Staff Member')
            ->assertDontSee('Staff Performance')
            ->assertDontSee('Blockchain Transactions')
            ->assertDontSee('Export');
        $this->actingAs($staff)->get(route('admin.reports.export.csv'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.reports.export.pdf'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk();
    }

    public function test_invalid_dates_and_unknown_filter_values_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.reports.index', [
                'from' => '2026-11-10',
                'to' => '2026-11-09',
            ]))
            ->assertSessionHasErrors('to');

        $this->actingAs($admin)
            ->get(route('admin.reports.index', [
                'from' => '2026-11-01',
                'to' => '2026-11-13',
                'document_type' => 'unknown-type',
            ]))
            ->assertSessionHasErrors('document_type');

        $this->actingAs($admin)
            ->get(route('admin.reports.index', [
                'from' => '2020-01-01',
                'to' => '2026-01-02',
            ]))
            ->assertSessionHasErrors('to');

        $this->actingAs($admin)
            ->get(route('admin.reports.index', [
                'from' => '2026-11-01',
                'to' => '2026-11-13',
                'grouping' => 'weekly',
            ]))
            ->assertSessionHasErrors('grouping');
    }

    public function test_report_page_uses_default_daily_filters_and_displays_all_six_report_areas(): void
    {
        ServiceRequest::factory()->create(['created_at' => Carbon::parse('2026-11-12 08:00:00')]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));

        $response->assertOk()
            ->assertSee('value="2026-10-15"', false)
            ->assertSee('value="2026-11-13"', false)
            ->assertSee('data-report-preset="month"', false)
            ->assertSee('data-report-preset="last30"', false)
            ->assertSee('Daily grouping')
            ->assertSee('Service Requests')
            ->assertSee('Citizen Verification')
            ->assertSee('Blockchain Transactions')
            ->assertSee('Document Verification')
            ->assertSee('Staff Performance')
            ->assertSee('No issues detected.')
            ->assertSee('Open ledger')
            ->assertSee('CSV (opens in Excel)')
            ->assertSee('PDF')
            ->assertDontSee('data-report-preset="today"', false)
            ->assertDontSee('data-report-preset="week"', false)
            ->assertDontSee('name="status"', false)
            ->assertDontSee('name="purok"', false)
            ->assertSee('Average Processing Time')
            ->assertSee('In Progress')
            ->assertSee('Approval Performance')
            ->assertSee('Application Trends')
            ->assertSee('Applications by Permit Type')
            ->assertSee('Verification attempts are counted from the date logging began.');

        $this->assertGreaterThanOrEqual(7, substr_count($response->getContent(), 'data-report-chart='));
    }

    public function test_dashboard_comparison_badges_use_the_equal_length_previous_period(): void
    {
        ServiceRequest::factory()->create(['created_at' => Carbon::parse('2026-10-31 23:59:59')]);
        ServiceRequest::factory()->count(2)->create(['created_at' => Carbon::parse('2026-11-01 00:00:00')]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.reports.index', [
                'from' => '2026-11-01',
                'to' => '2026-11-13',
            ]))
            ->assertOk()
            ->assertSee('100.0% vs prior');
    }

    public function test_csv_is_filtered_bom_encoded_formula_safe_and_excludes_personal_data(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Exporter',
        ]);
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'name' => 'Sensitive Applicant Name',
            'email' => 'sensitive@example.test',
        ]);
        ServiceRequest::factory()->create([
            'user_id' => $citizen->id,
            'reference_number' => 'PRIVATE-REFERENCE-001',
            'applicant_name' => 'Sensitive Applicant Name',
            'address' => 'Private Residential Address',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'created_at' => Carbon::parse('2026-11-12 08:00:00'),
        ]);
        foreach ([
            '=HYPERLINK("https://example.test","open")',
            '+SUM(1,1)',
            '-1+1',
            '@SUM(A1:A2)',
        ] as $serviceType) {
            ServiceRequest::factory()->create([
                'user_id' => $citizen->id,
                'service_type' => $serviceType,
                'created_at' => Carbon::parse('2026-11-12 09:00:00'),
            ]);
        }
        ServiceRequest::factory()->create([
            'service_type' => 'OUTSIDE-FILTER',
            'created_at' => Carbon::parse('2026-10-12 08:00:00'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.export.csv', [
            'from' => '2026-11-01',
            'to' => '2026-11-13',
        ]));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'+SUM", $csv);
        $this->assertStringContainsString("'-1+1", $csv);
        $this->assertStringContainsString("'@SUM", $csv);
        $this->assertStringNotContainsString('OUTSIDE-FILTER', $csv);
        $this->assertStringNotContainsString('PRIVATE-REFERENCE-001', $csv);
        $this->assertStringNotContainsString('Sensitive Applicant Name', $csv);
        $this->assertStringNotContainsString('sensitive@example.test', $csv);
        $this->assertStringNotContainsString('Private Residential Address', $csv);

        $audit = AuditLog::query()->where('action', 'report_csv_exported')->sole();
        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame([
            'filters' => [
                'from' => '2026-11-01',
                'to' => '2026-11-13',
                'permit_type' => null,
                'grouping' => 'daily',
            ],
            'format' => 'csv',
        ], $audit->new_values);
        $this->assertNotNull($audit->created_at);
    }

    public function test_pdf_export_returns_a_pdf_and_audits_only_export_metadata(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Report Generator']);

        $response = $this->actingAs($admin)->get(route('admin.reports.export.pdf', [
            'from' => '2026-11-01',
            'to' => '2026-11-13',
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());

        $audit = AuditLog::query()->where('action', 'report_pdf_exported')->sole();
        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame('pdf', $audit->new_values['format']);
        $this->assertSame('2026-11-01', $audit->new_values['filters']['from']);
        $this->assertArrayNotHasKey('summary', $audit->new_values);
    }

    public function test_csv_contains_all_admin_report_sections_and_no_citizen_pii(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'name' => 'Private Citizen Name',
            'email' => 'private-citizen@example.test',
        ]);
        ServiceRequest::factory()->create([
            'user_id' => $citizen->id,
            'applicant_name' => 'Private Citizen Name',
            'address' => 'Private Citizen Address',
            'created_at' => Carbon::parse('2026-11-12 08:00:00'),
        ]);

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.reports.export.csv', [
                'from' => '2026-11-01',
                'to' => '2026-11-13',
            ]));

        $csv = $response->streamedContent();
        foreach ([
            'Service Requests',
            'Citizen Verification',
            'Staff Performance',
            'Blockchain Transactions',
            'Document Verification',
            'Insights',
        ] as $section) {
            $this->assertStringContainsString($section, $csv);
        }
        $this->assertStringNotContainsString('Private Citizen Name', $csv);
        $this->assertStringNotContainsString('private-citizen@example.test', $csv);
        $this->assertStringNotContainsString('Private Citizen Address', $csv);
    }

    public function test_csv_export_route_is_throttled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $url = route('admin.reports.export.csv', [
            'from' => '2026-11-01',
            'to' => '2026-11-13',
        ]);

        for ($request = 0; $request < 10; $request++) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->actingAs($admin)->get($url)->assertTooManyRequests();
    }
}
