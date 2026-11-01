{{-- Staff permissions popup. Filled by JS from window.userRecords (set in index.blade.php). --}}
@php
    $permissionList = config('permissions.list');

    // Which staff member to reopen after a failed save (errors live in the "updatePermissions" bag).
    $reopenId = $errors->updatePermissions->any() ? (int) old('permissions_user_id') : null;
    $oldPermissions = (array) old('permissions', []);
@endphp

<div id="permissionsModal"
    class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
    onclick="if (event.target === this) closePermissionsModal()">
    <section
        class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-slate-200 bg-white text-slate-900 shadow-2xl">

        {{-- Header --}}
        <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white p-5">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#17325c] text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path
                            d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-lg font-semibold leading-tight text-slate-900">Staff permissions</h2>
                    <p class="mt-1 text-sm text-slate-500">Choose which modules this staff member can use.</p>
                </div>
            </div>

            <button type="button" onclick="closePermissionsModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                aria-label="Close modal">&times;</button>
        </div>

        <form id="permissionsForm" action="#" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="permissions_user_id" id="permissionsUserId"
                value="{{ old('permissions_user_id') }}">

            {{-- Staff member --}}
            <div class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
                <div
                    class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-full bg-[#17325c]/10 text-sm font-semibold text-[#17325c]">
                    <img id="permissionsAvatar" class="hidden size-full object-cover" alt="">
                    <span id="permissionsInitials"></span>
                </div>
                <div class="min-w-0">
                    <p id="permissionsName" class="truncate text-sm font-semibold text-slate-900"></p>
                    <p id="permissionsEmail" class="truncate text-xs text-slate-500"></p>
                </div>
            </div>

            <div class="space-y-5 p-5">
                @if ($errors->updatePermissions->any())
                    <div id="permissionsErrors"
                        class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                        <ul class="list-disc space-y-0.5 pl-5">
                            @foreach ($errors->updatePermissions->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Always-available note --}}
                <div class="flex gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none"
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
                            class="font-semibold text-emerald-700 hover:underline">Select all</button>
                        <button type="button" id="permissionsNone"
                            class="font-semibold text-slate-500 hover:text-slate-800 hover:underline">Clear</button>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($permissionList as $key => $label)
                        <label
                            class="group flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 has-checked:border-emerald-600 has-checked:bg-emerald-50/60 has-checked:text-slate-900">
                            <input type="checkbox" name="permissions[]" value="{{ $key }}" data-pm-box
                                @checked(in_array($key, $oldPermissions, true)) class="peer sr-only">

                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded border border-slate-300 bg-white text-transparent transition peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 peer-focus-visible:ring-offset-2">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                                    aria-hidden="true">
                                    <path d="m5 12 5 5L20 7" />
                                </svg>
                            </span>

                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Footer --}}
            <div class="sticky bottom-0 border-t border-slate-200 bg-slate-50 p-4">
                <div class="grid grid-cols-2 gap-3 sm:ml-auto sm:w-96">
                    <button type="button" onclick="closePermissionsModal()"
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                        Cancel
                    </button>

                    <x-form.button color="#010048" hoverColor="#090088" type="submit" loading-text="Saving..."
                        class="w-full">
                        Save Permissions
                    </x-form.button>
                </div>
            </div>
        </form>
    </section>
</div>

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

        @if ($reopenId)
            openPermissionsModal({{ $reopenId }}, true);
        @endif
    })();
</script>