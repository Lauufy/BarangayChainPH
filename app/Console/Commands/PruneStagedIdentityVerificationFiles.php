<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('identity:prune-staged-files')]
#[Description('Delete expired private identity-verification uploads')]
class PruneStagedIdentityVerificationFiles extends Command
{
    public function handle(): int
    {
        $disk = Storage::disk('supabase');
        $cutoff = now()->subDay()->toDateString();
        $deleted = 0;

        foreach ($disk->allFiles('identity-staging') as $path) {
            $segments = explode('/', $path);
            $date = $segments[1] ?? '';

            if (($segments[0] ?? null) !== 'identity-staging'
                || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1
                || $date >= $cutoff) {
                continue;
            }

            if (! $disk->delete($path)) {
                $this->error('An expired identity-verification upload could not be deleted.');

                return self::FAILURE;
            }

            $deleted++;
        }

        $this->info("Deleted {$deleted} expired identity-verification upload(s).");

        return self::SUCCESS;
    }
}
