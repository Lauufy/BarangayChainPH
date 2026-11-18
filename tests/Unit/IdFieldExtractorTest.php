<?php

// Put this file at: tests/Unit/IdFieldExtractorTest.php

namespace Tests\Unit;

use App\Services\Ocr\IdFieldExtractor;
use PHPUnit\Framework\TestCase;

class IdFieldExtractorTest extends TestCase
{
    private IdFieldExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new IdFieldExtractor;
    }

    public function test_finds_name_with_enye_and_small_ocr_error(): void
    {
        $ocr = "REPUBLIKA NG PILIPINAS\nAPELYIDO\nDE LA PENA\nMGA PANGALAN\nJUAN CARLOS";

        $this->assertTrue($this->extractor->nameFound('Peña', $ocr));
        $this->assertTrue($this->extractor->nameFound('Juan Carlos', $ocr));
        // one wrong letter from OCR (CARLQS) should still match
        $this->assertTrue($this->extractor->nameFound('Carlos', 'JUAN CARLQS'));
    }

    public function test_wrong_name_is_not_found(): void
    {
        $this->assertFalse($this->extractor->nameFound('Santos', 'JUAN CARLOS DE LA PENA'));
    }

    public function test_finds_birthdate_in_common_formats(): void
    {
        $this->assertTrue($this->extractor->birthdateFound('1990-05-12', 'Petsa ng Kapanganakan MAY 12, 1990'));
        $this->assertTrue($this->extractor->birthdateFound('1990-05-12', 'DOB 12 MAY 1990'));
        $this->assertTrue($this->extractor->birthdateFound('1990-05-12', 'Birth 1990/05/12'));
        $this->assertTrue($this->extractor->birthdateFound('1990-05-12', 'Birth 12-05-1990'));
        $this->assertTrue($this->extractor->birthdateFound('1990-05-12', 'Birth 05/12/1990'));
    }

    public function test_wrong_birthdate_is_not_found(): void
    {
        $this->assertFalse($this->extractor->birthdateFound('1991-05-12', 'MAY 12, 1990'));
    }

    public function test_empty_inputs_do_not_crash(): void
    {
        $this->assertFalse($this->extractor->birthdateFound(null, 'anything'));
        $this->assertFalse($this->extractor->nameFound('', 'anything'));
    }
}
