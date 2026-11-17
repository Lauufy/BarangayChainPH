{{-- Create account popup. Opened by the "Add New" button in index.blade.php. --}}
<form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" class="contents">
    @csrf

    <x-.admin.modal id="createUserModal" title="Create an account"
        subtitle="Staff and admin accounts receive a unique employee ID that can be used to sign in."
        size="max-w-3xl" :open="$createFailed" :close-on-backdrop="false">
        <x-slot:icon>
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 19c0-2.2-2.7-4-6-4s-6 1.8-6 4M9 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM19 8v6M22 11h-6" />
            </svg>
        </x-slot:icon>

        <div class="space-y-6 p-6">
            <x-feedbacks.form-errors />

            {{-- Profile Photo --}}
            <fieldset>
                <legend class="text-xs font-bold uppercase tracking-wide text-(--blue-700)">Profile Photo (Optional)
                </legend>

                <div class="mt-3 flex items-center gap-4">
                    <div
                        class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-full border border-slate-200 bg-(--blue-700)/10 text-(--blue-700)">
                        <img id="createUserAvatarPreview" class="hidden size-full object-cover" alt="Photo preview">
                        <svg id="createUserAvatarPlaceholder" class="size-7" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <input type="file" name="avatar" id="createUserAvatar"
                            accept="image/png,image/jpeg,image/webp"
                            class="block w-full text-sm text-slate-600 file:mr-3 file:cursor-pointer file:rounded-none file:border-0 file:bg-(--blue-700) file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-(--blue-600)">
                        <p class="mt-1 text-xs text-slate-500">JPG, PNG or WebP, up to 2 MB.</p>
                        @error('avatar')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </fieldset>

            {{-- Account --}}
            <fieldset class="border-t border-slate-100 pt-5">
                <legend class="text-xs font-bold uppercase tracking-wide text-(--blue-700)">Account</legend>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <x-form.input light :use-old="$createFailed" name="name" label="Full Name" required autofocus
                        autocomplete="name" placeholder="Enter full name" data-validate />

                    <x-form.input light :use-old="$createFailed" name="email" label="Email Address" type="email"
                        required autocomplete="email" placeholder="Enter email address" data-validate />

                    <x-form.input light :use-old="$createFailed" name="role" label="Role" type="select"
                        :options="[
                            'staff' => 'Staff / Employee',
                            'admin' => 'Administrator',
                            'citizen' => 'Citizen',
                        ]" value="{{ $createFailed ? old('role', 'staff') : 'staff' }}" required />

                    <x-form.input light :use-old="$createFailed" name="phone" label="Phone Number" type="tel"
                        autocomplete="tel" placeholder="Enter phone number" />
                </div>
            </fieldset>

            {{-- Employment (staff / admin only) --}}
            <fieldset id="createUserEmployment" class="border-t border-slate-100 pt-5">
                <legend class="text-xs font-bold uppercase tracking-wide text-(--blue-700)">Employment</legend>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <x-form.input light :use-old="$createFailed" name="department" label="Department" required
                        placeholder="Enter department" />

                    <x-form.input light :use-old="$createFailed" name="position" label="Position" required
                        placeholder="Enter job position" />

                    <x-form.input light :use-old="$createFailed" name="office" label="Office (Optional)"
                        placeholder="Enter office" />

                    <x-form.input light :use-old="$createFailed" name="date_hired" label="Date Hired (Optional)"
                        type="date" max="{{ now()->toDateString() }}" />
                </div>
            </fieldset>

            {{-- Security --}}
            <fieldset class="border-t border-slate-100 pt-5">
                <legend class="text-xs font-bold uppercase tracking-wide text-(--blue-700)">Security</legend>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div class="grid">
                        <x-form.input light :use-old="$createFailed" name="password" label="Password" type="password"
                            required autocomplete="new-password" placeholder="Enter password" data-validate />
                        <x-feedbacks.password-requirement />
                    </div>

                    <x-form.input light :use-old="$createFailed" name="password_confirmation"
                        label="Confirm Password" type="password" required autocomplete="new-password"
                        placeholder="Confirm password" data-validate />
                </div>
            </fieldset>
        </div>

        <x-slot:footer>
            <div class="grid grid-cols-2 gap-3 sm:ml-auto sm:w-96">
                <button type="button" data-modal-close
                    class="w-full rounded-none border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                    Cancel
                </button>

                <x-form.button color="#010048" hoverColor="#090088" type="submit" loading-text="Creating..."
                    class="w-full">
                    Create Account
                </x-form.button>
            </div>
        </x-slot:footer>
    </x-modal>
</form>

<script>
    (function() {
        const input = document.getElementById('createUserAvatar');
        const preview = document.getElementById('createUserAvatarPreview');
        const placeholder = document.getElementById('createUserAvatarPlaceholder');
        if (!input) return;

        input.addEventListener('change', function() {
            const file = input.files[0];

            if (!file) {
                preview.classList.add('hidden');
                placeholder.classList.remove('hidden');
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                alert('The photo must be 2 MB or smaller.');
                input.value = '';
                return;
            }

            if (preview.dataset.url) URL.revokeObjectURL(preview.dataset.url);
            preview.dataset.url = URL.createObjectURL(file);
            preview.src = preview.dataset.url;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        });
    })();
</script>

<script>
    (function() {
        const modal = document.getElementById('createUserModal');
        const role = modal.querySelector('[name="role"]');
        const employment = document.getElementById('createUserEmployment');
        if (!role || !employment) return;

        function sync() {
            const isCitizen = role.value === 'citizen';
            employment.classList.toggle('hidden', isCitizen);
            ['department', 'position'].forEach((name) => {
                const input = modal.querySelector(`[name="${name}"]`);
                if (input) input.required = !isCitizen;
            });
        }

        role.addEventListener('change', sync);
        sync();
    })();
</script>