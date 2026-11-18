<?php

// Put this file at: app/Jobs/ProcessIdentityOcr.php
//
// Lines marked ADAPT are the only places that depend on your own schema.

namespace App\Jobs;

use App\Models\IdentityVerification;
use App\Services\Ocr\IdFieldExtractor;
use App\Services\Ocr\IdOcrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessIdentityOcr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public int $verificationId) {}

    public function handle(IdOcrService $ocr, IdFieldExtractor $extractor): void
    {
        $verification = IdentityVerification::with('user')->findOrFail($this->verificationId);
        $flags = [];
        $temporaryPath = null;

        try {
            $temporaryPath = tempnam(sys_get_temp_dir(), 'identity-ocr-');

            if ($temporaryPath === false) {
                throw new RuntimeException('Unable to create a temporary file for identity OCR.');
            }

            $contents = Storage::disk('supabase')->get($verification->document_path);

            if (file_put_contents($temporaryPath, $contents) === false) {
                throw new RuntimeException('Unable to write the identity document for OCR.');
            }

            $flags = $ocr->checkQuality($temporaryPath);
            $text = $ocr->read($temporaryPath);

            if (trim($text) === '') {
                $flags[] = 'no_text_detected';
            } else {
                $user = $verification->user;
                $profile = $user->citizenProfile;

                if (! $extractor->nameFound((string) $user->name, $text)) {
                    $flags[] = 'name_not_found';
                }

                if (! $extractor->birthdateFound($profile?->date_of_birth?->toDateString(), $text)) {
                    $flags[] = 'birthdate_not_found';
                }
            }

            $verification->update([
                'ocr_status' => 'done',
                'ocr_flags' => array_values(array_unique($flags)),
                'ocr_processed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $verification->update([
                'ocr_status' => 'failed',
                'ocr_flags' => ['ocr_failed'],
                'ocr_processed_at' => now(),
            ]);
        } finally {
            if (is_string($temporaryPath) && file_exists($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }
}
