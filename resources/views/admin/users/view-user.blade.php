{{-- Read-only account popup. Filled by JS from window.userRecords (set in index.blade.php). --}}
<x-admin.modal id="viewUserModal" title="Account details" subtitle="Account information, role, and profile details."
    size="max-w-xl">

    {{-- Identity --}}
    <div class="flex items-center gap-4 p-6">
        <div
            class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-full border border-(--celadon-600)/30 bg-(--celadon-500)/10 text-xl font-semibold text-(--blue-700) dark:border-(--celadon-500)/30 dark:text-(--celadon-500)">
            <img id="viewUserAvatar" class="hidden size-full object-cover" alt="">
            <span id="viewUserInitials"></span>
        </div>

        <div class="min-w-0">
            <h3 id="viewUserName" class="truncate text-lg font-semibold text-(--gray-900) dark:text-(--off-white)"></h3>
            <p id="viewUserEmail" class="truncate text-sm text-(--gray-500) dark:text-(--gray-300)"></p>
            <x-tables.badge id="viewUserRole" variant="neutral" class="mt-2"></x-tables.badge>
        </div>
    </div>

    {{-- Show account fields for staff, citizens, or everyone according to their group. --}}
    <dl class="grid gap-x-6 gap-y-4 border-t border-(--gray-200) p-6 sm:grid-cols-2 dark:border-white/10">
        @foreach ($detailFields as $field => $info)
            <div>
                <dt class="text-(--blue-700) text-xs font-bold uppercase tracking-wide dark:text-(--celadon-500)">{{ $info[0] }}</dt>
                <dd class="mt-1 text-sm text-(--gray-900) dark:text-(--gray-200)" data-field="{{ $field }}"
                    data-group="{{ $info[1] }}"></dd>
            </div>
        @endforeach
    </dl>

    {{-- Permissions summary (staff only) --}}
    <div id="viewUserPermissions" class="hidden border-t border-(--gray-200) px-6 py-4 dark:border-white/10">
        <p class="text-(--blue-700) text-xs font-bold uppercase tracking-wide dark:text-(--celadon-500)">Permissions</p>
        <p class="mt-1 text-sm text-(--gray-700) dark:text-(--gray-200)"><span id="viewUserPermissionCount">0</span> permission(s) granted</p>
    </div>

    <x-slot:footer>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <button type="button" id="viewUserPermissionsBtn"
                class="hidden items-center justify-center gap-2 rounded-md border border-(--blue-700)/30 bg-(--white) px-4 py-3 text-sm font-semibold text-(--blue-700) transition hover:bg-(--blue-50) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-(--blue-500) dark:border-(--celadon-500)/40 dark:bg-transparent dark:text-(--celadon-500) dark:hover:bg-(--celadon-500)/10 dark:focus-visible:ring-(--celadon-500)">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path
                        d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                    <path d="m9 12 2 2 4-4" />
                </svg>
                Permissions
            </button>

            <div class="grid grid-cols-2 gap-3 sm:ml-auto sm:w-80">
                <button type="button" data-modal-close
                    class="w-full rounded-md border border-(--gray-300) bg-(--white) px-4 py-2.5 text-sm font-semibold text-(--gray-700) transition hover:bg-(--gray-100) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-(--blue-500) dark:border-white/20 dark:bg-transparent dark:text-(--gray-200) dark:hover:bg-white/10 dark:focus-visible:ring-(--celadon-500)">
                    Cancel
                </button>
                <x-form.button type="button" id="viewUserEditBtn" color="#1F3F7A" hoverColor="#173266" class="w-full">
                    Edit
                </x-form.button>
            </div>
        </div>
    </x-slot:footer>
    </x-modal>

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
