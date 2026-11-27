<?php

namespace Tests\Feature\Ocr;

use App\Services\Ocr\IdOcrService;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Tests\TestCase;

class IdOcrServiceTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (file_exists($path) && ! unlink($path)) {
                throw new RuntimeException('Unable to remove a temporary OCR test file.');
            }
        }

        parent::tearDown();
    }

    public function test_returns_empty_text_when_tesseract_succeeds_without_recognizing_text(): void
    {
        config()->set('permit-verification.tesseract', PHP_BINARY);
        $scriptPath = $this->createFakeTesseractScript('', 0);

        $this->assertSame('', app(IdOcrService::class)->read($scriptPath));
    }

    public function test_returns_recognized_text_from_tesseract_stdout(): void
    {
        config()->set('permit-verification.tesseract', PHP_BINARY);
        $scriptPath = $this->createFakeTesseractScript("  recognized text\n", 0);

        $this->assertSame('recognized text', app(IdOcrService::class)->read($scriptPath));
    }

    public function test_throws_when_tesseract_exits_unsuccessfully(): void
    {
        config()->set('permit-verification.tesseract', PHP_BINARY);
        $scriptPath = $this->createFakeTesseractScript('', 1);
        $this->expectException(ProcessFailedException::class);

        app(IdOcrService::class)->read($scriptPath);
    }

    private function createFakeTesseractScript(string $output, int $exitCode): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fake-tesseract-');

        if ($path === false) {
            throw new RuntimeException('Unable to create a temporary OCR test file.');
        }

        $script = \sprintf(
            '<?php fwrite(STDOUT, %s); exit(%d);',
            var_export($output, true),
            $exitCode,
        );

        if (file_put_contents($path, $script) === false) {
            unlink($path);

            throw new RuntimeException('Unable to write the temporary OCR test file.');
        }

        $this->temporaryFiles[] = $path;

        return $path;
    }
}
