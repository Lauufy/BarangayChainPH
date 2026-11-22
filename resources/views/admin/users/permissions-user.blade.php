{{-- Staff permissions popup. Filled by JS from window.userRecords (set in index.blade.php). --}}
<form id="permissionsForm" action="#" method="POST" class="contents">
    @csrf
    @method('PUT')

    <x-admin.modal id="permissionsModal" title="Staff permissions"
        subtitle="Choose which modules this staff member can use." size="max-w-2xl" :close-on-backdrop="false">

        <input type="hidden" name="permissions_user_id" id="permissionsUserId" value="{{ $permissionsUserId }}">

        {{-- Staff member --}}
        <div class="flex items-center gap-3 border-b border-(--gray-200) bg-(--gray-50) px-6 py-4 dark:border-white/10 dark:bg-white/5">
            <div
                class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-full border border-(--celadon-600)/30 bg-(--celadon-500)/10 text-sm font-semibold text-(--blue-700) dark:border-(--celadon-500)/30 dark:text-(--celadon-500)">
                <img id="permissionsAvatar" class="hidden size-full object-cover" alt="">
                <span id="permissionsInitials"></span>
            </div>
            <div class="min-w-0">
                <p id="permissionsName" class="truncate text-sm font-semibold text-(--gray-900) dark:text-(--off-white)"></p>
                <p id="permissionsEmail" class="truncate text-xs text-(--gray-500) dark:text-(--gray-300)"></p>
            </div>
        </div>

        <div class="space-y-5 p-6">
            <x-feedbacks.form-errors id="permissionsErrors" bag="updatePermissions" />

            {{-- Always-available note --}}
            <div class="flex gap-3 border-l-4 border-(--celadon-600) bg-(--celadon-500)/10 px-4 py-3 dark:border-(--celadon-500)">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-(--celadon-600) dark:text-(--celadon-500)" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                    aria-hidden="true">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 11v5M12 8h.01" />
                </svg>
                <p class="text-sm leading-relaxed text-(--gray-700) dark:text-(--gray-200)">
                    Dashboard, My Profile and Notifications are always available. Users &amp; Roles is for
                    administrators only.
                </p>
            </div>

            {{-- Module list --}}
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-semibold text-(--gray-900) dark:text-(--off-white)">Modules</p>

                <div class="flex items-center gap-3 text-xs">
                    <span id="permissionsCount" class="text-(--gray-500) dark:text-(--gray-300)"></span>
                    <span class="h-3 border-l border-(--gray-200) dark:border-white/15" aria-hidden="true"></span>
                    <button type="button" id="permissionsAll"
                        class="font-semibold text-(--blue-700) hover:underline dark:text-(--celadon-500)">Select all</button>
                    <button type="button" id="permissionsNone"
                        class="font-semibold text-(--gray-500) hover:text-(--gray-800) hover:underline dark:text-(--gray-300) dark:hover:text-(--off-white)">Clear</button>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($permissionList as $key => $label)
                    <label
                        class="group flex cursor-pointer items-center gap-3 rounded-md border border-(--gray-200) bg-(--white) px-4 py-3 text-sm font-medium text-(--gray-700) transition hover:border-(--gray-300) has-checked:border-(--celadon-600) has-checked:bg-(--celadon-500)/10 has-checked:text-(--gray-900) dark:border-white/15 dark:bg-white/5 dark:text-(--gray-200) dark:hover:border-white/30 dark:has-checked:border-(--celadon-500) dark:has-checked:bg-(--celadon-500)/10 dark:has-checked:text-(--off-white)">
                        <input type="checkbox" name="permissions[]" value="{{ $key }}" data-pm-box
                            @checked(in_array($key, $oldPermissions, true)) class="peer sr-only">

                        <span
                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded border border-(--gray-300) bg-(--white) text-transparent transition peer-checked:border-(--celadon-600) peer-checked:bg-(--celadon-600) peer-focus-visible:ring-2 peer-focus-visible:ring-(--celadon-600) peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-white peer-checked:text-white dark:border-white/25 dark:bg-(--blue-850) dark:peer-checked:border-(--celadon-500) dark:peer-checked:bg-(--celadon-500) dark:peer-focus-visible:ring-(--celadon-500) dark:peer-focus-visible:ring-offset-(--blue-850) dark:peer-checked:text-(--blue-850)">
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
                    class="w-full rounded-md border border-(--gray-300) bg-(--white) px-4 py-2.5 text-sm font-semibold text-(--gray-700) transition hover:bg-(--gray-100) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-(--blue-500) dark:border-white/20 dark:bg-transparent dark:text-(--gray-200) dark:hover:bg-white/10 dark:focus-visible:ring-(--celadon-500)">
                    Cancel
                </button>

                <x-form.button color="#1F3F7A" hoverColor="#173266" type="submit" loading-text="Saving..."
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
