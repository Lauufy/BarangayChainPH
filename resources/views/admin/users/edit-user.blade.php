{{-- Edit account popup. Filled by JS from window.userRecords (set in index.blade.php). --}}
<form id="editUserForm" action="#" method="POST" enctype="multipart/form-data" class="contents">
    @csrf
    @method('PUT')

    <x-admin.modal id="editUserModal" title="Edit account"
        subtitle="Update account information, role, and profile details." size="max-w-3xl" :close-on-backdrop="false">

        <input type="hidden" name="edit_user_id" id="editUser_id" value="{{ old('edit_user_id') }}">

        <div class="space-y-6 p-6">
            <x-feedbacks.form-errors id="editUserErrors" bag="updateUser" />

            {{-- Account --}}
            <fieldset class="border-(--gray-200) border-t pt-5 dark:border-white/10">
                <legend class="text-(--blue-700) dark:text-(--celadon-500) text-xs font-bold uppercase tracking-wide">
                    Account</legend>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <x-form.input light id="editUser_name" name="name" data-validate label="Full Name" required
                        autocomplete="off" placeholder="Enter full name" :use-old="false" :value="$editOld['name'] ?? null" />

                    <x-form.input light id="editUser_email" name="email" data-validate label="Email Address"
                        type="email" required autocomplete="off" placeholder="Enter email address" :use-old="false"
                        :value="$editOld['email'] ?? null" />

                    <x-form.input light id="editUser_role" name="role" label="Role" type="select" required
                        :options="['staff' => 'Staff / Employee', 'admin' => 'Administrator', 'citizen' => 'Citizen']" :use-old="false" :value="$editOld['role'] ?? null">
                        {{-- A disabled select isn't submitted, so locked roles are sent from here. --}}
                        <input type="hidden" name="role" id="editUser_roleHidden" disabled>
                        <p id="editUserRoleNote" class="text-(--gray-500) dark:text-(--gray-300) mt-1 hidden text-xs">
                        </p>
                    </x-form.input>

                    <x-form.input light id="editUser_status" name="status" label="Account Status" type="select"
                        required :options="$statuses" :use-old="false" :value="$editOld['status'] ?? null" />

                    <x-form.input light id="editUser_phone" name="phone" label="Phone Number" type="tel"
                        autocomplete="off" placeholder="Enter phone number" :use-old="false" :value="$editOld['phone'] ?? null" />
                </div>
            </fieldset>

            {{-- Employment (staff / admin only) --}}
            <fieldset id="editUserEmployment" class="border-(--gray-200) border-t pt-5 dark:border-white/10">
                <legend class="text-(--blue-700) dark:text-(--celadon-500) text-xs font-bold uppercase tracking-wide">
                    Employment</legend>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <x-form.input light id="editUser_employment_type" name="employment_type" label="Employment Type"
                        type="select" required :options="$employmentTypes" :use-old="false" :value="$editOld['employment_type'] ?? null" />

                    <x-form.input light id="editUser_department" name="department" label="Department" type="select"
                        required :use-old="false" :value="$editOld['department'] ?? null" :options="[
                            'administration_records' => 'Administration & Records',
                            'barangay_council' => 'Barangay Council',
                            'finance_treasury' => 'Finance & Treasury',
                            'peace_order' => 'Peace & Order',
                            'health_nutrition' => 'Health & Nutrition',
                            'social_welfare' => 'Social Welfare',
                            'disaster_risk_reduction' => 'Disaster Risk Reduction',
                            'environment_infrastructure' => 'Environment & Infrastructure',
                            'others' => 'Others',
                        ]" />

                    <div data-other-for="department" class="hidden">
                        <x-form.input light id="editUser_department_other" name="department_other"
                            label="Specify Department" placeholder="Enter department" :use-old="false"
                            :value="$editOld['department_other'] ?? null" />
                    </div>

                    <x-form.input light id="editUser_position" name="position" label="Position" type="select" required
                        :use-old="false" :value="$editOld['position'] ?? null" :options="[
                            'punong_barangay' => 'Punong Barangay',
                            'barangay_kagawad' => 'Barangay Kagawad',
                            'sk_chairperson' => 'SK Chairperson',
                            'sk_kagawad' => 'SK Kagawad',
                            'barangay_secretary' => 'Barangay Secretary',
                            'barangay_treasurer' => 'Barangay Treasurer',
                            'barangay_tanod' => 'Barangay Tanod',
                            'administrative_assistant' => 'Administrative Assistant',
                            'bhw' => 'Barangay Health Worker (BHW)',
                            'day_care_worker' => 'Day Care Worker',
                            'others' => 'Others',
                        ]" />

                    <div data-other-for="position" class="hidden">
                        <x-form.input light id="editUser_position_other" name="position_other" label="Specify Position"
                            placeholder="Enter position" :use-old="false" :value="$editOld['position_other'] ?? null" />
                    </div>

                    <x-form.input light id="editUser_office" name="office" label="Office (Optional)" type="select"
                        :use-old="false" :value="$editOld['office'] ?? null" :options="[
                            'office_punong_barangay' => 'Office of the Punong Barangay',
                            'secretarys_office' => 'Barangay Secretary\'s Office',
                            'treasurers_office' => 'Barangay Treasurer\'s Office',
                            'administrative_office' => 'Barangay Administrative Office',
                            'council_office' => 'Barangay Council Office',
                            'peace_order_office' => 'Barangay Peace & Order Office',
                            'health_station' => 'Barangay Health Station',
                            'social_welfare_desk' => 'Social Welfare Desk',
                            'disaster_response_office' => 'Disaster Response Office',
                            'environment_sanitation_office' => 'Environment & Sanitation Office',
                            'others' => 'Others',
                        ]" />

                    <div data-other-for="office" class="hidden">
                        <x-form.input light id="editUser_office_other" name="office_other" label="Specify Office"
                            placeholder="Enter office" :use-old="false" :value="$editOld['office_other'] ?? null" />
                    </div>

                    <x-form.input light id="editUser_date_hired" name="date_hired" label="Date Hired (Optional)"
                        type="date" max="{{ now()->toDateString() }}" placeholder="Select date" :use-old="false"
                        :value="$editOld['date_hired'] ?? null" />
                </div>
            </fieldset>

            {{-- Citizen details (citizens with a profile only) --}}
            <fieldset id="editUserCitizen" class="border-(--gray-200) hidden border-t pt-5 dark:border-white/10">
                <legend class="text-(--blue-700) dark:text-(--celadon-500) text-xs font-bold uppercase tracking-wide">
                    Citizen Details</legend>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <x-form.input light id="editUser_civil_status" name="civil_status" label="Civil Status"
                        type="select" required :use-old="false" :value="$editOld['civil_status'] ?? null" :options="[
                            'Single' => 'Single',
                            'Married' => 'Married',
                            'Widowed' => 'Widowed',
                            'Separated' => 'Separated',
                            'Divorced' => 'Divorced',
                        ]" />

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
        </div>

        <x-slot:footer>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <button type="button" id="editUserPermissionsBtn"
                    class="border-(--blue-700)/30 bg-(--white) text-(--blue-700) hover:bg-(--blue-50) focus-visible:ring-(--blue-500) dark:border-(--celadon-500)/40 dark:text-(--celadon-500) dark:hover:bg-(--celadon-500)/10 dark:focus-visible:ring-(--celadon-500) hidden items-center justify-center gap-2 rounded-md border px-4 py-3 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 dark:bg-transparent">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path
                            d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                    Permissions
                </button>

                <div class="grid grid-cols-2 gap-3 sm:ml-auto sm:w-80">
                    <button type="button" data-modal-close
                        class="border-(--gray-300) bg-(--white) text-(--gray-700) hover:bg-(--gray-100) focus-visible:ring-(--blue-500) dark:text-(--gray-200) dark:focus-visible:ring-(--celadon-500) w-full rounded-md border px-4 py-2.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 dark:border-white/20 dark:bg-transparent dark:hover:bg-white/10">
                        Cancel
                    </button>

                    <x-form.button color="#1F3F7A" hoverColor="#173266" type="submit" loading-text="Saving..."
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
        const fields = ['name', 'email', 'role', 'status', 'phone', 'employment_type', 'date_hired',
            'civil_status', 'religion', 'occupation', 'house_number', 'barangay', 'city_municipality',
            'province', 'zip_code'
        ];
        // Dropdowns that also have a "Specify ..." box for "Others"
        const picks = ['department', 'position', 'office'];
        const requiredStaff = ['employment_type', 'department', 'position'];
        const requiredCitizen = ['civil_status', 'barangay', 'city_municipality', 'province', 'zip_code'];

        // Show the "Specify ..." box only when "Others" is picked
        picks.forEach((name) => {
            const select = $('editUser_' + name);
            const box = modal.querySelector(`[data-other-for="${name}"]`);
            const input = $('editUser_' + name + '_other');

            select.addEventListener('change', () => {
                const show = select.value === 'others';
                box.classList.toggle('hidden', !show);
                input.required = show && !$('editUserEmployment').classList.contains('hidden');
                if (!show) input.value = '';
            });
        });

        // Pick the saved option. A value that is not in the list was typed under "Others".
        function fillPick(name, value) {
            const select = $('editUser_' + name);
            const known = !!value && [...select.options].some((opt) => opt.value === value);

            select.value = known ? value : (value ? 'others' : '');
            $('editUser_' + name + '_other').value = known ? '' : (value || '');
        }

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
                picks.forEach((f) => fillPick(f, u[f]));
                fileInput.value = '';
                $('editUserRemove').checked = false;
            }

            // Role: citizens stay citizens, staff/admin switch between each other,
            // and nobody can change their own role.
            const isCitizen = u.role === 'citizen';
            const locked = u.is_self || isCitizen;
            const role = $('editUser_role');
            [...role.options].forEach((opt) => {
                opt.hidden = opt.disabled = opt.value === '' || (isCitizen ? opt.value !== 'citizen' :
                    opt.value === 'citizen');
            });
            role.disabled = locked;
            $('editUser_roleHidden').disabled = !locked;
            $('editUser_roleHidden').value = u.role;
            if (locked) role.value = u.role;

            const note = $('editUserRoleNote');
            note.textContent = u.is_self ? "You can't change your own role." :
                (isCitizen ? '  Citizen accounts keep the citizen role.' : '');
            note.classList.toggle('hidden', !locked);

            // Nobody can suspend or deactivate their own account.
            [...$('editUser_status').options].forEach((opt) => opt.disabled = u.is_self && opt.value !==
                'active');

            // Employment and photo: staff / admin only. Citizen details: citizens with a profile only.
            const showCitizen = isCitizen && u.has_citizen;
            $('editUserEmployment').classList.toggle('hidden', isCitizen);
            $('editUserPhoto').classList.toggle('hidden', isCitizen);
            $('editUserCitizen').classList.toggle('hidden', !showCitizen);
            requiredStaff.forEach((f) => $('editUser_' + f).required = !isCitizen);
            requiredCitizen.forEach((f) => $('editUser_' + f).required = showCitizen);

            // Refresh the "Specify ..." boxes (after the Employment section is shown or hidden)
            picks.forEach((f) => $('editUser_' + f).dispatchEvent(new Event('change')));

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
