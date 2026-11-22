<?php

namespace App\Services;

use App\Models\DataSubjectRequest;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

// Removes a citizen's personal data but keeps issued permits.
class DataErasureService
{
    public function __construct(private SupabaseStorage $storage) {}

    /** @return array{deleted_requests: int, kept_permits: int, files: int} */
    public function erase(User $user, ?User $actor = null): array
    {
        if (! $user->isCitizen()) {
            throw new RuntimeException('Only citizen accounts can be erased.');
        }

        if (filled($user->erased_at)) {
            throw new RuntimeException('This account was already erased.');
        }

        $paths = [];
        $deleted = 0;
        $kept = 0;

        DB::transaction(function () use ($user, $actor, &$paths, &$deleted, &$kept): void {
            // Remember every file first, delete them after the database work succeeds.
            $paths[] = $user->citizenProfile?->photo_path;

            foreach ($user->identityVerifications as $verification) {
                $paths[] = $verification->document_path;
                $paths[] = $verification->selfie_path;
            }

            foreach (ServiceRequest::where('user_id', $user->id)->get() as $request) {
                if ($request->issuedDocuments()->exists()) {
                    // Issued permits are official records. Keep them, but remove the chat text.
                    $request->ticket?->messages()->delete();
                    $kept++;

                    continue;
                }

                $paths = array_merge($paths, $this->removeRequest($request));
                $deleted++;
            }

            $user->identityVerifications()->delete();
            $user->citizenProfile()->delete();

            if (Schema::hasTable('notifications')) {
                DB::table('notifications')
                    ->where('notifiable_type', $user->getMorphClass())
                    ->where('notifiable_id', $user->id)
                    ->delete();
            }

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            // The account stays as an empty shell so old records still point somewhere.
            $user->forceFill([
                'name' => 'Erased User '.$user->id,
                'email' => "erased+{$user->id}@invalid.local",
                'email_verified_at' => null,
                'phone' => null,
                'phone_verified_at' => null,
                'google_id' => null,
                'google_password_setup_required' => false,
                'password' => Str::random(64),
                'remember_token' => null,
                'consent_ip' => null,
                'two_factor_enabled' => false,
                'status' => 'erased',
                'erased_at' => now(),
            ])->save();

            DataSubjectRequest::where('user_id', $user->id)
                ->where('type', 'erasure')
                ->whereIn('status', ['pending', 'in_review'])
                ->update([
                    'status' => 'completed',
                    'details' => null,
                    'handled_by' => $actor?->id,
                    'handled_at' => now(),
                    'note' => "Erased. {$kept} issued permit(s) kept for legal retention.",
                ]);
        });

        $files = $this->deleteFiles($paths);

        try {
            app(AuditLogger::class)->log(
                'privacy.erasure_completed',
                $user,
                newValues: [
                    'deleted_requests' => $deleted,
                    'kept_permits' => $kept,
                    'files_deleted' => $files,
                ],
                actor: $actor
            );
        } catch (\Throwable $exception) {
            report($exception);
        }

        return ['deleted_requests' => $deleted, 'kept_permits' => $kept, 'files' => $files];
    }

    // Deletes one application that has no issued permit (used by the retention job).
    public function deleteRequest(ServiceRequest $request): void
    {
        $paths = DB::transaction(fn () => $this->removeRequest($request));

        $this->deleteFiles($paths);
    }

    // Deletes the application, its ticket, and returns the file paths to remove.
    private function removeRequest(ServiceRequest $request): array
    {
        // Works once your ServiceRequest model has attachmentPaths() (see notes).
        $paths = method_exists($request, 'attachmentPaths')
            ? collect($request->attachmentPaths())->all()
            : [];

        if ($ticket = $request->ticket) {
            $ticket->messages()->delete();
            $ticket->delete();
        }

        $request->delete();

        return $paths;
    }

    private function deleteFiles(array $paths): int
    {
        $count = 0;

        foreach (array_unique(array_filter($paths)) as $path) {
            $this->storage->delete($path);
            $count++;
        }

        return $count;
    }
}