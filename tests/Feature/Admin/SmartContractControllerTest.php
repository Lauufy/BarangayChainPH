<?php

namespace Tests\Feature\Admin;

use App\Models\BlockchainRecord;
use App\Models\ContractDeployment;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\BlockchainRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartContractControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_smart_contract_tables_render_deployments_officials_and_activity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create([
            'name' => 'Morgan Staff',
            'role' => 'staff',
            'permissions' => ['permits.review'],
        ]);
        ContractDeployment::create([
            'version' => '2.1',
            'network' => 'testnet',
            'contract_address' => '0x'.str_repeat('a', 40),
            'is_active' => true,
            'deployed_at' => now(),
        ]);
        $request = ServiceRequest::factory()->create([
            'reference_number' => 'BRGY-CONTRACT-0001',
        ]);
        $document = IssuedDocument::factory()->for($request)->create([
            'document_number' => 'PERMIT-CONTRACT-0001',
        ]);
        BlockchainRecord::factory()->for($document, 'issuedDocument')->create([
            'recorded_at' => now(),
            'tx_hash' => '0x123456'.str_repeat('a', 30).'abcd',
        ]);
        $this->mock(BlockchainRegistry::class)
            ->shouldReceive('contractInfo')
            ->once()
            ->andReturn(null);

        $this->actingAs($admin)
            ->get(route('admin.smart-contracts.index'))
            ->assertOk()
            ->assertSee('2.1')
            ->assertSee('Morgan Staff')
            ->assertSee('Manage')
            ->assertSee('PERMIT-CONTRACT-0001')
            ->assertSee('Recorded')
            ->assertSee('data-table-search', false)
            ->assertSee('data-table-key="contract-deployments"', false)
            ->assertSee('data-table-key="authorized-officials"', false)
            ->assertSee('data-table-key="contract-activity"', false)
            ->assertSee('placeholder="Search deployments..."', false)
            ->assertSee('placeholder="Search officials..."', false)
            ->assertSee('placeholder="Search activity..."', false);
    }

    public function test_smart_contract_tables_show_shared_empty_states_without_matching_rows(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['contracts.view'],
        ]);
        $this->mock(BlockchainRegistry::class)
            ->shouldReceive('contractInfo')
            ->once()
            ->andReturn(null);

        $this->actingAs($staff)
            ->get(route('admin.smart-contracts.index'))
            ->assertOk()
            ->assertSee('PermitRegistry')
            ->assertSee('No officials found.')
            ->assertSee('No blockchain activity yet.');
    }

    public function test_pause_control_uses_the_shared_confirmation_dialog(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contractAddress = '0x'.str_repeat('a', 40);
        $this->mock(BlockchainRegistry::class)
            ->shouldReceive('contractInfo')
            ->once()
            ->andReturn([
                'hasCode' => true,
                'chainMatches' => true,
                'chainId' => 31337,
                'contract' => $contractAddress,
                'issuer' => '0x'.str_repeat('b', 40),
                'balance' => '1.5',
                'supportsPause' => true,
                'paused' => false,
                'isOwner' => true,
            ]);

        $this->actingAs($admin)
            ->get(route('admin.smart-contracts.index'))
            ->assertOk()
            ->assertSee('data-confirm-label="Pause recording"', false)
            ->assertSee("tConfirmation({ title: 'Confirm processing change'", false);
    }
}
