# Audit logging

## Enable a model

Import and use the shared trait in an Eloquent model:

```php
use App\Models\Concerns\Auditable;

class ServiceRequest extends Model
{
    use Auditable;
}
```

The observer writes create, delete, and changed-field update events. Status changes use `status_changed`; user role or permission changes use `role_changed`. Login, logout, and explicit status events are recorded through `AuditLogger`. Events include the acting user when available and request IP and user agent for web requests. Queue and scheduled work records a null user. Unauthenticated console model events are skipped.

Bulk query updates and deletes bypass Eloquent events and are not recorded.

## Redaction and privacy

Extend `redacted_fields` in [config/audit.php](../config/audit.php) to add fields that must be stored as `[redacted]`. The logger also redacts URL, base64, and recognizable binary file content. Audit rows are append-only through the `AuditLog` model.

Audit values capture changed fields rather than full model snapshots. They do not include model primary keys, timestamps, or uploaded file contents. Redaction is a safeguard; do not pass unrelated sensitive values to explicit audit events.
