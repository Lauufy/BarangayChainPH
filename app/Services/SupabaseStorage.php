<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LogicException;
use RuntimeException;

class SupabaseStorage
{
    /** How long a signed photo link stays valid. */
    private const URL_MINUTES = 60;

    /** Uploads a file to the Supabase disk and returns its path in the bucket (e.g. "avatars/xyz.jpg"). */
    public function upload(UploadedFile $file, string $folder = 'avatars'): string
    {
        $path = $this->disk()->putFile(trim($folder, '/'), $file);

        if (! $path) {
            throw new RuntimeException('The file could not be uploaded to Supabase.');
        }

        return $path;
    }

    /** Deletes a file. Failures are reported but never break the request. */
    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            $this->disk()->delete($path);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Signed link for displaying a file from the private bucket (null when there is no file). */
    public function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return $this->temporaryUrl($path, now()->addMinutes(self::URL_MINUTES));
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiration): string
    {
        $disk = $this->disk();

        if (! $disk instanceof FilesystemAdapter) {
            throw new LogicException('The Supabase disk does not support temporary URLs.');
        }

        return $disk->temporaryUrl($path, $expiration);
    }

    private function disk()
    {
        return Storage::disk('supabase');
    }
}