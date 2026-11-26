<?php

namespace Tests\Feature\Admin;

use App\Models\ContractDeployment;
use App\Services\BlockchainRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncContractDeploymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_validated_deployment_can_replace_the_active_contract(): void
    {
        $owner = '0x'.str_repeat('a', 40);
        $address = '0x'.str_repeat('b', 40);
        $transaction = '0x'.str_repeat('c', 64);
        config(['blockchain.network' => 'sepolia', 'blockchain.contract' => $address]);
        $info = ['ok' => true, 'compatible' => true, 'contract' => $address, 'owner' => $owner, 'deployedAt' => 1720000000];
        $this->mock(BlockchainRegistry::class)->shouldReceive('inspectDeployment')->times(3)
            ->andReturn($info + ['chainMatches' => true], $info + ['chainMatches' => true], $info + ['chainMatches' => false]);
        $args = compact('transaction', 'owner') + ['version' => '2.0'];
        $this->artisan('blockchain:sync-deployment', $args)->assertSuccessful();
        $this->artisan('blockchain:sync-deployment', $args)->assertSuccessful();
        $this->artisan('blockchain:sync-deployment', $args)->assertFailed();
        $this->assertSame($address, ContractDeployment::active()->contract_address);
        $this->assertDatabaseCount('contract_deployments', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'deployment_sync_failed']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'contract_activated']);
    }

    public function test_invalid_address_is_rejected_before_rpc(): void
    {
        config(['blockchain.contract' => 'invalid']);
        $this->mock(BlockchainRegistry::class)->shouldNotReceive('inspectDeployment');
        $this->artisan('blockchain:sync-deployment bad v1 bad')->assertFailed();
        $this->assertDatabaseCount('contract_deployments', 0);
    }
}
