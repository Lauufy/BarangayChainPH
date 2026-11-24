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

    public function test_uploads_are_limited_to_ten_attempts_a_minute(): void
    {
        $url = route('permits.verify.upload');

        for ($i = 0; $i < 10; $i++) {
            $this->post($url, ['ref' => (string) Str::uuid()])->assertUnprocessable();
        }

        $this->post($url)->assertStatus(429);
    }
}
