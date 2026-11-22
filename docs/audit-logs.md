# Audit logs

The audit log records when service requests and issued documents are created, changed, or deleted.
The `audit_logs` table is created by `2026_11_05_113241_create_audit_logs_table.php`.
The `AuditLog` model represents each saved audit record.
The `AuditableObserver` watches model changes and stores the old and new values.
`AppServiceProvider` registers that observer for service requests and issued documents only.
Each log stores the acting user, action, record type, record ID, changed values, and time.
`AuditLogController` validates filters, reads matching records, and prepares readable table text.
The page can filter by user ID, action key, or date, and keeps filters while paging.
The page reuses the existing `<x-admin.table>` component.
Example: changing a request status from `pending` to `processing` records both values.
