@extends('layouts.citizen')

@section('title', 'My Profile')

@section('content')
    @php
        // Shared look (theme tokens follow the light / dark toggle)
        $card = 'rounded-lg border border-(--gray-200) bg-(--white) shadow-sm dark:border-(--gray-700) dark:bg-(--gray-800)';
        $line = 'border-(--gray-100) dark:border-(--gray-700)';
        $title = 'text-(--gray-900) dark:text-(--gray-50)';
        $muted = 'text-(--gray-500) dark:text-(--gray-400)';
        $primary = 'bg-[#17325c] text-white hover:bg-[#10244a] dark:bg-(--blue-600) dark:hover:bg-(--blue-700)';
        $outline = 'border border-(--gray-300) text-(--gray-700) hover:bg-(--gray-50) dark:border-(--gray-600) dark:text-(--gray-200) dark:hover:bg-(--gray-700)';
        $tile = 'rounded-md bg-(--gray-50) p-4 dark:bg-(--gray-900)';

        // Two-factor errors are shown inside their own card, not in the page summary.
        $pageErrors = collect($errors->getMessages())
            ->except(['two_factor_enabled', 'current_password'])
            ->flatten();
    @endphp

    <div class="space-y-6">
        {{-- Header --}}
        <header class="border-(--gray-200) dark:border-(--gray-700) space-y-2 border-b pb-4">
            <nav class="text-(--gray-500) dark:text-(--gray-400) flex items-center gap-2 text-xs" aria-label="Breadcrumb">
                <a href="{{ route('citizen.services') }}"
                    class="hover:text-(--blue-700) dark:hover:text-(--blue-300)">Services</a>
                <span aria-hidden="true">›</span>
                <span class="text-(--blue-700) dark:text-(--blue-300) font-semibold">My profile</span>
            </nav>

            <div>
                <h1 class="text-(--blue-900) dark:text-(--blue-300) text-2xl font-bold tracking-tight sm:text-3xl">My profile
                </h1>
                <p class="text-(--gray-600) dark:text-(--gray-300) mt-1 text-sm">Review the account and contact information
                    tied to your citizen portal.</p>
            </div>
        </header>

        @unless ($verified)
            <x-partials.citizen-notice type="amber" title="Profile editing is locked">
                @if ($status === 'pending')
                    Your identity application is being reviewed. Editing opens once it is approved.
                @else
                    Profile editing is available only after your identity verification is approved.
                @endif

                <a href="{{ route('citizen.identity-verification') }}"
                    class="{{ $primary }} mt-3 block w-fit rounded-md px-5 py-2.5 text-sm font-semibold">
                    @if ($status === 'pending')
                        View status
                    @else
                        Verify account
                    @endif
                </a>
            </x-partials.citizen-notice>
        @endunless

        @if ($pageErrors->isNotEmpty())
            <div role="alert"
                class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
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
                        <img src="{{ route('citizen.profile.photo.show') }}?v={{ $profile->updated_at?->timestamp }}"
                            alt="Profile photo"
                            class="relative z-10 -mt-12 h-24 w-24 shrink-0 rounded-full border-4 border-(--white) object-cover sm:-mt-14 sm:h-28 sm:w-28 dark:border-(--gray-800)">
                    @else
                        <span
                            class="relative z-10 -mt-12 flex h-24 w-24 shrink-0 items-center justify-center rounded-full border-4 border-(--white) bg-(--gray-100) text-3xl font-bold text-[#17325c] sm:-mt-14 sm:h-28 sm:w-28 sm:text-4xl dark:border-(--gray-800) dark:bg-(--gray-700) dark:text-(--blue-300)">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </span>
                    @endif

                    <div class="min-w-0 sm:pb-1">
                        <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                            <h2 class="{{ $title }} text-xl font-bold">{{ $user->name }}</h2>
                            @if ($verified)
                                <span
                                    class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-bold text-green-700 dark:bg-green-900/40 dark:text-green-300">Verified</span>
                            @else
                                <span
                                    class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Not
                                    verified</span>
                            @endif
                        </div>
                        <p class="{{ $muted }} break-all text-sm">{{ $user->email }}</p>
                    </div>
                </div>

                @if ($verified)
                    {{-- Buttons: a grid on mobile, side by side on larger screens --}}
                    <div
                        class="{{ $line }} grid w-full grid-cols-2 gap-3 border-t pt-4 has-[#edit-button.hidden]:grid-cols-1 sm:flex sm:w-auto sm:border-0 sm:pt-0">
                        {{-- Photo uploads right after choosing a file --}}
                        <form method="POST" action="{{ route('citizen.profile.photo') }}" enctype="multipart/form-data">
                            @csrf
                            <label
                                class="{{ $outline }} block cursor-pointer rounded-md px-4 py-2 text-center text-sm font-semibold">
                                Change photo
                                <input type="file" name="photo" accept=".jpg,.jpeg,.png" class="hidden"
                                    onchange="this.form.submit()">
                            </label>
                        </form>

                        <button type="button" id="edit-button" @class([
                            $primary,
                            'rounded-md px-5 py-2 text-sm font-semibold',
                            'hidden' => $editing,
                        ])>Edit</button>
                    </div>
                @endif
            </div>
        </section>

        {{-- Editable details (disabled until Edit is clicked) --}}
        <form id="profile-form" method="POST" action="{{ route('citizen.profile.update') }}"
            class="{{ $card }} space-y-6 p-4 sm:p-6">
            @csrf
            @method('PUT')

            <fieldset>
                <h2 class="{{ $line }} {{ $title }} border-b pb-2 text-lg font-semibold">Account Information</h2>

                {{-- Verified details (checked against your ID, so not editable) --}}
                <div class="mt-4">
                    <h3 class="{{ $title }} text-sm font-semibold">Verified details</h3>
                    <p class="{{ $muted }} mt-0.5 text-sm">These are checked against your ID, so they cannot be edited.
                    </p>

                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
                        <div class="{{ $tile }}">
                            <dt class="{{ $muted }}">Date of birth</dt>
                            <dd class="mt-1 font-medium text-(--gray-800) dark:text-(--gray-100)">
                                {{ $profile?->date_of_birth?->format('M j, Y') ?: 'Not added' }}</dd>
                        </div>

                        <div class="{{ $tile }}">
                            <dt class="{{ $muted }}">Gender</dt>
                            <dd class="mt-1 font-medium text-(--gray-800) dark:text-(--gray-100)">
                                {{ $profile?->gender ?: 'Not added' }}</dd>
                        </div>

                        <div class="{{ $tile }}">
                            <dt class="{{ $muted }}">Place of birth</dt>
                            <dd class="mt-1 font-medium text-(--gray-800) dark:text-(--gray-100)">
                                {{ $profile?->place_of_birth ?: 'Not added' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    {{-- Name and age are always locked --}}
                    <x-form.input light placeholder=" " name="full_name" label="Full name" :value="$user->name"
                        :disabled="true" />
                    <x-form.input light placeholder=" " name="age" label="Age" :value="$profile?->age()" :disabled="true" />

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
                    <x-form.input light placeholder=" " name="nationality" label="Nationality" :value="$profile?->nationality"
                        :disabled="!$editing" />
                </div>
            </fieldset>

            <fieldset>
                <legend class="{{ $line }} {{ $title }} border-b pb-2 text-sm font-semibold">Contact and address
                </legend>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <x-form.input light placeholder=" " name="phone" label="Phone number" type="tel"
                        :value="$user->phone" :disabled="!$editing" />

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
                <div class="{{ $line }} border-t pt-5">
                    <div id="edit-actions" @class(['flex gap-3', 'hidden' => !$editing])>
                        <button type="button" id="cancel-button"
                            class="{{ $outline }} w-full rounded-md px-5 py-2.5 text-sm font-semibold">
                            Cancel
                        </button>
                        <button type="submit" class="{{ $primary }} w-full rounded-md px-5 py-2.5 text-sm font-semibold">
                            Save
                        </button>
                    </div>
                </div>
            @endif
        </form>

        {{-- Login security: its own form, so it never submits with the profile form --}}
        <section class="{{ $card }} p-4 sm:p-6">
            <h2 class="{{ $line }} {{ $title }} border-b pb-2 text-lg font-semibold">Login security</h2>
            <p class="{{ $muted }} mt-3 text-sm">
                When on, you enter a code sent to your verified email or mobile number each time you log in.
            </p>

            <div class="{{ $title }} mt-4">
                @include('components.partials.two-factor-form', ['action' => route('citizen.profile.two-factor')])
            </div>
        </section>
    </div>

    @if ($verified)
        <script>
            (() => {
                const form = document.getElementById('profile-form');
                const editButton = document.getElementById('edit-button');
                const actions = document.getElementById('edit-actions');
                const cancelButton = document.getElementById('cancel-button');

                if (!form || !editButton || !actions || !cancelButton) return;

                // Full name and age stay locked; everything else becomes editable.
                const fields = form.querySelectorAll(
                    'input:not([name="full_name"]):not([name="age"]):not([type="hidden"]), select, textarea'
                );

                editButton.addEventListener('click', () => {
                    fields.forEach((field) => (field.disabled = false));
                    editButton.classList.add('hidden');
                    actions.classList.remove('hidden');
                });

                // Reload to discard unsaved changes.
                cancelButton.addEventListener('click', () => window.location.reload());
            })();
        </script>
    @endif
@endsection