<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /** Show filtered audit records so authorized staff can review changes. */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
        ]);
        $logs = $this->filteredLogs($filters);
        $logs->setCollection($this->formatLogs($logs->getCollection()));

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'userFilter' => $filters['user_id'] ?? '',
            'actionFilter' => $filters['action'] ?? '',
            'dateFilter' => $filters['date'] ?? '',
            'isEmpty' => $logs->isEmpty(),
        ]);
    }

    /**
     * Apply the three GET filters and keep them when moving between pages.
     *
     * @param  array{user_id?: int|string, action?: string, date?: string}  $filters
     */
    private function filteredLogs(array $filters): LengthAwarePaginator
    {
        $query = AuditLog::query()->with('user');
        $query->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId));
        $query->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action));
        $query->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', $date));

        return $query->latest('created_at')->paginate(20)->withQueryString();
    }

    /**
     * Prepare plain display values so the Blade page needs no formatting code.
     *
     * @return Collection<int, array{date: string, user: string, action: string, record: string, changes: string}>
     */
    private function formatLogs(Collection $logs): Collection
    {
        return $logs->map(fn (AuditLog $log): array => [
            'date' => $log->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A'),
            'user' => $log->user?->name ?? 'System',
            'action' => $this->readableAction($log->action),
            'record' => $this->readableRecord($log),
            'changes' => $this->readableChanges($log),
        ]);
    }

    /** Turn stored action keys into names staff can understand. */
    private function readableAction(string $action): string
    {
        $names = [
            'service_requests.created' => 'Service request created',
            'service_requests.updated' => 'Service request updated',
            'service_requests.deleted' => 'Service request deleted',
            'issued_documents.created' => 'Issued document created',
            'issued_documents.updated' => 'Issued document updated',
            'issued_documents.deleted' => 'Issued document deleted',
        ];

        return $names[$action] ?? Str::headline(str_replace('.', ' ', $action));
    }

    /** Show the type and ID of the record that changed. */
    private function readableRecord(AuditLog $log): string
    {
        $names = [
            'App\\Models\\ServiceRequest' => 'Service request',
            'App\\Models\\IssuedDocument' => 'Issued document',
        ];

        return ($names[$log->subject_type] ?? 'Record').' #'.$log->subject_id;
    }

    /** Summarize changed fields in one short, readable sentence. */
    private function readableChanges(AuditLog $log): string
    {
        $oldValues = $log->old_values ?? [];
        $newValues = $log->new_values ?? [];
        $fields = array_unique([...array_keys($oldValues), ...array_keys($newValues)]);
        $changes = [];

        foreach ($fields as $field) {
            $old = array_key_exists($field, $oldValues) ? $this->readableValue($oldValues[$field]) : 'not set';
            $new = array_key_exists($field, $newValues) ? $this->readableValue($newValues[$field]) : 'not set';
            $changes[] = Str::headline($field).': '.$old.' -> '.$new;
        }

        return Str::limit(implode('; ', $changes) ?: 'No field values recorded', 180);
    }

    /** Keep each old or new value short enough to scan in the table. */
    private function readableValue(mixed $value): string
    {
        if (is_array($value)) {
            return Str::limit((string) json_encode($value, JSON_UNESCAPED_SLASHES), 40);
        }

        return match ($value) {
            null => 'empty',
            true => 'yes',
            false => 'no',
            default => Str::limit((string) $value, 40),
        };
    }
}
