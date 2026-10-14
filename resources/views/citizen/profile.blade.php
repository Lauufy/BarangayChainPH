@extends('layouts.citizen')
@section('title', 'My Profile')
@section('content')
    <div class="mx-auto max-w-4xl space-y-5 sm:space-y-6">
        {{-- Notice when editing is locked --}}
        @unless ($verified)
            <x-partials.citizen-notice type="amber" title="Profile editing is locked">
                {{ $status === 'pending'
                    ? 'Your identity application is being reviewed. Editing opens once it is approved.'
                    : 'Profile editing is available only after your identity verification is approved.' }}
                <a href="{{ route('citizen.identity-verification') }}"
                    class="mt-3 block w-fit rounded-md bg-[#17325c] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#10244a]">
                    {{ $status === 'pending' ? 'View status' : 'Verify account' }}
                </a>
            </x-partials.citizen-notice>
        @endunless
        {{-- Error messages --}}
        @if ($errors->any())
            <div role="alert" class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="flex flex-col gap-5 sm:gap-6 lg:flex-row">
            {{-- Left: profile card (photo, name, email) --}}
            <section
                class="flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm lg:w-72 lg:shrink-0">
                <div class="h-24 bg-[#17325c]"></div>
                <div class="flex flex-1 flex-col items-center px-5 pb-6 text-center">
                    @if ($profile?->photo_path)
                        <img src="{{ route('citizen.profile.photo.show') }}?v={{ $profile->updated_at?->timestamp }}"
                            alt="Profile photo" class="-mt-14 h-28 w-28 rounded-full border-4 border-white object-cover">
                    @else
                        <span
                            class="-mt-14 flex h-28 w-28 items-center justify-center rounded-full border-4 border-white bg-slate-100 text-4xl font-bold text-[#17325c]">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </span>
                    @endif
                    <h2 class="mt-4 text-xl font-bold text-slate-900">{{ $user->name }}</h2>
                    <p class="break-all text-sm text-slate-500">{{ $user->email }}</p>
                    @if ($verified)
                        <span
                            class="mt-3 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-bold text-green-700">Verified</span>
                        {{-- Photo uploads right after choosing a file --}}
                        <form method="POST" action="{{ route('citizen.profile.photo') }}" enctype="multipart/form-data"
                            class="mt-auto w-full pt-5">
                            @csrf
                            <label
                                class="block cursor-pointer rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Change photo
                                <input type="file" name="photo" accept=".jpg,.jpeg,.png" class="hidden"
                                    onchange="this.form.submit()">
                            </label>
                        </form>
                    @else
                        <span class="mt-3 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-700">Not
                            verified</span>
                    @endif
                </div>
            </section>
            {{-- Right: page title on top, verified details below (shows first on mobile) --}}
            <div class="lg:order-0 order-first flex flex-1 flex-col">
                <section class="flex flex-1 flex-col rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                    {{-- Page title --}}
                    <h1 class="text-2xl font-bold text-slate-900">My profile</h1>
                    <p class="mt-1 text-sm text-slate-500">Review the account and contact information tied to your citizen
                        portal.</p>
                    {{-- Verified details --}}
                    <h2 class="mt-5 border-t border-slate-100 pt-5 font-semibold text-slate-900">Verified details</h2>
                    <p class="mt-1 text-sm text-slate-500">These are checked against your ID, so they cannot be edited.</p>
                    <dl class="mt-4 grid flex-1 gap-3 text-sm sm:grid-cols-2">
                        @foreach ([
            'Date of birth' => $profile?->date_of_birth?->format('M j, Y'),
            'Age' => $profile?->age(),
            'Gender' => $profile?->gender,
            'Place of birth' => $profile?->place_of_birth,
            'Nationality' => $profile?->nationality,
        ] as $label => $value)
                            <div class="flex flex-col justify-center rounded-md bg-slate-50 p-4">
                                <dt class="text-slate-500">{{ $label }}</dt>
                                <dd class="mt-1 font-medium text-slate-800">{{ $value ?: 'Not added' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            </div>
        </div>
        {{-- Editable details (fields stay disabled until Edit is clicked) --}}
        <form id="profile-form" method="POST" action="{{ route('citizen.profile.update') }}"
            class="space-y-6 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            @csrf
            @method('PUT')
            <fieldset>
                <div class="flex items-center justify-between">
                    <h2 class="border-b border-slate-100 pb-2 text-lg font-semibold text-slate-900">
                        Account Information
                    </h2>
                    @if ($verified)
                        <button type="button" id="edit-button" @class([
                            'rounded-md bg-[#17325c] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#10244a]',
                            'hidden' => $editing,
                        ])>
                            Edit
                        </button>
                    @endif
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    {{-- Name is always locked --}}
                    <x-form.input light placeholder=" " name="full_name" label="Full name" :value="$user->name"
                        :disabled="true" />
                    <x-form.input light placeholder=" " name="age" label="Age" :value="$profile?->age()"
                        :disabled="true" />
                    <x-form.input light name="civil_status" label="Civil status" type="select" placeholder="Select"
                        :options="[
                            'Single' => 'Single',
                            'Married' => 'Married',
                            'Widowed' => 'Widowed',
                            'Separated' => 'Separated',
                            'Divorced' => 'Divorced',
                        ]" :value="$profile?->civil_status" :disabled="!$editing" />
                    <x-form.input light placeholder=" " name="religion" label="Religion" :value="$profile?->religion"
                        :disabled="!$editing" />
                    <x-form.input light placeholder=" " name="occupation" label="Occupation" :value="$profile?->occupation"
                        :disabled="!$editing" />
                </div>
            </fieldset>
            <fieldset>
                <legend class="border-b border-slate-100 pb-2 text-sm font-semibold text-slate-900">Contact and address
                </legend>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <x-form.input light placeholder=" " name="phone" label="Phone number" type="tel" :value="$profile?->phone"
                        :disabled="!$editing" />
                    <x-form.input light placeholder=" " name="house_number" label="House number" :value="$profile?->house_number"
                        :disabled="!$editing" />
                    <x-form.input light placeholder=" " name="barangay" label="Barangay" :value="$profile?->barangay"
                        :disabled="!$editing" />
                    <x-form.input light placeholder=" " name="city_municipality" label="City / Municipality"
                        :value="$profile?->city_municipality" :disabled="!$editing" />
                    <x-form.input light placeholder=" " name="province" label="Province" :value="$profile?->province"
                        :disabled="!$editing" />
                    <x-form.input light placeholder=" " name="zip_code" label="ZIP code" :value="$profile?->zip_code"
                        :disabled="!$editing" />
                </div>
            </fieldset>
            @if ($verified)
                <div class="border-t border-slate-100 pt-5">
                    {{-- Shown while editing --}}
                    <div id="edit-actions" @class(['flex gap-3', 'hidden' => !$editing])>
                        <button type="button" id="cancel-button"
                            class="w-full rounded-md border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit"
                            class="w-full rounded-md bg-[#17325c] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#10244a]">
                            Save
                        </button>
                    </div>
                </div>
            @endif
        </form>
    </div>
    @if ($verified)
        <script>
            (() => {
                const profileForm = document.getElementById('profile-form');
                const editButton = document.getElementById('edit-button');
                const actions = document.getElementById('edit-actions');
                const cancelButton = document.getElementById('cancel-button');
                if (!profileForm || !editButton || !actions || !cancelButton) {
                    return;
                }
                // Keep the full name locked; enable the remaining editable fields.
                const fields = profileForm.querySelectorAll(
                    'input:not([name="full_name"]):not([type="hidden"]), select, textarea'
                );
                editButton.addEventListener('click', () => {
                    fields.forEach((field) => {
                        field.disabled = false;
                    });
                    editButton.classList.add('hidden');
                    actions.classList.remove('hidden');
                });
                // Reload to discard unsaved changes and restore saved values.
                cancelButton.addEventListener('click', () => {
                    window.location.reload();
                });
            })();
        </script>
    @endif

@endsection
