{{-- Staff permissions popup. Filled by JS from window.userRecords (set in index.blade.php). --}}
<form id="permissionsForm" action="#" method="POST" class="contents">
    @csrf
    @method('PUT')

    <x-.admin.modal id="permissionsModal" title="Staff permissions"
        subtitle="Choose which modules this staff member can use." size="max-w-2xl" :close-on-backdrop="false">
        <x-slot:icon>
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                <path d="m9 12 2 2 4-4" />
            </svg>
        </x-slot:icon>

        <input type="hidden" name="permissions_user_id" id="permissionsUserId" value="{{ $permissionsUserId }}">

        {{-- Staff member --}}
        <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-50 px-6 py-4">
            <div
                class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-full bg-(--blue-700)/10 text-sm font-semibold text-(--blue-700)">
                <img id="permissionsAvatar" class="hidden size-full object-cover" alt="">
                <span id="permissionsInitials"></span>
            </div>
            <div class="min-w-0">
                <p id="permissionsName" class="truncate text-sm font-semibold text-slate-900"></p>
                <p id="permissionsEmail" class="truncate text-xs text-slate-500"></p>
            </div>
        </div>

        <div class="space-y-5 p-6">
            <x-feedbacks.form-errors id="permissionsErrors" bag="updatePermissions" />

            {{-- Always-available note --}}
            <div class="flex gap-3 border-l-4 border-(--celadon-300) bg-(--celadon-50) px-4 py-3">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-(--celadon-700)" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                    aria-hidden="true">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 11v5M12 8h.01" />
                </svg>
                <p class="text-sm leading-relaxed text-slate-600">
                    Dashboard, My Profile and Notifications are always available. Users &amp; Roles is for
                    administrators only.
                </p>
            </div>

            {{-- Module list --}}
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-semibold text-slate-900">Modules</p>

                <div class="flex items-center gap-3 text-xs">
                    <span id="permissionsCount" class="text-slate-500"></span>
                    <span class="h-3 border-l border-slate-200" aria-hidden="true"></span>
                    <button type="button" id="permissionsAll"
                        class="font-semibold text-(--blue-700) hover:underline">Select all</button>
                    <button type="button" id="permissionsNone"
                        class="font-semibold text-slate-500 hover:text-slate-800 hover:underline">Clear</button>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($permissionList as $key => $label)
                    <label
                        class="group flex cursor-pointer items-center gap-3 rounded-none border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 has-checked:border-(--celadon-600) has-checked:bg-(--celadon-50) has-checked:text-slate-900">
                        <input type="checkbox" name="permissions[]" value="{{ $key }}" data-pm-box
                            @checked(in_array($key, $oldPermissions, true)) class="peer sr-only">

                        <span
                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-none border border-slate-300 bg-white text-transparent transition peer-checked:border-(--celadon-600) peer-checked:bg-(--celadon-600) peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-(--celadon-600) peer-focus-visible:ring-offset-2">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m5 12 5 5L20 7" />
                            </svg>
                        </span>

                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <x-slot:footer>
            <div class="grid grid-cols-2 gap-3 sm:ml-auto sm:w-96">
                <button type="button" data-modal-close
                    class="w-full rounded-none border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                    Cancel
                </button>

                <x-form.button color="#010048" hoverColor="#090088" type="submit" loading-text="Saving..."
                    class="w-full">
                    Save Permissions
                </x-form.button>
            </div>
        </x-slot:footer>
    </x-modal>
</form>

<script>
    (function() {
        const modal = document.getElementById('permissionsModal');
        const form = document.getElementById('permissionsForm');
        const $ = (id) => document.getElementById(id);
        const boxes = () => [...form.querySelectorAll('[data-pm-box]')];

        function updateCount() {
            const all = boxes();
            $('permissionsCount').textContent = `${all.filter((b) => b.checked).length} of ${all.length} selected`;
        }

        // keepOld = true when reopening after a failed save: keep the boxes the user ticked.
        window.openPermissionsModal = function(id, keepOld = false) {
            const u = window.userRecords[id];
            if (!u) return;

            if (typeof closeViewUserModal === 'function') closeViewUserModal();

            form.action = u.permissions_url;
            $('permissionsUserId').value = id;

            const img = $('permissionsAvatar');
            const initials = $('permissionsInitials');
            if (u.avatar_url) {
                img.src = u.avatar_url;
                img.alt = u.name;
                img.classList.remove('hidden');
                initials.classList.add('hidden');
            } else {
                img.classList.add('hidden');
                initials.classList.remove('hidden');
                initials.textContent = window.userInitials(u.name);
            }
            $('permissionsName').textContent = u.name;
            $('permissionsEmail').textContent = u.email;

            if (!keepOld) {
                $('permissionsErrors')?.remove();
                const granted = u.permissions || [];
                boxes().forEach((b) => b.checked = granted.includes(b.value));
            }

            updateCount();
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        window.closePermissionsModal = function() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        form.addEventListener('change', (e) => {
            if (e.target.matches('[data-pm-box]')) updateCount();
        });
        $('permissionsAll').addEventListener('click', () => {
            boxes().forEach((b) => b.checked = true);
            updateCount();
        });
        $('permissionsNone').addEventListener('click', () => {
            boxes().forEach((b) => b.checked = false);
            updateCount();
        });

        @if ($permissionsReopenId)
            openPermissionsModal({{ $permissionsReopenId }}, true);
        @endif
    })();
</script>