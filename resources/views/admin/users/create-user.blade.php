{{-- Create account popup. Opened by the "Add New" button in index.blade.php. --}}
<form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" class="contents">
    @csrf

    <x-admin.modal id="createUserModal" title="Create an account"
        subtitle="Staff and admin accounts receive a unique employee ID that can be used to sign in." size="max-w-3xl"
        :open="$createFailed" :close-on-backdrop="false">


        <div class="space-y-6 p-6">
            <x-feedbacks.form-errors />

            {{-- Account --}}
            <fieldset class="border-t border-(--gray-200) pt-5 dark:border-white/10">
                <legend class="text-(--blue-700) text-xs font-bold uppercase tracking-wide dark:text-(--celadon-500)">Account
                </legend>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <x-form.input light :use-old="$createFailed" name="name" label="Full Name" required autofocus
                        autocomplete="name" placeholder="Enter full name" data-validate />

                    <x-form.input light :use-old="$createFailed" name="email" label="Email Address" type="email" required
                        autocomplete="email" placeholder="Enter email address" data-validate />

                    <x-form.input light :use-old="$createFailed" name="role" label="Role" type="select" :options="[
                        'staff' => 'Staff / Employee',
                        'admin' => 'Administrator',
                        'citizen' => 'Citizen',
                    ]"
                        value="{{ $createFailed ? old('role', 'staff') : 'staff' }}" required />

                    <x-form.input light :use-old="$createFailed" name="phone" label="Phone Number" type="tel"
                        autocomplete="tel" placeholder="Enter phone number" />
                </div>
            </fieldset>

            {{-- Employment (staff / admin only) --}}
            <fieldset id="createUserEmployment" class="border-t border-(--gray-200) pt-5 dark:border-white/10">
                <legend class="text-(--blue-700) text-xs font-bold uppercase tracking-wide dark:text-(--celadon-500)">
                    Employment</legend>

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
            <fieldset class="border-t border-(--gray-200) pt-5 dark:border-white/10">
                <legend class="text-(--blue-700) text-xs font-bold uppercase tracking-wide dark:text-(--celadon-500)">Security
                </legend>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div class="grid">
                        <x-form.input light :use-old="$createFailed" name="password" label="Password" type="password" required
                            autocomplete="new-password" placeholder="Enter password" data-validate />
                        <x-feedbacks.password-requirement />
                    </div>

                    <x-form.input light :use-old="$createFailed" name="password_confirmation" label="Confirm Password"
                        type="password" required autocomplete="new-password" placeholder="Confirm password"
                        data-validate />
                </div>
            </fieldset>
        </div>

        <x-slot:footer>
            <div class="grid grid-cols-2 gap-3 sm:ml-auto sm:w-96">
                <button type="button" data-modal-close
                    class="w-full rounded-md border border-(--gray-300) bg-(--white) px-4 py-2.5 text-sm font-semibold text-(--gray-700) transition hover:bg-(--gray-100) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-(--blue-500) dark:border-white/20 dark:bg-transparent dark:text-(--gray-200) dark:hover:bg-white/10 dark:focus-visible:ring-(--celadon-500)">
                    Cancel
                </button>

                <x-form.button color="#1F3F7A" hoverColor="#173266" type="submit" loading-text="Creating..."
                    class="w-full">
                    Create Account
                </x-form.button>
            </div>
        </x-slot:footer>
    </x-admin.modal>
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
