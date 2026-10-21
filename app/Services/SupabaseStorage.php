<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use LogicException;

class SupabaseStorage
{
    public function temporaryUrl(string $path, DateTimeInterface $expiration): string
    {
        $disk = Storage::disk('supabase');

        if (! $disk instanceof FilesystemAdapter) {
            throw new LogicException('The Supabase disk does not support temporary URLs.');
        }

        return $disk->temporaryUrl($path, $expiration);
    }
}
