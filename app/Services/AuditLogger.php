<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/** Creates redacted audit events with the current actor and request context. */
class AuditLogger
{
    private static int $backgroundExecutionDepth = 0;

    private static ?string $previousBackgroundMarker = null;

    public static function beginBackgroundExecution(): void
    {
        if (self::$backgroundExecutionDepth === 0) {
            $previous = getenv('AUDIT_BACKGROUND_EXECUTION');
            self::$previousBackgroundMarker = $previous === false ? null : $previous;
            putenv('AUDIT_BACKGROUND_EXECUTION=1');
        }

        self::$backgroundExecutionDepth++;
    }

    public static function endBackgroundExecution(): void
    {
        if (self::$backgroundExecutionDepth === 0) {
            return;
        }

        self::$backgroundExecutionDepth--;

        if (self::$backgroundExecutionDepth === 0) {
            if (self::$previousBackgroundMarker === null) {
                putenv('AUDIT_BACKGROUND_EXECUTION');
            } else {
                putenv('AUDIT_BACKGROUND_EXECUTION='.self::$previousBackgroundMarker);
            }

            self::$previousBackgroundMarker = null;
        }
    }

    public function withinBackgroundExecution(callable $callback): mixed
    {
        self::beginBackgroundExecution();

        try {
            return $callback();
        } finally {
            self::endBackgroundExecution();
        }
    }

    public function log(
        string $action,
        Model $subject,
        array $oldValues = [],
        array $newValues = [],
        ?string $note = null,
        ?Model $actor = null
    ): ?AuditLog {
        if ($note !== null) {
            $newValues['note'] = $note;
        }

        return $this->write($action, $subject, $oldValues, $newValues, $actor);
    }

    public function logStatusChange(
        Model $subject,
        string $oldStatus,
        string $newStatus,
        ?string $note = null,
        ?Model $actor = null
    ): ?AuditLog {
        return $this->log(
            'status_changed',
            $subject,
            ['status' => $oldStatus],
            ['status' => $newStatus],
            $note,
            $actor
        );
    }

    public function logModelEvent(
        Model $subject,
        string $action,
        array $oldValues = [],
        array $newValues = []
    ): ?AuditLog {
        return $this->write($action, $subject, $oldValues, $newValues);
    }

    private function write(
        string $action,
        Model $subject,
        array $oldValues,
        array $newValues,
        ?Model $actor = null
    ): ?AuditLog {
        if ($this->shouldSkipConsoleEvent()) {
            return null;
        }

        $authenticatedActor = Auth::user();
        $actor ??= $authenticatedActor instanceof Model ? $authenticatedActor : null;
        $request = app()->bound('request') ? app('request') : null;

        return AuditLog::create([
            'user_id' => $actor?->getKey(),
            'user_role' => $actor?->getAttribute('role'),
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => (string) $subject->getKey(),
            'old_values' => $this->redactValues($oldValues) ?: null,
            'new_values' => $this->redactValues($newValues) ?: null,
            'ip_address' => $request instanceof Request ? $request->ip() : null,
            'user_agent' => $request instanceof Request ? $request->userAgent() : null,
            'created_at' => now(),
        ]);
    }

    private function shouldSkipConsoleEvent(): bool
    {
        return app()->runningInConsole()
            && ! app()->runningUnitTests()
            && Auth::guest()
            && getenv('AUDIT_BACKGROUND_EXECUTION') !== '1';
    }

    private function redactValues(array $values): array
    {
        $redactedFields = config('audit.redacted_fields', []);

        foreach ($values as $field => $value) {
            $normalizedField = Str::lower((string) $field);
            $isRedacted = false;

            foreach ($redactedFields as $pattern) {
                if (Str::is($pattern, $normalizedField)) {
                    $isRedacted = true;

                    break;
                }
            }

            $values[$field] = $isRedacted ? '[redacted]' : $this->redactValue($value);
        }

        return $values;
    }

    private function redactValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->redactValues($value);
        }

        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->redactValues($decoded);
        }

        if (
            str_contains($value, "\0")
            || preg_match('/(?:https?|s3|gs|supabase|storage):\\/\\/|data:[^,]*;base64,/i', $value) === 1
            || preg_match('/^(?:%PDF-|\\x89PNG|\\xFF\\xD8\\xFF|GIF8|RIFF.{4}WEBP|PK\\x03\\x04)/s', $value) === 1
            || (strlen($value) >= 256
                && preg_match('/^[A-Za-z0-9+\\/=\\r\\n]+$/D', $value) === 1
                && base64_decode($value, true) !== false)
        ) {
            return '[redacted]';
        }

        return $value;
    }
}
