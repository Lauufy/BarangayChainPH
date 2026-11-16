<?php

namespace App\Exports;

use App\Exports\Contracts\AdminExport;
use App\Models\AuditLog;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AuditLogsExport implements AdminExport
{
    public function filename(): string
    {
        return 'audit-logs';
    }

    public function headings(): array
    {
        return ['Date & Time', 'User', 'Action', 'Record', 'Changes'];
    }

    /**
     * @return array{user_id?: int|string, action?: string, date?: string}
     */
    public function filters(Request $request): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
        ]);
    }

    public function query(Request $request): Builder
    {
        return $this->queryFromFilters($this->filters($request));
    }

    /**
     * @param  array{user_id?: int|string, action?: string, date?: string}  $filters
     */
    public function queryFromFilters(array $filters): Builder
    {
        return AuditLog::query()
            ->with('user')
            ->when($filters['user_id'] ?? null, fn (Builder $query, int|string $userId) => $query->where('user_id', $userId))
            ->when($filters['action'] ?? null, fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($filters['date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', $date))
            ->latest('created_at');
    }

    public function row(Model $model): array
    {
        if (! $model instanceof AuditLog) {
            throw new InvalidArgumentException('Audit log exports require AuditLog models.');
        }

        $oldValues = $model->old_values ?? [];
        $newValues = $model->new_values ?? [];
        $fields = array_unique([...array_keys($oldValues), ...array_keys($newValues)]);
        $changes = [];

        foreach ($fields as $field) {
            $old = array_key_exists($field, $oldValues) ? $this->readableValue($oldValues[$field]) : 'not set';
            $new = array_key_exists($field, $newValues) ? $this->readableValue($newValues[$field]) : 'not set';
            $changes[] = Str::headline($field).': '.$old.' -> '.$new;
        }

        return [
            $model->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A'),
            $model->user?->name ?? 'System',
            $this->readableAction($model->action),
            $this->readableRecord($model),
            Str::limit(implode('; ', $changes) ?: 'No field values recorded', 180),
        ];
    }

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

    private function readableRecord(AuditLog $log): string
    {
        $names = [
            ServiceRequest::class => 'Service request',
            IssuedDocument::class => 'Issued document',
        ];

        return ($names[$log->subject_type] ?? 'Record').' #'.$log->subject_id;
    }

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
