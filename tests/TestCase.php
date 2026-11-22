<?php

namespace Tests;

use App\Services\BlockchainRegistry;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // No test may reach the real blockchain. Every test starts with this
    // stand-in. A test that needs other answers sets up its own mock,
    // which replaces this one.
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(BlockchainRegistry::class, function ($mock) {
            $mock->shouldReceive('verify')->andReturn(['ok' => true, 'status' => 'not_found']);
            $mock->shouldReceive('issue')->andReturn(['ok' => true, 'txHash' => '0xtest', 'contract' => '0xtest']);
            $mock->shouldReceive('revoke')->andReturn(['ok' => true, 'txHash' => '0xtest']);
            $mock->shouldReceive('contractInfo')->andReturn(null);
        });
    }
}
