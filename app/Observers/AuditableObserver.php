<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditableObserver
{
    /** Record the initial values when an auditable record is created. */
    public function created(Model $model): void
    {
        $this->write($model, 'created', [], $this->values($model->getAttributes()));
    }

    /** Record only changed values so the audit entry stays readable. */
    public function updated(Model $model): void
    {
        $changes = $this->values($model->getChanges());
        $oldValues = array_intersect_key($this->values($model->getRawOriginal()), $changes);

        if ($changes !== []) {
            $this->write($model, 'updated', $oldValues, $changes);
        }
    }

    /** Record the last persisted values before a record is removed. */
    public function deleted(Model $model): void
    {
        $this->write($model, 'deleted', $this->values($model->getRawOriginal()), []);
    }

    /** Remove timestamps and internal Eloquent fields from recorded data. */
    private function values(array $values): array
    {
        unset($values['created_at'], $values['updated_at']);

        return $values;
    }

    /** Save one model change with its authenticated actor. */
    private function write(Model $model, string $action, array $oldValues, array $newValues): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $model->getTable().'.'.$action,
            'subject_type' => $model::class,
            'subject_id' => (string) $model->getKey(),
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
            'created_at' => now(),
        ]);
    }
}
