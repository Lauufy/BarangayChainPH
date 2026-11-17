{{-- Edit account popup. Filled by JS from window.userRecords (set in index.blade.php). --}}
<form id="editUserForm" action="#" method="POST" enctype="multipart/form-data" class="contents">
    @csrf
    @method('PUT')

    <x-.admin.modal id="editUserModal" title="Edit account"
        subtitle="Update account information, role, and profile details." size="max-w-3xl"
        :close-on-backdrop="false">
        <x-slot:icon>
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 20h9" />
                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z" />
            </svg>
        </x-slot:icon>

        <input type="hidden" name="edit_user_id" id="editUser_id" value="{{ old('edit_user_id') }}">

        <div class="space-y-6 p-6">
            <x-feedbacks.form-errors id="editUserErrors" bag="updateUser" />

            {{-- Profile Photo (staff / admin only) --}}
            <fieldset id="editUserPhoto">
                <legend class="text-xs font-bold uppercase tracking-wide text-(--blue-700)">Profile Photo</legend>

                <div class="mt-3 flex items-center gap-4">
                    <div
                        class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-full border border-slate-200 bg-(--blue-700)/10 text-lg font-semibold text-(--blue-700)">
                        <img id="editUserAvatarPreview" class="hidden size-full object-cover" alt="Photo preview">
                        <span id="editUserAvatarInitials"></span>
                    </div>

                    <div class="min-w-0 space-y-2">
                        <input type="file" name="avatar" id="editUserAvatar"
                            accept="image/png,image/jpeg,image/webp"
                            class="block w-full text-sm text-slate-600 file:mr-3 file:cursor-pointer file:rounded-none file:border-0 file:bg-(--blue-700) file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-(--blue-600)">
                        <p class="text-xs text-slate-500">JPG, PNG or WebP, up to 2 MB. A new photo replaces the
                            current one.</p>

                        <label id="editUserRemoveWrap" class="hidden items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="remove_avatar" value="1" id="editUserRemove"
                                class="size-4 rounded-none border-slate-300 text-(--blue-700) focus:ring-(--blue-700)">
                            Remove current photo
                        </label>
                    </div>
                </div>
            </fieldset>

            {{-- Account --}}
            <fieldset class="border-t border-slate-100 pt-5">
                <legend class="text-xs font-bold uppercase tracking-wide text-(--blue-700)">Account</legend>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <x-form.input light id="editUser_name" name="name" data-validate label="Full Name" required
                        autocomplete="off" placeholder="Enter full name" :use-old="false"
                        :value="$editOld['name'] ?? null" />

                    <x-form.input light id="editUser_email" name="email" data-validate label="Email Address"
                        type="email" required autocomplete="off" placeholder="Enter email address" :use-old="false"
                        :value="$editOld['email'] ?? null" />

                    <x-form.input light id="editUser_role" name="role" label="Role" type="select" required
                        :options="['staff' => 'Staff / Employee', 'admin' => 'Administrator', 'citizen' => 'Citizen']" :use-old="false" :value="$editOld['role'] ?? null">
                        {{-- A disabled select isn't submitted, so locked roles are sent from here. --}}
                        <input type="hidden" name="role" id="editUser_roleHidden" disabled>
                        <p id="editUserRoleNote" class="mt-1 hidden text-xs text-slate-500"></p>
                    </x-form.input>

                    <x-form.input light id="editUser_phone" name="phone" label="Phone Number" type="tel"
                        autocomplete="off" placeholder="Enter phone number" :use-old="false"
                        :value="$editOld['phone'] ?? null" />
                </div>
            </fieldset>

            {{-- Employment (staff / admin only) --}}
            <fieldset id="editUserEmployment" class="border-t border-slate-100 pt-5">
                <legend class="text-xs font-bold uppercase tracking-wide text-(--blue-700)">Employment</legend>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <x-form.input light id="editUser_department" name="department" label="Department" required
                        placeholder="Enter department" :use-old="false" :value="$editOld['department'] ?? null" />

                    <x-form.input light id="editUser_position" name="position" label="Position" required
                        placeholder="Enter job position" :use-old="false" :value="$editOld['position'] ?? null" />

                    <x-form.input light id="editUser_office" name="office" label="Office (Optional)"
                        placeholder="Enter office" :use-old="false" :value="$editOld['office'] ?? null" />

                    <x-form.input light id="editUser_date_hired" name="date_hired" label="Date Hired (Optional)"
                        type="date" max="{{ now()->toDateString() }}" placeholder="Select date" :use-old="false"
                        :value="$editOld['date_hired'] ?? null" />
                </div>
            </fieldset>

            {{-- Citizen details (citizens with a profile only) --}}
            <fieldset id="editUserCitizen" class="hidden border-t border-slate-100 pt-5">
                <legend class="text-xs font-bold uppercase tracking-wide text-(--blue-700)">Citizen Details</legend>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <x-form.input light id="editUser_civil_status" name="civil_status" label="Civil Status"
                        type="select" required :use-old="false" :value="$editOld['civil_status'] ?? null"
                        :options="['Single' => 'Single', 'Married' => 'Married', 'Widowed' => 'Widowed', 'Separated' => 'Separated', 'Divorced' => 'Divorced']" />

                    <x-form.input light id="editUser_religion" name="religion" label="Religion (Optional)"
                        placeholder="Enter religion" :use-old="false" :value="$editOld['religion'] ?? null" />

                    <x-form.input light id="editUser_occupation" name="occupation" label="Occupation (Optional)"
                        placeholder="Enter occupation" :use-old="false" :value="$editOld['occupation'] ?? null" />

                    <x-form.input light id="editUser_house_number" name="house_number"
                        label="House Number (Optional)" placeholder="Enter house number" :use-old="false"
                        :value="$editOld['house_number'] ?? null" />

                    <x-form.input light id="editUser_barangay" name="barangay" label="Barangay" required
                        placeholder="Enter barangay" :use-old="false" :value="$editOld['barangay'] ?? null" />

                    <x-form.input light id="editUser_city_municipality" name="city_municipality"
                        label="City / Municipality" required placeholder="Enter city or municipality"
                        :use-old="false" :value="$editOld['city_municipality'] ?? null" />

                    <x-form.input light id="editUser_province" name="province" label="Province" required
                        placeholder="Enter province" :use-old="false" :value="$editOld['province'] ?? null" />

                    <x-form.input light id="editUser_zip_code" name="zip_code" label="Zip Code" required
                        placeholder="Enter zip code" :use-old="false" :value="$editOld['zip_code'] ?? null" />
                </div>
            </fieldset>

            {{-- Password --}}
            <fieldset class="border-t border-slate-100 pt-5">
                <legend class="text-xs font-bold uppercase tracking-wide text-(--blue-700)">Change Password</legend>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div class="grid">
                        <x-form.input light id="editUser_password" name="password" data-validate label="New Password"
                            type="password" autocomplete="new-password" placeholder="Enter new password"
                            hint="Leave blank to keep the current password." :use-old="false" />
                        <x-feedbacks.password-requirement input-id="editUser_password" />
                    </div>

                    <x-form.input light id="editUser_password_confirmation" name="password_confirmation"
                        data-validate label="Confirm New Password" type="password" autocomplete="new-password"
                        placeholder="Confirm new password" :use-old="false" />
                </div>
            </fieldset>
        </div>

        <x-slot:footer>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <button type="button" id="editUserPermissionsBtn"
                    class="hidden items-center justify-center gap-2 rounded-none border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                    Permissions
                </button>

                <div class="grid grid-cols-2 gap-3 sm:ml-auto sm:w-80">
                    <button type="button" data-modal-close
                        class="w-full rounded-none border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                        Cancel
                    </button>

                    <x-form.button color="#010048" hoverColor="#090088" type="submit" loading-text="Saving..."
                        class="w-full">
                        Save Changes
                    </x-form.button>
                </div>
            </div>
        </x-slot:footer>
    </x-modal>
</form>

<script>
    (function() {
        const modal = document.getElementById('editUserModal');
        const form = document.getElementById('editUserForm');
        const $ = (id) => document.getElementById(id);

        const preview = $('editUserAvatarPreview');
        const initialsEl = $('editUserAvatarInitials');
        const fileInput = $('editUserAvatar');
        let savedAvatar = null;
        let savedName = '';

        // Fields filled from the user record
        const fields = ['name', 'email', 'role', 'phone', 'department', 'position', 'office', 'date_hired',
            'civil_status', 'religion', 'occupation', 'house_number', 'barangay', 'city_municipality',
            'province', 'zip_code'
        ];
        const requiredStaff = ['department', 'position'];
        const requiredCitizen = ['civil_status', 'barangay', 'city_municipality', 'province', 'zip_code'];

        // Show a photo, or the initials when there is none
        function showAvatar(url, name) {
            if (url) preview.src = url;
            preview.classList.toggle('hidden', !url);
            initialsEl.classList.toggle('hidden', !!url);
            initialsEl.textContent = window.userInitials(name);
        }

        // keepOld = true when reopening after a failed save: keep the values the user typed.
        window.openEditUserModal = function(id, keepOld = false) {
            const u = window.userRecords[id];
            if (!u) return;

            if (typeof closeViewUserModal === 'function') closeViewUserModal();

            form.action = u.update_url;
            $('editUser_id').value = id;
            savedAvatar = u.avatar_url;
            savedName = u.name;

            if (!keepOld) {
                $('editUserErrors')?.remove();
                fields.forEach((f) => $('editUser_' + f).value = u[f] ?? '');
                $('editUser_password').value = '';
                $('editUser_password').dispatchEvent(new Event('input'));
                $('editUser_password_confirmation').value = '';
                fileInput.value = '';
                $('editUserRemove').checked = false;
            }

            // Role: citizens stay citizens, staff/admin switch between each other,
            // and nobody can change their own role.
            const isCitizen = u.role === 'citizen';
            const locked = u.is_self || isCitizen;
            const role = $('editUser_role');
            [...role.options].forEach((opt) => {
                opt.hidden = opt.disabled = opt.value === '' || (isCitizen ? opt.value !== 'citizen' : opt.value === 'citizen');
            });
            role.disabled = locked;
            $('editUser_roleHidden').disabled = !locked;
            $('editUser_roleHidden').value = u.role;
            if (locked) role.value = u.role;

            const note = $('editUserRoleNote');
            note.textContent = u.is_self ? "You can't change your own role." :
                (isCitizen ? 'Citizen accounts keep the citizen role.' : '');
            note.classList.toggle('hidden', !locked);

            // Employment and photo: staff / admin only. Citizen details: citizens with a profile only.
            const showCitizen = isCitizen && u.has_citizen;
            $('editUserEmployment').classList.toggle('hidden', isCitizen);
            $('editUserPhoto').classList.toggle('hidden', isCitizen);
            $('editUserCitizen').classList.toggle('hidden', !showCitizen);
            requiredStaff.forEach((f) => $('editUser_' + f).required = !isCitizen);
            requiredCitizen.forEach((f) => $('editUser_' + f).required = showCitizen);

            showAvatar(savedAvatar, savedName);
            $('editUserRemoveWrap').classList.toggle('hidden', !u.avatar_url);
            $('editUserRemoveWrap').classList.toggle('flex', !!u.avatar_url);

            // Permissions button: staff only, and nobody manages their own.
            const showPerms = u.is_staff && !u.is_self;
            $('editUserPermissionsBtn').classList.toggle('hidden', !showPerms);
            $('editUserPermissionsBtn').classList.toggle('inline-flex', showPerms);

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        window.closeEditUserModal = function() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        fileInput.addEventListener('change', function() {
            const file = fileInput.files[0];

            if (!file) return showAvatar(savedAvatar, savedName);

            if (file.size > 2 * 1024 * 1024) {
                alert('The photo must be 2 MB or smaller.');
                fileInput.value = '';
                return;
            }

            if (preview.dataset.url) URL.revokeObjectURL(preview.dataset.url);
            preview.dataset.url = URL.createObjectURL(file);
            showAvatar(preview.dataset.url, savedName);
            $('editUserRemove').checked = false;
        });

        $('editUserPermissionsBtn').addEventListener('click', function() {
            const id = $('editUser_id').value;
            closeEditUserModal();
            openPermissionsModal(id);
        });

        @if ($reopenId)
            openEditUserModal({{ $reopenId }}, true);
        @endif
    })();
</script>