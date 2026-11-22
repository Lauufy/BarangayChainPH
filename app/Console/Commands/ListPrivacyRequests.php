<?php

namespace App\Console\Commands;

use App\Models\DataSubjectRequest;
use Illuminate\Console\Command;

// php artisan privacy:requests
class ListPrivacyRequests extends Command
{
    protected $signature = 'privacy:requests';

    protected $description = 'Show open personal data requests, oldest due date first';

    public function handle(): int
    {
        $rows = DataSubjectRequest::with('user:id,name')
            ->whereIn('status', ['pending', 'in_review'])
            ->orderBy('due_at')
            ->get()
            ->map(fn ($r) => [
                $r->id,
                $r->type,
                $r->user_id.' - '.($r->user?->name ?? 'n/a'),
                $r->due_at->toDateString().($r->due_at->isPast() ? ' (LATE)' : ''),
                \Illuminate\Support\Str::limit((string) $r->details, 50),
            ]);

        $this->table(['ID', 'Type', 'Citizen', 'Due', 'Details'], $rows);

        return self::SUCCESS;
    }
}