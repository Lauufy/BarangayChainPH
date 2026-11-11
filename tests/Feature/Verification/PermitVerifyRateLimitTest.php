<?php

namespace Tests\Feature\Verification;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PermitVerifyRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_page_is_limited_to_sixty_visits_a_minute(): void
    {
        $url = route('permits.verify', (string) Str::uuid());

        for ($i = 0; $i < 60; $i++) {
            $this->get($url);
        }

        $this->get($url)->assertStatus(429);
    }
}