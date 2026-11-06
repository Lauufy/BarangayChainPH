# Row-level security

- Every migration that creates a table must call `Rls::enable('table_name')` for that table.
- Do not expose a table through the Supabase API without a documented reason.
- Run `php artisan security:check-rls` before each deployment and resolve every table reported with RLS disabled.
