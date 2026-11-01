{{-- Read-only account popup. Filled by JS from window.userRecords (set in index.blade.php). --}}
<div id="viewUserModal"
    class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
    onclick="if (event.target === this) closeViewUserModal()">
    <section
        class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-xl border border-slate-200 bg-white text-slate-900 shadow-2xl">

        {{-- Header --}}
        <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white p-5">
            <div>
                <h2 class="text-lg font-semibold leading-tight text-slate-900">Account details</h2>
                <p class="mt-1 text-sm text-slate-500">Account information, role, and profile details.</p>
            </div>

            <button type="button" onclick="closeViewUserModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                aria-label="Close modal">&times;</button>
        </div>

        {{-- Identity --}}
        <div class="flex items-center gap-4 p-5">
            <div
                class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-full border border-slate-200 bg-[#17325c]/10 text-xl font-semibold text-[#17325c]">
                <img id="viewUserAvatar" class="hidden size-full object-cover" alt="">
                <span id="viewUserInitials"></span>
            </div>

            <div class="min-w-0">
                <h3 id="viewUserName" class="truncate text-lg font-semibold text-slate-900"></h3>
                <p id="viewUserEmail" class="truncate text-sm text-slate-500"></p>
                <span id="viewUserRole"
                    class="mt-2 inline-block rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold capitalize text-slate-700"></span>
            </div>
        </div>

        {{-- Details: field => [label, who sees it (staff = staff/admin, citizen, null = everyone)] --}}
        <dl class="grid gap-x-6 gap-y-4 border-t border-slate-100 p-5 sm:grid-cols-2">
            @foreach ([
                'employee_id' => ['Employee ID', 'staff'],
                'department' => ['Department', 'staff'],
                'position' => ['Position', 'staff'],
                'office' => ['Office', 'staff'],
                'phone' => ['Phone', null],
                'date_hired_label' => ['Date Hired', 'staff'],
                'gender' => ['Gender', 'citizen'],
                'birth_label' => ['Date of Birth', 'citizen'],
                'civil_status' => ['Civil Status', 'citizen'],
                'nationality' => ['Nationality', 'citizen'],
                'place_of_birth' => ['Place of Birth', 'citizen'],
                'religion' => ['Religion', 'citizen'],
                'occupation' => ['Occupation', 'citizen'],
                'address' => ['Address', 'citizen'],
                'created' => ['Account Created', null],
                'updated' => ['Last Updated', null],
            ] as $field => $info)
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-[#17325c]">{{ $info[0] }}</dt>
                    <dd class="mt-1 text-sm text-slate-900" data-field="{{ $field }}" data-group="{{ $info[1] }}"></dd>
                </div>
            @endforeach
        </dl>

        {{-- Permissions summary (staff only) --}}
        <div id="viewUserPermissions" class="hidden border-t border-slate-200 bg-slate-50 px-5 py-4">
            <p class="text-xs font-bold uppercase tracking-wide text-[#17325c]">Permissions</p>
            <p class="mt-1 text-sm text-slate-700"><span id="viewUserPermissionCount">0</span> permission(s) granted</p>
        </div>

        {{-- Footer --}}
        <div class="sticky bottom-0 border-t border-slate-200 bg-slate-50 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <button type="button" id="viewUserPermissionsBtn"
                    class="hidden items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                    Permissions
                </button>

                <div class="grid grid-cols-2 gap-3 sm:ml-auto sm:w-80">
                    <button type="button" onclick="closeViewUserModal()"
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                        Close
                    </button>
                    <button type="button" id="viewUserEditBtn"
                        class="w-full rounded-lg bg-[#010048] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#090088]">
                        Edit
                    </button>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    (function() {
        const modal = document.getElementById('viewUserModal');
        const $ = (id) => document.getElementById(id);
        let currentId = null;

        window.openViewUserModal = function(id) {
            const u = window.userRecords[id];
            if (!u) return;
            currentId = id;

            // Photo, or initials when there is no photo
            const img = $('viewUserAvatar');
            if (u.avatar_url) img.src = u.avatar_url;
            img.classList.toggle('hidden', !u.avatar_url);
            $('viewUserInitials').classList.toggle('hidden', !!u.avatar_url);
            $('viewUserInitials').textContent = window.userInitials(u.name);

            $('viewUserName').textContent = u.name;
            $('viewUserEmail').textContent = u.email;
            $('viewUserRole').textContent = u.role;

            // Staff fields hide for citizens, citizen fields hide for staff / admin
            const isCitizen = u.role === 'citizen';
            modal.querySelectorAll('[data-field]').forEach((el) => {
                const group = el.dataset.group;
                el.textContent = u[el.dataset.field] || '—';
                el.parentElement.classList.toggle('hidden',
                    (group === 'staff' && isCitizen) || (group === 'citizen' && !isCitizen));
            });

            $('viewUserPermissions').classList.toggle('hidden', !u.is_staff);
            $('viewUserPermissionCount').textContent = u.permission_count;

            // Permissions button: staff only, and nobody manages their own
            const showPerms = u.is_staff && !u.is_self;
            $('viewUserPermissionsBtn').classList.toggle('hidden', !showPerms);
            $('viewUserPermissionsBtn').classList.toggle('inline-flex', showPerms);

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        window.closeViewUserModal = function() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        $('viewUserPermissionsBtn').addEventListener('click', function() {
            closeViewUserModal();
            openPermissionsModal(currentId);
        });

        $('viewUserEditBtn').addEventListener('click', function() {
            closeViewUserModal();
            openEditUserModal(currentId);
        });
    })();
</script>