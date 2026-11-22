<?php

use App\Models\ServiceRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $serviceRequestMorph = (new ServiceRequest)->getMorphClass();

        DB::table('service_requests')
            ->whereIn('status', ['processing', 'approved', 'rejected'])
            ->where(function ($query): void {
                $query->whereNull('processed_by')->orWhereNull('processed_at');
            })
            ->orderBy('id')
            ->chunkById(500, function ($requests) use ($serviceRequestMorph): void {
                $requestIds = $requests->pluck('id')->map(fn ($id) => (string) $id)->all();
                $events = DB::table('audit_logs')
                    ->where('subject_type', $serviceRequestMorph)
                    ->where('action', 'status_changed')
                    ->whereIn('subject_id', $requestIds)
                    ->whereNotNull('user_id')
                    ->orderBy('id')
                    ->get(['subject_id', 'user_id', 'user_role', 'new_values', 'created_at']);

                if ($events->isEmpty()) {
                    return;
                }

                $actorIds = $events->pluck('user_id')->unique()->all();
                $currentRoles = DB::table('users')
                    ->whereIn('id', $actorIds)
                    ->pluck('role', 'id');
                $backfill = [];

                foreach ($events as $event) {
                    $values = is_array($event->new_values)
                        ? $event->new_values
                        : json_decode((string) $event->new_values, true);

                    if (
                        ! is_array($values)
                        || ! in_array($values['status'] ?? null, ['processing', 'approved', 'rejected'], true)
                    ) {
                        continue;
                    }

                    $eventRole = $event->user_role ?? $currentRoles[$event->user_id] ?? null;

                    if (! in_array($eventRole, ['admin', 'staff'], true)) {
                        continue;
                    }

                    $backfill[(string) $event->subject_id] = [
                        'processed_by' => $event->user_id,
                        'processed_at' => $event->created_at,
                    ];
                }

                foreach ($requests as $request) {
                    $attributes = $backfill[(string) $request->id] ?? null;

                    if ($attributes === null) {
                        continue;
                    }

                    $missingAttributes = [];
                    $query = DB::table('service_requests')->where('id', $request->id);

                    if ($request->processed_by === null) {
                        $missingAttributes['processed_by'] = $attributes['processed_by'];
                        $query->whereNull('processed_by');
                    }

                    if ($request->processed_at === null) {
                        $missingAttributes['processed_at'] = $attributes['processed_at'];
                        $query->whereNull('processed_at');
                    }

                    if ($missingAttributes !== []) {
                        $query->update($missingAttributes);
                    }
                }
            });

        $unattributed = DB::table('service_requests')
            ->whereIn('status', ['processing', 'approved', 'rejected'])
            ->whereNull('processed_by')
            ->count();

        logger()->info('Report staff attribution backfill completed.', [
            'unattributed_requests' => $unattributed,
            'requests_missing_processed_at' => DB::table('service_requests')
                ->whereIn('status', ['processing', 'approved', 'rejected'])
                ->whereNull('processed_at')
                ->count(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
