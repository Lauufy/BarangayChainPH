<?php

namespace Tests\Feature\Auth;

use App\Support\Phone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('phoneNumbers')]
    public function test_normalizes_only_valid_philippine_mobile_numbers(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, Phone::normalize($input));
    }

    public static function phoneNumbers(): array
    {
        return [
            'national prefix removed' => ['9171234567', '+639171234567'],
            'leading zero removed' => ['09171234567', '+639171234567'],
            'international formatting removed' => ['+63 917-123-4567', '+639171234567'],
            'too short' => ['917123456', null],
            'wrong mobile prefix' => ['+63212345678', null],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }
}
