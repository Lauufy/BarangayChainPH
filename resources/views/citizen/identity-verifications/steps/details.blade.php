{{-- STEP 1: Personal information --}}
<form id="details-form" method="POST" action="{{ route('citizen.identity-verification.details') }}" class="space-y-6"
    data-validate>
    @csrf

    @if (!auth()->user()->email)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800/50 dark:bg-amber-950/40 dark:text-amber-200">
            Please add your email address below to receive verification updates.
        </div>
    @endif

    {{-- CARD 1: Basic & Personal Information --}}
    <section class="{{ $card }} space-y-5">
        <div class="border-b border-gray-100 pb-3 dark:border-gray-800">
            <h3 class="{{ $h3 }}">Personal information</h3>
            <p class="{{ $sub }} mt-0.5">Make sure these match your valid government-issued ID.</p>
        </div>

        <div class="grid gap-x-5 gap-y-4 sm:grid-cols-2 lg:grid-cols-6">
            {{-- Row 1: Identity (3 cols each on desktop) --}}
            <div class="lg:col-span-3">
                <x-form.input light name="name" label="Full name" :value="auth()->user()->name" readonly autocomplete="name" />
            </div>

            <div class="lg:col-span-3">
                <x-form.input light name="place_of_birth" label="Place of birth" required :value="old('place_of_birth', $formDetails['place_of_birth'] ?? null)"
                    placeholder="City / Municipality, Province" maxlength="100"
                    error-message="Enter your place of birth, e.g. Morong, Rizal." />
            </div>

            {{-- Row 2: Birth & Demographics (2 cols each on desktop) --}}
            <div class="lg:col-span-2">
                <x-form.input light name="date_of_birth" label="Date of birth" type="date" required :value="old('date_of_birth', $formDetails['date_of_birth'] ?? null)"
                    min="1900-01-01" max="{{ now()->subYears(18)->format('Y-m-d') }}" autocomplete="bday"
                    error-message="Select your date of birth. You must be at least 18 years old." />
            </div>

            <div class="lg:col-span-2">
                <x-form.input light name="gender" label="Gender" type="select" required placeholder="Select"
                    :options="['Male' => 'Male', 'Female' => 'Female']" :value="old('gender', $formDetails['gender'] ?? null)" error-message="Please select your gender." />
            </div>

            <div class="lg:col-span-2">
                <x-form.input light name="civil_status" label="Civil status" type="select" required placeholder="Select"
                    :options="[
                        'Single' => 'Single',
                        'Married' => 'Married',
                        'Widowed' => 'Widowed',
                        'Separated' => 'Separated',
                        'Divorced' => 'Divorced',
                    ]" :value="old('civil_status', $formDetails['civil_status'] ?? null)" error-message="Please select your civil status." />
            </div>

            {{-- Row 3: Background (2 cols each on desktop) --}}
            <div class="lg:col-span-2">
                <x-form.input light name="nationality" label="Nationality" required :value="old('nationality', $formDetails['nationality'] ?? null)"
                    placeholder="Filipino" pattern="[A-Za-zÑñ\s\-]{2,50}" maxlength="50"
                    error-message="Enter your nationality using letters only, e.g. Filipino." />
            </div>

            <div class="lg:col-span-2">
                <x-form.input light name="religion" label="Religion (optional)" :value="old('religion', $formDetails['religion'] ?? null)"
                    placeholder="e.g. Roman Catholic" maxlength="50" />
            </div>

            <div class="lg:col-span-2">
                <x-form.input light name="occupation" label="Occupation (optional)" :value="old('occupation', $formDetails['occupation'] ?? null)"
                    placeholder="e.g. Teacher" maxlength="80" />
            </div>
        </div>
    </section>

    {{-- CARD 2: Contact & Current Address --}}
    <section class="{{ $card }} space-y-5">
        <div class="border-b border-gray-100 pb-3 dark:border-gray-800">
            <h3 class="{{ $h3 }}">Contact & current address</h3>
            <p class="{{ $sub }} mt-0.5">Provide your active contact details and the address where you live now.</p>
        </div>

        <div class="grid gap-x-5 gap-y-4 sm:grid-cols-2 lg:grid-cols-6">
            {{-- Row 1: Contact Details (Email + Phone grouped side-by-side) --}}
            <div class="lg:col-span-3">
                <x-form.input light name="email" label="Email address" type="email" :value="old('email', $formDetails['email'] ?? auth()->user()->email)" :disabled="auth()->user()->email !== null"
                    :required="auth()->user()->email === null" placeholder="juan@example.com"
                    error-message="Enter a valid email address, e.g. juan@example.com." />
            </div>

            <div class="lg:col-span-3">
                {{-- prefix="+63": digits only, leading 0 stripped. Mobile numbers are 10 digits starting with 9 --}}
                <x-form.input light name="phone" label="Mobile number" type="tel" required prefix="+63"
                    placeholder="9171234567" :use-old="false" :value="preg_replace(
                        '/^(\+?63|0)/',
                        '',
                        preg_replace('/[^\d+]/', '', (string) old('phone', $formDetails['phone'] ?? '')),
                    )" pattern="9\d{9}" maxlength="10"
                    error-message="Enter a 10-digit mobile number starting with 9, e.g. 9171234567." />
            </div>

            {{-- Row 2: Administrative Location (Province -> City -> Barangay) --}}
            <div class="lg:col-span-2">
                <x-form.input light name="province" label="Province" required :value="old('province', $formDetails['province'] ?? null)" placeholder="e.g. Rizal"
                    maxlength="60" error-message="Enter your province." />
            </div>

            <div class="lg:col-span-2" data-address-after="province">
                <x-form.input light name="city_municipality" label="City / Municipality" required :value="old('city_municipality', $formDetails['city_municipality'] ?? null)"
                    placeholder="e.g. Morong" maxlength="60" error-message="Enter your city or municipality." />
            </div>

            <div class="lg:col-span-2" data-address-after="city_municipality">
                <x-form.input light name="barangay" label="Barangay" required :value="old('barangay', $formDetails['barangay'] ?? null)"
                    placeholder="e.g. San Juan" maxlength="60" error-message="Enter your barangay." />
            </div>

            {{-- Row 3: Street Details + ZIP Code (4 cols + 2 cols) --}}
            <div class="lg:col-span-4" data-address-after="barangay">
                <x-form.input light name="house_number" label="House / Lot / Street / Purok (optional)" :value="old('house_number', $formDetails['house_number'] ?? null)"
                    placeholder="e.g. Blk 4 Lot 12, Purok 3" maxlength="40" />
            </div>

            <div class="lg:col-span-2" data-address-after="barangay">
                <x-form.input light name="zip_code" label="ZIP code" required :value="old('zip_code', $formDetails['zip_code'] ?? null)" placeholder="1960"
                    inputmode="numeric" pattern="\d{4}" maxlength="4"
                    oninput="this.value = this.value.replace(/\D/g, '')"
                    error-message="Enter a 4-digit ZIP code, e.g. 1960." />
            </div>
        </div>
    </section>
</form>