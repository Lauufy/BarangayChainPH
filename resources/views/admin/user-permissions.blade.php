@extends('layouts.admin')

@section('title', 'Staff Permissions')

@section('content')
    @php
        $selected = old('permissions', $user->permissions ?? []);
        $selectedCount = collect($permissionList)->keys()->intersect($selected)->count();
    @endphp

    <x-feedbacks.toast />
    <div class="space-y-6">
        <x-admin.page-header label="Administration" title="Staff Permissions" :text="'Choose which modules ' . $user->name . ' can use.'">
            <a href="{{ route('admin.users.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                Back to Users
            </a>
        </x-admin.page-header>

        <form action="{{ route('admin.users.permissions.update', $user) }}" method="POST"
            class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            @csrf
            @method('PUT')

            {{-- Staff member --}}
            <div class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#0A1A33] text-sm font-semibold text-white">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                </div>
            </div>

            <div class="space-y-5 p-5">
                <x-feedbacks.form-errors />

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
                        <span data-perm-count class="text-slate-500">{{ $selectedCount }} of
                            {{ count($permissionList) }} selected</span>
                        <span class="h-3 border-l border-slate-200" aria-hidden="true"></span>
                        <button type="button" data-perm-all
                            class="font-semibold text-emerald-700 hover:underline">Select all</button>
                        <button type="button" data-perm-none
                            class="font-semibold text-slate-500 hover:text-slate-800 hover:underline">Clear</button>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($permissionList as $key => $label)
                        <label
                            class="group flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 has-checked:border-emerald-600 has-checked:bg-emerald-50/60 has-checked:text-slate-900">
                            <input type="checkbox" name="permissions[]" value="{{ $key }}" data-perm-box
                                @checked(in_array($key, $selected, true)) class="peer sr-only">

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

            <div class="border-t border-slate-200 bg-slate-50 px-5 py-4">
                <div class="grid grid-cols-2 gap-3 sm:ml-auto sm:w-96">
                    <a href="{{ route('admin.users.index') }}"
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                        Cancel
                    </a>

                    <x-form.button color="#0A1A33" hoverColor="#12294D" type="submit" loading-text="Saving..."
                        class="w-full">
                        Save Permissions
                    </x-form.button>
                </div>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const update = () => {
                const boxes = [...document.querySelectorAll('[data-perm-box]')];
                const count = document.querySelector('[data-perm-count]');
                if (count) count.textContent = `${boxes.filter((box) => box.checked).length} of ${boxes.length} selected`;
            };

            document.addEventListener('change', (event) => {
                if (event.target.matches('[data-perm-box]')) update();
            });

            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-perm-all], [data-perm-none]');
                if (!button) return;

                const checked = button.hasAttribute('data-perm-all');
                document.querySelectorAll('[data-perm-box]').forEach((box) => box.checked = checked);
                update();
            });
        })();
    </script>
@endsection