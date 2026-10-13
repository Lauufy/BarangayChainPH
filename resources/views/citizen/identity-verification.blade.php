@extends('layouts.citizen')

@section('title', 'Identity Verification')

@section('content')
    <div class="mx-auto max-w-4xl space-y-5 sm:space-y-6">
        <x-partials.citizen-page-header title="Identity verification"
            text="Verify your identity to request barangay services and edit your profile." />

        {{-- Status message --}}
        @if ($status === 'verified')
            <x-partials.citizen-notice type="green" title="Verified">Your identity is verified. You can now request barangay services.</x-partials.citizen-notice>
        @elseif ($status === 'pending')
            <x-partials.citizen-notice type="amber" title="Pending review">Your application was submitted. Barangay staff will review it. You will be notified of the result.</x-partials.citizen-notice>
        @elseif ($status === 'rejected')
            <x-partials.citizen-notice type="red" title="Application rejected">
                <p>Reason: {{ $verification->rejection_reason }}</p>
                <p>Please fix the problem and submit again below.</p>
            </x-partials.citizen-notice>
        @else
            <x-partials.citizen-notice title="Not verified yet">Complete the two steps below. Barangay staff will review your application.</x-partials.citizen-notice>
        @endif

        @if ($status === 'pending' || $status === 'verified')
            {{-- Submission only --}}
            <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <h2 class="font-semibold text-slate-900">Your submission</h2>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">ID type</dt>
                        <dd class="mt-1 font-medium text-slate-800">{{ $verification->document_type }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Submitted</dt>
                        <dd class="mt-1 font-medium text-slate-800">{{ $verification->submitted_at->format('M j, Y g:i A') }}</dd>
                    </div>
                </dl>
                <a href="{{ route('citizen.identity-verification.file', $verification) }}" target="_blank"
                    class="mt-4 inline-block text-sm font-semibold text-[#17325c] underline">View my uploaded ID</a>
            </section>
        @else
            {{-- Errors --}}
            @if ($errors->any())
                <div role="alert" class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Steps: step 1 is clickable when you are on step 2 --}}
            <div class="flex gap-2 text-sm font-semibold">
                @if ($step === 2)
                    <a href="{{ route('citizen.identity-verification') }}"
                        class="flex-1 rounded-full border border-[#17325c] px-4 py-2 text-center text-[#17325c] hover:bg-slate-50 sm:flex-none">✓ Personal Information</a>
                @else
                    <span class="flex-1 rounded-full bg-[#17325c] px-4 py-2 text-center text-white sm:flex-none">1 Personal Information</span>
                @endif
                <span @class([
                    'flex-1 rounded-full px-4 py-2 text-center sm:flex-none',
                    'bg-[#17325c] text-white' => $step === 2,
                    'bg-slate-100 text-slate-400' => $step === 1,
                ])>2 Upload ID</span>
            </div>

            @if ($step === 1)
                {{-- STEP 1 --}}
                <form method="POST" action="{{ route('citizen.identity-verification.details') }}"
                    class="space-y-8 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                    @csrf

                    <fieldset>
                        <legend class="border-b border-slate-100 pb-2 text-sm font-semibold text-slate-900">Personal Information</legend>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            {{-- From the account (locked) --}}
                            <x-form.input light disabled placeholder=" " name="name" label="Full name" :value="auth()->user()->name" />
                            <x-form.input light disabled placeholder=" " name="email" label="Email" :value="auth()->user()->email" />

                            <x-form.input light placeholder=" " name="date_of_birth" label="Date of birth" type="date" required
                                :value="$profile?->date_of_birth?->format('Y-m-d')" />
                            <x-form.input light name="gender" label="Gender" type="select" required placeholder="Select"
                                :options="['Male' => 'Male', 'Female' => 'Female']" :value="$profile?->gender" />
                            <x-form.input light name="civil_status" label="Civil status" type="select" required placeholder="Select"
                                :options="['Single' => 'Single', 'Married' => 'Married', 'Widowed' => 'Widowed', 'Separated' => 'Separated', 'Divorced' => 'Divorced']"
                                :value="$profile?->civil_status" />
                            <x-form.input light placeholder=" " name="place_of_birth" label="Place of birth" required :value="$profile?->place_of_birth" />
                            <x-form.input light placeholder=" " name="nationality" label="Nationality" required :value="$profile?->nationality" />
                            <x-form.input light placeholder=" " name="religion" label="Religion (optional)" :value="$profile?->religion" />
                            <x-form.input light placeholder=" " name="occupation" label="Occupation (optional)" :value="$profile?->occupation" />
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="border-b border-slate-100 pb-2 text-sm font-semibold text-slate-900">Address and contact</legend>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <x-form.input light placeholder=" " name="phone" label="Phone number" type="tel" required :value="$profile?->phone" />
                            <x-form.input light placeholder=" " name="house_number" label="House number (optional)" :value="$profile?->house_number" />
                            <x-form.input light placeholder=" " name="barangay" label="Barangay" required :value="$profile?->barangay" />
                            <x-form.input light placeholder=" " name="city_municipality" label="City / Municipality" required :value="$profile?->city_municipality" />
                            <x-form.input light placeholder=" " name="province" label="Province" required :value="$profile?->province" />
                            <x-form.input light placeholder=" " name="zip_code" label="ZIP code" required :value="$profile?->zip_code" />
                        </div>
                    </fieldset>

                    {{-- Buttons: full width --}}
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row">
                        <a href="{{ url('/') }}"
                            class="w-full rounded-md border border-slate-300 px-5 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
                        <button type="submit"
                            class="w-full rounded-md bg-[#17325c] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#10244a]">Next</button>
                    </div>
                </form>
            @else
                {{-- STEP 2 --}}
                <form id="document-form" method="POST" action="{{ route('citizen.identity-verification.document') }}"
                    enctype="multipart/form-data" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                    @csrf

                    <h2 class="text-xl font-semibold text-slate-900">Upload ID</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">
                        Upload a clear photo or scan of a valid government ID. Your name and photo must be readable.
                    </p>

                    <div class="mt-6 grid gap-6 md:grid-cols-2">
                        <div class="space-y-5">
                            <x-form.input light name="document_type" label="Type of ID" type="select" required placeholder="Select"
                                :options="array_combine($documentTypes, $documentTypes)" />

                            <div>
                                <label for="document" class="text-sm font-medium text-slate-700">ID file</label>
                                <input id="document" name="document" type="file" required accept=".jpg,.jpeg,.png,.pdf"
                                    class="mt-1 block w-full rounded-md border border-dashed border-slate-300 bg-slate-50 p-3 text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-[#17325c] file:px-4 file:py-2 file:font-semibold file:text-white">
                                <p class="mt-1 text-xs text-slate-500">JPG, PNG, or PDF · max 5 MB</p>
                                <p id="file-error" class="mt-1 text-sm text-red-600 empty:hidden"></p>
                            </div>
                        </div>

                        <div class="relative flex min-h-64 items-center justify-center overflow-hidden rounded-md border border-dashed border-slate-300 bg-slate-50">
                            <span class="px-4 text-center text-sm text-slate-400">ID preview will appear here</span>
                            <img id="preview" alt="ID preview" class="absolute inset-0 hidden h-full w-full bg-white object-contain p-3">
                        </div>
                    </div>

                    {{-- Buttons: full width --}}
                    <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row">
                        <a href="{{ route('citizen.identity-verification') }}"
                            class="w-full rounded-md border border-slate-300 px-5 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</a>
                        <button id="submit-button" type="submit"
                            class="w-full rounded-md bg-[#17325c] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#10244a] disabled:opacity-60">Submit for verification</button>
                    </div>
                </form>

                <script>
                    const input = document.getElementById('document');
                    const preview = document.getElementById('preview');
                    const error = document.getElementById('file-error');

                    // Check the file and show a preview for images
                    input.addEventListener('change', () => {
                        const file = input.files[0];
                        preview.classList.add('hidden');
                        error.textContent = '';
                        if (!file) return;

                        if (!['image/jpeg', 'image/png', 'application/pdf'].includes(file.type)) {
                            error.textContent = 'The ID must be a JPG, PNG, or PDF file.';
                            input.value = '';
                        } else if (file.size > 5 * 1024 * 1024) {
                            error.textContent = 'The ID must not be larger than 5 MB.';
                            input.value = '';
                        } else if (file.type.startsWith('image/')) {
                            preview.src = URL.createObjectURL(file);
                            preview.classList.remove('hidden');
                        }
                    });

                    // Stop double submits
                    document.getElementById('document-form').addEventListener('submit', () => {
                        document.getElementById('submit-button').disabled = true;
                    });
                </script>
            @endif
        @endif
    </div>
@endsection