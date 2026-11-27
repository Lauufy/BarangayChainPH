<?php

namespace Tests\Feature\Admin;

use App\Jobs\RecordPermitOnBlockchain;
use App\Models\BlockchainRecord;
use App\Models\ContractDeployment;
use App\Models\User;
use App\Services\BlockchainRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SmartContractControllerTest extends TestCase
{
    use RefreshDatabase;

    private function deployment(): array
    {
        $contract = '0x'.str_repeat('a', 40);
        $owner = '0x'.str_repeat('b', 40);
        config(['blockchain.network' => 'sepolia', 'blockchain.chain_id' => 11155111, 'blockchain.contract' => $contract]);
        ContractDeployment::create(['network' => 'sepolia', 'active_network' => 'sepolia', 'contract_address' => $contract,
            'owner_address' => $owner, 'is_active' => true, 'version' => '2.1', 'synced_at' => now()]);

        return compact('contract', 'owner') + ['hasCode' => true, 'compatible' => true, 'supportsPause' => true,
            'paused' => false, 'isOwner' => true, 'chainMatches' => true, 'chainId' => 11155111, 'issuerAuthorized' => true, 'issuer' => $owner];
    }

    public function test_staff_monitor_offline_without_management_access(): void
    {
        $this->mock(BlockchainRegistry::class)->shouldReceive('contractInfo')->once()->andReturn(null);
        $staff = User::factory()->create(['role' => 'staff', 'permissions' => ['contracts.view']]);
        $this->actingAs($staff)->get(route('admin.smart-contracts.index'))->assertOk()
            ->assertSee('Blockchain status unavailable')->assertSee('No deployments recorded')->assertDontSee('Blockchain Transactions')
            ->assertDontSee('Authorized Officials')->assertDontSee('Retry failed transactions');
        $this->post(route('admin.smart-contracts.pause'), ['paused' => 1])->assertForbidden();
        $this->post(route('admin.smart-contracts.recovery', 'retry'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'staff', 'permissions' => []]))
            ->get(route('admin.smart-contracts.index'))->assertForbidden();
    }

    public function test_mismatched_or_unavailable_registry_prevents_writes_and_logs_failure(): void
    {
        $info = $this->deployment();
        $registry = $this->mock(BlockchainRegistry::class);
        $registry->shouldReceive('contractInfo')->times(3)->andReturn(null, ['chainMatches' => false] + $info, ['contract' => 'other'] + $info);
        $registry->shouldNotReceive('setPaused');
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (range(1, 3) as $attempt) {
            $this->actingAs($admin)->post(route('admin.smart-contracts.pause'), ['paused' => 1])->assertSessionHas('error');
        }
        $this->assertDatabaseHas('audit_logs', ['action' => 'contract_operation_failed', 'user_id' => $admin->id]);
    }

    public function test_pause_and_resume_require_receipt_and_requeue_only_paused_documents(): void
    {
        Queue::fake();
        $info = $this->deployment();
        $registry = $this->mock(BlockchainRegistry::class);
        $registry->shouldReceive('contractInfo')->times(3)->andReturn($info, $info, ['paused' => true] + $info);
        $receipt = ['ok' => true, 'txHash' => '0x'.str_repeat('c', 64)] + $info;
        $registry->shouldReceive('setPaused')->with(true)->twice()->andReturn(['ok' => true], ['paused' => true] + $receipt);
        $registry->shouldReceive('setPaused')->with(false)->once()->andReturn($receipt);
        $record = BlockchainRecord::factory()->create(['network' => 'sepolia', 'contract' => $info['contract'], 'error' => 'ContractPaused']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post(route('admin.smart-contracts.pause'), ['paused' => 1])->assertSessionHas('error');
        $this->post(route('admin.smart-contracts.pause'), ['paused' => 1])->assertSessionHas('success');
        $this->post(route('admin.smart-contracts.pause'), ['paused' => 0])->assertSessionHas('success');
        Queue::assertPushed(RecordPermitOnBlockchain::class, fn ($job) => $job->issuedDocumentId === $record->issued_document_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'contract_resumed']);
    }

    public function test_recovery_uses_actual_states_and_links_to_the_ledger(): void
    {
        Queue::fake();
        $info = $this->deployment();
        $this->mock(BlockchainRegistry::class)->shouldReceive('contractInfo')->andReturn($info);
        $target = ['network' => 'sepolia', 'contract' => $info['contract']];
        $failed = BlockchainRecord::factory()->create($target + ['error' => 'RPC unavailable']);
        $pending = BlockchainRecord::factory()->create($target);
        BlockchainRecord::factory()->create($target + ['recorded_at' => now()]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('admin.smart-contracts.index'))->assertOk()
            ->assertSee(route('admin.blockchain-ledger.index', ['status' => 'failed']))->assertDontSee($failed->issuedDocument->document_number)->assertDontSee($pending->issuedDocument->document_number)
            ->assertSee('data-confirm-label="Pause blockchain recording"', false);
        $this->post(route('admin.smart-contracts.recovery', 'retry'))->assertSessionHas('registry_notice');
        $this->post(route('admin.smart-contracts.recovery', 'recover'))->assertSessionHas('registry_notice');
        Queue::assertPushed(RecordPermitOnBlockchain::class, 2);
        $this->assertDatabaseHas('audit_logs', ['action' => 'contract_recover']);
    }
}
