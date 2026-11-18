<?php

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

class ProcessIdentityOcr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    // ADAPT: the disk where uploaded IDs are stored (should be a PRIVATE disk).
    private const DISK = 'local';

    public function __construct(public int $verificationId) {}

    public function handle(IdOcrService $ocr, IdFieldExtractor $extractor): void
    {
        $verification = IdentityVerification::with('user')->findOrFail($this->verificationId);
        $user = $verification->user;

        // ADAPT: column that holds the path of the ID photo
        $path = Storage::disk(self::DISK)->path($verification->id_image_path);

        $flags = [];

        try {
            $flags = $ocr->checkQuality($path);
            $text = $ocr->read($path);

            if (trim($text) === '') {
                $flags[] = 'no_text_detected';
            } else {
                // ADAPT: the user fields that hold the citizen's typed details
                if (! $extractor->nameFound((string) $user->last_name, $text)) {
                    $flags[] = 'last_name_not_found';
                }
                if (! $extractor->nameFound((string) $user->first_name, $text)) {
                    $flags[] = 'first_name_not_found';
                }
                if (! $extractor->birthdateFound($user->birthdate, $text)) {
                    $flags[] = 'birthdate_not_found';
                }
            }

            // Only the status and flags are saved. The raw OCR text is NOT
            // stored, to keep to data minimisation.
            $verification->update([
                'ocr_status' => 'done',
                'ocr_flags' => array_values(array_unique($flags)),
                'ocr_processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            $verification->update([
                'ocr_status' => 'failed',
                'ocr_flags' => ['ocr_failed'],
                'ocr_processed_at' => now(),
            ]);
        }
    }
}
