@extends('layouts.admin')

@section('title', 'My Profile')

@section('content')
    @php
        // Shared look (theme tokens follow the light / dark toggle)
        $card = 'rounded-lg border border-(--gray-200) bg-(--white) shadow-sm dark:border-(--gray-700) dark:bg-(--gray-800)';
        $line = 'border-(--gray-100) dark:border-(--gray-700)';
        $title = 'text-(--gray-900) dark:text-(--gray-50)';
        $muted = 'text-(--gray-500) dark:text-(--gray-400)';
        $outline = 'border border-(--gray-300) text-(--gray-700) hover:bg-(--gray-50) dark:border-(--gray-600) dark:text-(--gray-200) dark:hover:bg-(--gray-700)';
        $tile = 'rounded-md bg-(--gray-50) p-4 dark:bg-(--gray-900)';
        $value = 'mt-1 font-medium text-(--gray-800) dark:text-(--gray-100)';

        // Account fields are locked until Edit; employment fields also need an employee record.
        $locked = !$editing;
        $employmentLocked = !$editing || !$profile;

        // After a failed save the old input is used instead of the stored values.
        $hasOld = old('_form') === 'details';
        $stored = fn(string $field) => $hasOld ? null : $profile?->{$field};
    @endphp

    <div class="admin-module mx-auto max-w-4xl space-y-5">
        {{-- Explain which profile details can be changed on this page. --}}
        <x-admin.page-header title="My Profile"
            text="Review your account details and update the information you are allowed to change." />

        {{-- Show successful profile updates. --}}
        @if (session('success'))
            <div role="status"
                class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        {{-- Show only errors that belong on the profile page, excluding modal and separate-form errors. --}}
        @if ($pageErrors->isNotEmpty())
            <div role="alert"
                class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($pageErrors as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Profile card: centered on mobile, one row on larger screens --}}
        <section class="{{ $card }} overflow-hidden">
            {{-- Banner: barangay photo with a blue filter and shadow (navy shows if the photo is missing) --}}
            <div class="relative h-28 bg-[#17325c] bg-cover bg-center sm:h-40"
                style="background-image: url('{{ asset('img/photos/ynares-hall-front.jpg') }}')">
                <div class="absolute inset-0 bg-[#2457a6]/45 mix-blend-multiply dark:bg-[#0a1630]/80"></div>
                <div
                    class="bg-linear-to-t absolute inset-0 from-black/60 via-black/20 to-black/10 shadow-[inset_0_-24px_32px_-12px_rgba(0,0,0,0.45)] dark:from-black/80 dark:via-black/40 dark:to-black/20">
                </div>
            </div>

            <div
                class="flex flex-col items-center gap-4 px-5 pb-5 text-center sm:flex-row sm:items-end sm:justify-between sm:px-6 sm:text-left">
                <div class="flex min-w-0 flex-col items-center gap-3 sm:flex-row sm:items-end sm:gap-4">
                    @if ($profile?->photo_path)
                        <img src="{{ route('admin.profile.photo.show') }}?v={{ $user->updated_at?->timestamp }}"
                            alt="Profile photo"
                            class="relative z-10 -mt-12 h-24 w-24 shrink-0 rounded-full border-4 border-(--white) object-cover sm:-mt-14 sm:h-28 sm:w-28 dark:border-(--gray-800)">
                    @else
                        <span
                            class="relative z-10 -mt-12 flex h-24 w-24 shrink-0 items-center justify-center rounded-full border-4 border-(--white) bg-(--gray-100) text-3xl font-bold text-[#17325c] sm:-mt-14 sm:h-28 sm:w-28 sm:text-4xl dark:border-(--gray-800) dark:bg-(--gray-700) dark:text-(--blue-300)"
                            aria-hidden="true">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </span>
                    @endif

                    <div class="min-w-0 sm:pb-1">
                        <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                            <h2 class="{{ $title }} text-xl font-bold">{{ $user->name }}</h2>
                            <span
                                class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Administrator</span>
                        </div>
                        <p class="{{ $muted }} break-all text-sm">{{ $user->email }}</p>
                    </div>
                </div>

                {{-- Buttons: a grid on mobile, side by side on larger screens --}}
                <div
                    class="{{ $line }} grid w-full grid-cols-2 gap-3 border-t pt-4 has-[#edit-button.hidden]:grid-cols-1 sm:flex sm:w-auto sm:items-start sm:border-0 sm:pt-0">
                    {{-- Photo uploads right after choosing a file --}}
                    <form id="photo-form" method="POST" action="{{ route('admin.profile.photo') }}"
                        enctype="multipart/form-data">
                        @csrf
                        <label for="admin-profile-photo"
                            class="{{ $outline }} block cursor-pointer rounded-md px-4 py-2 text-center text-sm font-semibold focus-within:ring-2 focus-within:ring-[#17325c]/30">
                            <span id="photo-label">
                                {{ $profile?->photo_path ? 'Change photo' : 'Add photo' }}
                            </span>
                            <input id="admin-profile-photo" type="file" name="photo" accept=".jpg,.jpeg,.png"
                                class="sr-only">
                        </label>
                    </form>

                    {{-- The wrapper is what gets hidden (the button itself is inline-flex) --}}
                    <div id="edit-button" @class(['hidden' => $editing])>
                        <x-form.button type="button" id="edit-trigger" color="#17325c" class="w-full py-2!">
                            Edit
                        </x-form.button>
                    </div>
                </div>
            </div>

            <p id="photo-error" role="alert"
                class="{{ $errors->has('photo') ? '' : 'hidden' }} px-5 pb-4 text-center text-sm text-red-700 sm:px-6 sm:text-right dark:text-red-300">
                {{ $errors->first('photo') }}
            </p>
        </section>

        {{-- Account + employment details (everything unlocks when Edit is clicked) --}}
        <form id="profile-form" method="POST" action="{{ route('admin.profile.update') }}"
            class="{{ $card }} space-y-6 p-4 sm:p-6" data-validate data-has-profile="{{ $profile ? '1' : '0' }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="details">

            <fieldset>
                <legend class="{{ $line }} {{ $title }} w-full border-b pb-2 text-lg font-semibold">Account
                    Information</legend>

                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                    <div class="{{ $tile }}">
                        <dt class="{{ $muted }}">Role</dt>
                        <dd class="{{ $value }}">Administrator</dd>
                    </div>

                    <div class="{{ $tile }}">
                        <dt class="{{ $muted }}">Account status</dt>
                        <dd class="{{ $value }}">{{ $user->status ? ucfirst($user->status) : 'Not added' }}</dd>
                    </div>

                    <div class="{{ $tile }}">
                        <dt class="{{ $muted }}">Member since</dt>
                        <dd class="{{ $value }}">{{ $user->created_at?->format('M j, Y') ?: 'Not added' }}</dd>
                    </div>
                </dl>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <x-form.input light placeholder=" " name="name" label="Full name" :value="$user->name" required
                        maxlength="255" autocomplete="name" data-editable :disabled="$locked" />

                    <x-form.input light placeholder=" " name="phone" label="Phone number" type="tel"
                        :value="$user->phone" maxlength="20" autocomplete="tel" data-editable :disabled="$locked" />

                    <div class="sm:col-span-2">
                        <x-form.input light placeholder=" " name="email" label="Email address" type="email"
                            :value="$user->email" required maxlength="255" autocomplete="email" data-editable
                            :disabled="$locked" />
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="{{ $line }} {{ $title }} w-full border-b pb-2 text-lg font-semibold">Employment
                    Details</legend>
                <p class="{{ $muted }} mt-3 text-sm">
                    @if ($profile)
                        Your Employee ID comes from your employee record and cannot be changed. The rest can be updated
                        with Edit.
                    @else
                        No employee record was found for this account, so these details cannot be edited here.
                    @endif
                </p>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    {{-- Employee ID: always read-only --}}
                    <x-form.input light placeholder=" " name="employee_id" label="Employee ID"
                        :value="$profile?->employee_id ?: 'Not added'" :use-old="false" readonly />

                    <x-form.input light name="employment_type" label="Employment type" type="select" required
                        :options="['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract']" :value="$profile?->employment_type" data-editable data-employment
                        :disabled="$employmentLocked" />

                    <x-form.input light name="department" label="Department" type="select" required
                        :value="$profile?->department" data-pick="department"
                        data-stored="{{ $stored('department') }}" data-editable data-employment
                        :disabled="$employmentLocked" :options="[
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
                        <x-form.input light name="department_other" label="Specify Department"
                            placeholder="Enter department" maxlength="255" data-editable data-employment
                            :disabled="$employmentLocked" />
                    </div>

                    <x-form.input light name="position" label="Position" type="select" required
                        :value="$profile?->position" data-pick="position" data-stored="{{ $stored('position') }}"
                        data-editable data-employment :disabled="$employmentLocked" :options="[
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
                        <x-form.input light name="position_other" label="Specify Position"
                            placeholder="Enter position" maxlength="255" data-editable data-employment
                            :disabled="$employmentLocked" />
                    </div>

                    <x-form.input light name="office" label="Office (Optional)" type="select"
                        :value="$profile?->office" data-pick="office" data-stored="{{ $stored('office') }}"
                        data-editable data-employment :disabled="$employmentLocked" :options="[
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
                        <x-form.input light name="office_other" label="Specify Office" placeholder="Enter office"
                            maxlength="255" data-editable data-employment :disabled="$employmentLocked" />
                    </div>

                    <x-form.input light raised type="date" name="date_hired" label="Date Hired (Optional)"
                        max="{{ now()->toDateString() }}" :value="$profile?->date_hired?->toDateString()" data-editable
                        data-employment :disabled="$employmentLocked" />
                </div>
            </fieldset>

            <div class="{{ $line }} border-t pt-5">
                <div id="edit-actions" @class(['flex gap-3', 'hidden' => !$editing])>
                    <x-form.button type="button" id="cancel-button" color="slate" class="w-full py-2.5!">
                        Cancel
                    </x-form.button>
                    <x-form.button type="submit" color="#17325c" loading-text="Saving..." class="w-full py-2.5!">
                        Save
                    </x-form.button>
                </div>
            </div>
        </form>

        {{-- Password: its own form, so it never submits with the details form --}}
        <section class="{{ $card }} p-4 sm:p-6">
            <h2 class="{{ $line }} {{ $title }} border-b pb-2 text-lg font-semibold">Change Password</h2>
            <p class="{{ $muted }} mt-3 text-sm">Enter your current password, then choose a new one.</p>

            <form method="POST" action="{{ route('admin.profile.update') }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="password">

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.input light placeholder=" " name="current_password" label="Current password"
                        type="password" required autocomplete="current-password" />

                    <div class="hidden sm:block" aria-hidden="true"></div>

                    <x-form.input light placeholder=" " name="password" label="New password" type="password" required
                        data-password-strength maxlength="16" autocomplete="new-password">
                        <x-feedbacks.password-requirement />
                    </x-form.input>

                    <div>
                        <x-form.input light placeholder=" " name="password_confirmation" label="Confirm new password"
                            type="password" required data-password-confirmation maxlength="16"
                            autocomplete="new-password" aria-describedby="password_confirmation-feedback">
                            <p id="password_confirmation-feedback" data-password-confirmation-feedback
                                class="mt-1 hidden text-xs font-medium text-red-600" aria-live="polite"></p>
                        </x-form.input>
                    </div>
                </div>

                <div class="{{ $line }} flex justify-end border-t pt-4">
                    <x-form.button type="submit" color="blue" loading-text="Saving..." class="py-2.5">
                        Update password
                    </x-form.button>
                </div>
            </form>
        </section>

        {{-- Keep two-factor settings in their own independent form. --}}
        <section class="{{ $card }} p-4 sm:p-6">
            <h2 class="{{ $line }} {{ $title }} border-b pb-2 text-lg font-semibold">Two-factor login</h2>
            <p class="{{ $muted }} mt-3 text-sm">
                When on, you enter a code sent to your verified email or mobile number each time you log in.
            </p>

            <div class="{{ $title }} mt-4">
                <x-partials.two-factor-form :action="route('admin.profile.two-factor')" />
            </div>
        </section>
    </div>

    <script>
        (() => {
            const form = document.getElementById('profile-form');
            const editWrap = document.getElementById('edit-button');
            const editTrigger = document.getElementById('edit-trigger');
            const actions = document.getElementById('edit-actions');
            const cancelButton = document.getElementById('cancel-button');

            // ---- "Specify ..." box: shown only while "Others" is selected ----
            const picks = [...form.querySelectorAll('[data-pick]')].map((select) => {
                const name = select.dataset.pick;
                const box = form.querySelector(`[data-other-for="${name}"]`);
                const input = box.querySelector('input');

                const refresh = () => {
                    const show = select.value === 'others';
                    box.classList.toggle('hidden', !show);
                    input.required = show;
                };

                select.addEventListener('change', refresh);

                // A saved value that is not in the list was typed under "Others".
                const saved = select.dataset.stored;
                if (saved && ![...select.options].some((opt) => opt.value === saved)) {
                    select.value = 'others';
                    input.value = saved;
                }

                refresh();
                return { select, input, refresh };
            });

            // ---- Edit mode: fields stay locked until Edit is clicked ----
            if (form && editWrap && editTrigger && actions && cancelButton) {
                const hasProfile = form.dataset.hasProfile === '1';
                const fields = form.querySelectorAll('[data-editable]');

                editTrigger.addEventListener('click', () => {
                    fields.forEach((field) => {
                        // Employment fields need an employee record.
                        if (field.hasAttribute('data-employment') && !hasProfile) return;
                        field.disabled = false;
                    });
                    editWrap.classList.add('hidden');
                    actions.classList.remove('hidden');
                    fields[0]?.focus();
                });

                // Reload to discard unsaved changes.
                cancelButton.addEventListener('click', () => window.location.reload());
            }

            // ---- Photo upload: check size first, then submit ----
            const photoInput = document.getElementById('admin-profile-photo');
            const photoError = document.getElementById('photo-error');
            const photoLabel = document.getElementById('photo-label');
            const maxPhotoBytes = 2 * 1024 * 1024;

            photoInput.addEventListener('change', () => {
                const file = photoInput.files[0];
                if (!file) return;

                if (file.size > maxPhotoBytes) {
                    photoError.textContent = 'The photo must not be larger than 2 MB.';
                    photoError.classList.remove('hidden');
                    photoInput.value = '';
                    return;
                }

                photoError.classList.add('hidden');
                photoLabel.textContent = 'Uploading...';
                photoInput.form.submit();
            });
        })();
    </script>
@endsection