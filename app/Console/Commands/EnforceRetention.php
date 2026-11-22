<?php

namespace App\Console\Commands;

use App\Models\IdentityVerification;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\DataErasureService;
use App\Services\SupabaseStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// php artisan privacy:enforce-retention          (only shows what would happen)
// php artisan privacy:enforce-retention --force  (really deletes)
class EnforceRetention extends Command
{
    protected $signature = 'privacy:enforce-retention {--force : Really delete. Without this, nothing changes}';

    protected $description = 'Delete or anonymize data that passed its retention period';

    public function handle(DataErasureService $erasure, SupabaseStorage $storage): int
    {
        $live = (bool) $this->option('force');
        $rules = config('privacy.retention');

        $this->info($live ? 'LIVE RUN: data will be deleted.' : 'DRY RUN: nothing will change.');

        // 1. Inactive citizens: no activity at all in the audit log, and no pending application.
        $cutoff = now()->subDays($rules['inactive_account_days']);

        $inactive = User::where('role', 'citizen')
            ->whereNull('erased_at')
            ->where('created_at', '<', $cutoff)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('audit_logs')
                ->whereColumn('audit_logs.user_id', 'users.id')
                ->where('audit_logs.created_at', '>=', $cutoff))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('service_requests')
                ->whereColumn('service_requests.user_id', 'users.id')
                ->where('service_requests.status', 'pending'))
            ->get();

        $this->line("Inactive citizens to erase: {$inactive->count()}");

        if ($live) {
            foreach ($inactive as $user) {
                $erasure->erase($user);
            }
        }

        // 2. Old rejected or cancelled applications (never ones with an issued permit).
        $old = ServiceRequest::whereIn('status', ['rejected', 'cancelled'])
            ->where('updated_at', '<', now()->subDays($rules['rejected_application_days']))
            ->whereDoesntHave('issuedDocuments')
            ->get();

        $this->line("Old rejected or cancelled applications to delete: {$old->count()}");

        if ($live) {
            foreach ($old as $request) {
                $erasure->deleteRequest($request);
            }
        }

        // 3. ID photo and selfie of approved identities.
        $idCutoff = now()->subDays($rules['identity_files_after_verification_days']);

        $verifications = IdentityVerification::where('status', IdentityVerification::VERIFIED)
            ->where('reviewed_at', '<', $idCutoff)
            ->where('document_path', '!=', '')
            ->get();

        $this->line("Identity files to delete: {$verifications->count()}");

        if ($live) {
            foreach ($verifications as $verification) {
                $storage->delete($verification->document_path);
                $storage->delete($verification->selfie_path);

                $verification->forceFill(['document_path' => '', 'selfie_path' => null])->save();
            }
        }

        // 4. Applicant details on old permits of erased citizens.
        $permits = ServiceRequest::whereHas('user', fn ($q) => $q->whereNotNull('erased_at'))
            ->whereHas('issuedDocuments', fn ($q) => $q
                ->where('issued_at', '<', now()->subYears($rules['issued_permit_years'])))
            ->where('applicant_name', '!=', 'Erased')
            ->get();

        $this->line("Old permits to anonymize: {$permits->count()}");

        if ($live) {
            foreach ($permits as $permit) {
                $permit->forceFill(['applicant_name' => 'Erased', 'address' => '', 'purpose' => ''])->save();
            }
        }

        return self::SUCCESS;
    }
}