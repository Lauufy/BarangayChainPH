@extends('layouts.citizen')

@section('title', 'My Profile')

@section('content')
    @php
        $input = 'mt-1 w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-[#17325c] focus:outline-none disabled:bg-slate-50 disabled:text-slate-500';
        $button = 'rounded-md bg-[#17325c] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#10244a]';
    @endphp

    <div class="space-y-6">
        <x-partials.citizen-page-header title="My profile"
            text="Review the account and contact information tied to your citizen portal." />

        {{-- Notice when editing is locked --}}
        @unless ($verified)
            <x-partials.citizen-notice type="amber" title="Profile editing is locked">
                {{ $status === 'pending'
                    ? 'Your identity application is being reviewed. Editing opens once it is approved.'
                    : 'Profile editing is available only after your identity verification is approved.' }}
                <a href="{{ route('citizen.identity-verification') }}" class="mt-3 block w-fit {{ $button }}">
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

        <section class="rounded-md border border-slate-200 bg-white p-5 sm:p-6">
            {{-- Photo, name, email --}}
            <div class="flex items-center gap-4 border-b border-slate-100 pb-5">
                @if ($profile?->photo_path)
                    <img src="{{ route('citizen.profile.photo.show') }}?v={{ $profile->updated_at?->timestamp }}" alt="Profile photo"
                        class="h-14 w-14 shrink-0 rounded-full object-cover">
                @else
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[#17325c] text-xl font-bold text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                @endif

                <div class="min-w-0">
                    <h2 class="truncate text-lg font-bold text-slate-900">{{ $user->name }}</h2>
                    <p class="break-all text-sm text-slate-500">{{ $user->email }}</p>

                    {{-- Photo uploads right after choosing a file --}}
                    @if ($verified)
                        <form method="POST" action="{{ route('citizen.profile.photo') }}" enctype="multipart/form-data">
                            @csrf
                            <label class="mt-1 inline-block cursor-pointer text-sm font-semibold text-[#17325c] underline">
                                Change photo
                                <input type="file" name="photo" accept=".jpg,.jpeg,.png" class="hidden" onchange="this.form.submit()">
                            </label>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Details that cannot be edited (checked against the ID) --}}
            <dl class="grid gap-5 py-5 text-sm sm:grid-cols-2">
                @foreach ([
                    'Date of birth' => $profile?->date_of_birth?->format('M j, Y'),
                    'Gender' => $profile?->gender,
                    'Place of birth' => $profile?->place_of_birth,
                    'Nationality' => $profile?->nationality,
                ] as $label => $value)
                    <div>
                        <dt class="text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 font-medium text-slate-800">{{ $value ?: 'Not added' }}</dd>
                    </div>
                @endforeach
            </dl>

            {{-- Editable details (fields stay disabled until Edit is clicked) --}}
            <form id="profile-form" method="POST" action="{{ route('citizen.profile.update') }}" class="border-t border-slate-100 pt-5">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="civil_status" class="text-sm font-medium text-slate-700">Civil status</label>
                        <select id="civil_status" name="civil_status" @disabled(! $editing) class="{{ $input }}">
                            <option value="">Select</option>
                            @foreach (['Single', 'Married', 'Widowed', 'Separated', 'Divorced'] as $option)
                                <option value="{{ $option }}" @selected(old('civil_status', $profile?->civil_status) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    @foreach ([
                        'phone' => ['Phone number', 'tel'],
                        'religion' => ['Religion', 'text'],
                        'occupation' => ['Occupation', 'text'],
                        'house_number' => ['House number', 'text'],
                        'barangay' => ['Barangay', 'text'],
                        'city_municipality' => ['City / Municipality', 'text'],
                        'province' => ['Province', 'text'],
                        'zip_code' => ['ZIP code', 'text'],
                    ] as $name => [$label, $type])
                        <div>
                            <label for="{{ $name }}" class="text-sm font-medium text-slate-700">{{ $label }}</label>
                            <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" @disabled(! $editing)
                                value="{{ old($name, $profile?->$name) }}" class="{{ $input }}">
                        </div>
                    @endforeach
                </div>

                @if ($verified)
                    <div class="mt-6">
                        <button type="button" id="edit-button" @class([$button, 'hidden' => $editing])>Edit</button>

                        <div id="edit-actions" @class(['flex gap-3', 'hidden' => ! $editing])>
                            <button type="submit" class="{{ $button }}">Save</button>
                            <button type="button" id="cancel-button" class="rounded-md border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                        </div>
                    </div>
                @endif
            </form>
        </section>

        <a href="{{ route('privacy') }}" class="inline-block text-sm font-semibold text-[#17325c] underline">Read our privacy notice</a>
    </div>

    @if ($verified)
        <script>
            const fields = document.querySelectorAll('#profile-form input, #profile-form select');
            const editButton = document.getElementById('edit-button');
            const actions = document.getElementById('edit-actions');

            // Edit: unlock the fields
            editButton.addEventListener('click', () => {
                fields.forEach(field => field.disabled = false);
                editButton.classList.add('hidden');
                actions.classList.remove('hidden');
            });

            // Cancel: reload to restore the saved values
            document.getElementById('cancel-button').addEventListener('click', () => location.reload());
        </script>
    @endif
@endsection