-- Apply only after confirming the JWT claims described below.
-- Policies are for requests made as the Supabase "authenticated" role.
-- Laravel's trusted database connection must use a server-side role and never expose its key.
--
-- app_metadata must contain:
--   user_id: the matching integer ID from public.users
--   role: citizen, staff, or admin
--   permissions: an optional JSON array such as ["audit.view"]

create or replace function public.current_app_user_id()
returns bigint
language sql
stable
as $$
    select nullif(auth.jwt() -> 'app_metadata' ->> 'user_id', '')::bigint
$$;

create or replace function public.current_app_role()
returns text
language sql
stable
as $$
    select auth.jwt() -> 'app_metadata' ->> 'role'
$$;

alter table public.users enable row level security;
alter table public.citizen_profiles enable row level security;
alter table public.employee_profiles enable row level security;
alter table public.identity_verifications enable row level security;
alter table public.notifications enable row level security;
alter table public.service_requests enable row level security;
alter table public.tickets enable row level security;
alter table public.ticket_messages enable row level security;
alter table public.issued_documents enable row level security;
alter table public.audit_logs enable row level security;
alter table public.cache enable row level security;
alter table public.cache_locks enable row level security;
alter table public.jobs enable row level security;
alter table public.job_batches enable row level security;
alter table public.failed_jobs enable row level security;
alter table public.password_reset_tokens enable row level security;
alter table public.sessions enable row level security;

create policy "Users read own or staff records" on public.users
    for select to authenticated
    using (id = public.current_app_user_id() or public.current_app_role() in ('admin', 'staff'));
create policy "Staff manage users" on public.users
    for all to authenticated
    using (public.current_app_role() in ('admin', 'staff'))
    with check (public.current_app_role() in ('admin', 'staff'));

create policy "Citizens and staff read citizen profiles" on public.citizen_profiles
    for select to authenticated
    using (user_id = public.current_app_user_id() or public.current_app_role() in ('admin', 'staff'));
create policy "Users manage own citizen profile" on public.citizen_profiles
    for all to authenticated
    using (user_id = public.current_app_user_id() or public.current_app_role() in ('admin', 'staff'))
    with check (user_id = public.current_app_user_id() or public.current_app_role() in ('admin', 'staff'));

create policy "Employees read own or staff profiles" on public.employee_profiles
    for select to authenticated
    using (user_id = public.current_app_user_id() or public.current_app_role() in ('admin', 'staff'));
create policy "Admins manage employee profiles" on public.employee_profiles
    for all to authenticated
    using (public.current_app_role() = 'admin')
    with check (public.current_app_role() = 'admin');

create policy "Citizens and staff read identity verifications" on public.identity_verifications
    for select to authenticated
    using (user_id = public.current_app_user_id() or public.current_app_role() in ('admin', 'staff'));
create policy "Citizens submit identity verifications" on public.identity_verifications
    for insert to authenticated
    with check (user_id = public.current_app_user_id());
create policy "Staff review identity verifications" on public.identity_verifications
    for update to authenticated
    using (public.current_app_role() in ('admin', 'staff'))
    with check (public.current_app_role() in ('admin', 'staff'));

create policy "Users read own notifications" on public.notifications
    for select to authenticated
    using (notifiable_id = public.current_app_user_id() and notifiable_type = 'App\\Models\\User');
create policy "Users update own notifications" on public.notifications
    for update to authenticated
    using (notifiable_id = public.current_app_user_id() and notifiable_type = 'App\\Models\\User')
    with check (notifiable_id = public.current_app_user_id() and notifiable_type = 'App\\Models\\User');

create policy "Citizens and staff read service requests" on public.service_requests
    for select to authenticated
    using (user_id = public.current_app_user_id() or public.current_app_role() in ('admin', 'staff'));
create policy "Citizens submit service requests" on public.service_requests
    for insert to authenticated
    with check (user_id = public.current_app_user_id());
create policy "Staff update service requests" on public.service_requests
    for update to authenticated
    using (public.current_app_role() in ('admin', 'staff'))
    with check (public.current_app_role() in ('admin', 'staff'));

create policy "Participants and staff read tickets" on public.tickets
    for select to authenticated
    using (
        user_id = public.current_app_user_id()
        or public.current_app_role() in ('admin', 'staff')
    );
create policy "Staff issue and update issued documents" on public.issued_documents
    for all to authenticated
    using (
        public.current_app_role() = 'admin'
        or (
            public.current_app_role() = 'staff'
            and coalesce(auth.jwt() -> 'app_metadata' -> 'permissions', '[]'::jsonb) ? 'permits.review'
        )
    )
    with check (
        public.current_app_role() = 'admin'
        or (
            public.current_app_role() = 'staff'
            and coalesce(auth.jwt() -> 'app_metadata' -> 'permissions', '[]'::jsonb) ? 'permits.review'
        )
    );
create policy "Citizens create own tickets" on public.tickets
    for insert to authenticated
    with check (user_id = public.current_app_user_id());
create policy "Staff update tickets" on public.tickets
    for update to authenticated
    using (public.current_app_role() in ('admin', 'staff'))
    with check (public.current_app_role() in ('admin', 'staff'));

create policy "Ticket participants read messages" on public.ticket_messages
    for select to authenticated
    using (
        exists (
            select 1 from public.tickets
            where tickets.id = ticket_messages.ticket_id
              and (tickets.user_id = public.current_app_user_id()
                   or public.current_app_role() in ('admin', 'staff'))
        )
    );
create policy "Ticket participants create messages" on public.ticket_messages
    for insert to authenticated
    with check (
        (
            user_id = public.current_app_user_id()
            and exists (
                select 1 from public.tickets
                where tickets.id = ticket_messages.ticket_id
                  and (tickets.user_id = public.current_app_user_id()
                       or public.current_app_role() in ('admin', 'staff'))
            )
        )
        or (
            user_id is null
            and public.current_app_role() in ('admin', 'staff')
        )
    );

create policy "Owners and staff read issued documents" on public.issued_documents
    for select to authenticated
    using (
        exists (
            select 1 from public.service_requests
            where service_requests.id = issued_documents.service_request_id
              and (service_requests.user_id = public.current_app_user_id()
                   or public.current_app_role() in ('admin', 'staff'))
        )
    );

create policy "Audit viewers read audit logs" on public.audit_logs
    for select to authenticated
    using (
        public.current_app_role() = 'admin'
        or (
            public.current_app_role() = 'staff'
            and coalesce(auth.jwt() -> 'app_metadata' -> 'permissions', '[]'::jsonb) ? 'audit.view'
        )
    );

-- Cache, queue, session, and password-reset tables have no client policies:
-- only the trusted Laravel database role can read or write them.

-- Read access summary:
-- users and profiles: the owner; staff/admin as shown by each policy.
-- identity_verifications, service_requests, tickets, messages, issued_documents:
--   the owning citizen and staff/admin as shown by each policy.
-- notifications: the owning Laravel user.
-- audit_logs: admins and staff whose app_metadata permissions include audit.view.
-- cache, cache_locks, jobs, job_batches, failed_jobs, password_reset_tokens, sessions:
--   trusted Laravel database role only; Supabase client roles have no policies.
