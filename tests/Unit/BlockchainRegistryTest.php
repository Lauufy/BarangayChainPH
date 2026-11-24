<?php

namespace Tests\Unit;

use App\Services\BlockchainRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BlockchainRegistryTest extends TestCase
{
    public static function emptyIds(): array
    {
        return [
            'empty string' => [''],
            'spaces only' => ['   '],
            'nil uuid' => ['00000000-0000-0000-0000-000000000000'],
        ];
    }

    #[DataProvider('emptyIds')]
    public function test_it_rejects_an_empty_or_all_zero_document_id(string $id): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new BlockchainRegistry)->issue($id, str_repeat('a', 64));
    }
}
