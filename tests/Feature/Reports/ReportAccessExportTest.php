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

    public function test_reports_are_admin_only_even_when_staff_has_the_reports_permission(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen']);
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['reports.view'],
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($citizen)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.reports.index'))->assertForbidden();
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
    }

    public function test_report_page_uses_the_default_range_and_only_shows_essential_sections(): void
    {
        ServiceRequest::factory()->create(['created_at' => Carbon::parse('2026-11-12 08:00:00')]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));

        $response->assertOk()
            ->assertSee('value="2026-10-15"', false)
            ->assertSee('value="2026-11-13"', false)
            ->assertSee('data-report-preset="month"', false)
            ->assertSee('data-report-preset="last30"', false)
            ->assertSee('No issues detected.')
            ->assertSee('Open ledger')
            ->assertSee('Download CSV')
            ->assertDontSee('data-report-preset="today"', false)
            ->assertDontSee('data-report-preset="week"', false)
            ->assertDontSee('name="status"', false)
            ->assertDontSee('name="purok"', false)
            ->assertDontSee('Status distribution')
            ->assertDontSee('Median processing')
            ->assertDontSee('Rejection reasons')
            ->assertDontSee('Citizen identity summary')
            ->assertDontSee('Requests by purok')
            ->assertDontSee('Download PDF');

        $this->assertSame(2, substr_count($response->getContent(), 'data-report-chart='));
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
                'document_type' => null,
            ],
        ], $audit->new_values);
        $this->assertNotNull($audit->created_at);
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
