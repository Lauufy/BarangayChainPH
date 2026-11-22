<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/** Records model lifecycle changes through the shared audit logger. */
class AuditableObserver
{
    public function created(Model $model): void
    {
        app(AuditLogger::class)->logModelEvent(
            $model,
            $model->getTable().'.created',
            [],
            $this->values($model->getAttributes(), $model)
        );
    }

    public function updated(Model $model): void
    {
        $changes = $this->values($model->getChanges(), $model);

        if ($changes === []) {
            return;
        }

        $oldValues = array_intersect_key($this->values($model->getRawOriginal(), $model), $changes);
        $action = match (true) {
            $model instanceof User && array_intersect(['role', 'permissions'], array_keys($changes)) !== [] => 'role_changed',
            array_key_exists('status', $changes) => 'status_changed',
            default => $model->getTable().'.updated',
        };

        app(AuditLogger::class)->logModelEvent($model, $action, $oldValues, $changes);
    }

    public function deleted(Model $model): void
    {
        app(AuditLogger::class)->logModelEvent(
            $model,
            $model->getTable().'.deleted',
            $this->values($model->getRawOriginal(), $model),
            []
        );
    }

    private function values(array $values, Model $model): array
    {
        unset($values[$model->getKeyName()], $values['created_at'], $values['updated_at']);

        return $values;
    }
}
