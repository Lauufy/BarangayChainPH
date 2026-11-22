{{-- Create account popup. Opened by the "Add New" button in index.blade.php. --}}
<form action="{{ route('admin.users.store') }}" method="POST" class="contents">
    @csrf

    <x-admin.modal id="createUserModal" title="Create an account"
        subtitle="Staff and admin accounts receive a unique employee ID that can be used to sign in." size="max-w-3xl"
        :open="$createFailed" :close-on-backdrop="false">


        <div class="space-y-6 p-6">
            <x-feedbacks.form-errors />

            {{-- Account --}}
            <fieldset class="border-(--gray-200) border-t pt-5 dark:border-white/10">
                <legend class="text-(--blue-700) dark:text-(--celadon-500) text-xs font-bold uppercase tracking-wide">
                    Account
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
            <fieldset id="createUserEmployment" class="border-(--gray-200) border-t pt-5 dark:border-white/10">
                <legend class="text-(--blue-700) dark:text-(--celadon-500) text-xs font-bold uppercase tracking-wide">
                    Employment</legend>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    {{-- Filled automatically with the next free ID. It can be edited. --}}
                    <x-form.input light :use-old="$createFailed" name="employee_id" label="Employee ID" required
                        autocomplete="off" placeholder="Enter employee ID"
                        value="{{ $createFailed ? old('employee_id') : $nextEmployeeId }}" />

                    <x-form.input light :use-old="$createFailed" name="employment_type" label="Employment Type"
                        type="select" :options="$employmentTypes" value="{{ $createFailed ? old('employment_type') : '' }}"
                        required />

                    <x-form.input light :use-old="$createFailed" name="department" label="Department" type="select"
                        :options="[
                            'administration_records' => 'Administration & Records',
                            'barangay_council' => 'Barangay Council',
                            'finance_treasury' => 'Finance & Treasury',
                            'peace_order' => 'Peace & Order',
                            'health_nutrition' => 'Health & Nutrition',
                            'social_welfare' => 'Social Welfare',
                            'disaster_risk_reduction' => 'Disaster Risk Reduction',
                            'environment_infrastructure' => 'Environment & Infrastructure',
                            'others' => 'Others',
                        ]" value="{{ $createFailed ? old('department') : '' }}" required />

                    <div data-other-for="department" class="hidden">
                        <x-form.input light :use-old="$createFailed" name="department_other" label="Specify Department"
                            placeholder="Enter department" />
                    </div>

                    <x-form.input light :use-old="$createFailed" name="position" label="Position" type="select"
                        :options="[
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
                        ]" value="{{ $createFailed ? old('position') : '' }}" required />

                    <div data-other-for="position" class="hidden">
                        <x-form.input light :use-old="$createFailed" name="position_other" label="Specify Position"
                            placeholder="Enter position" />
                    </div>

                    <x-form.input light :use-old="$createFailed" name="office" label="Office (Optional)" type="select"
                        :options="[
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
                        ]" value="{{ $createFailed ? old('office') : '' }}" />

                    <div data-other-for="office" class="hidden">
                        <x-form.input light :use-old="$createFailed" name="office_other" label="Specify Office"
                            placeholder="Enter office" />
                    </div>

                    <x-form.input light :use-old="$createFailed" name="date_hired" label="Date Hired (Optional)"
                        type="date" max="{{ now()->toDateString() }}" />
                </div>
            </fieldset>

            {{-- Security --}}
            <fieldset class="border-(--gray-200) border-t pt-5 dark:border-white/10">
                <legend class="text-(--blue-700) dark:text-(--celadon-500) text-xs font-bold uppercase tracking-wide">
                    Security
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
                    class="border-(--gray-300) bg-(--white) text-(--gray-700) hover:bg-(--gray-100) focus-visible:ring-(--blue-500) dark:text-(--gray-200) dark:focus-visible:ring-(--celadon-500) w-full rounded-md border px-4 py-2.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 dark:border-white/20 dark:bg-transparent dark:hover:bg-white/10">
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

{{-- Show a "Specify ..." box when "Others" is picked --}}
<script>
    (function() {
        const modal = document.getElementById('createUserModal');
        const employment = document.getElementById('createUserEmployment');

        ['department', 'position', 'office'].forEach((name) => {
            const select = modal.querySelector(`[name="${name}"]`);
            const box = modal.querySelector(`[data-other-for="${name}"]`);
            const input = box.querySelector('input');

            function sync() {
                const show = select.value === 'others';
                box.classList.toggle('hidden', !show);
                input.required = show && !employment.classList.contains('hidden');
                if (!show) input.value = '';
            }

            select.addEventListener('change', sync);
            sync();
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
            ['employee_id', 'employment_type', 'department', 'position'].forEach((name) => {
                const input = modal.querySelector(`[name="${name}"]`);
                if (input) input.required = !isCitizen;
            });
            // refresh the "Others" boxes so they are not required while hidden
            ['department', 'position', 'office'].forEach((name) => {
                modal.querySelector(`[name="${name}"]`).dispatchEvent(new Event('change'));
            });
        }

        role.addEventListener('change', sync);
        sync();
    })();
</script>