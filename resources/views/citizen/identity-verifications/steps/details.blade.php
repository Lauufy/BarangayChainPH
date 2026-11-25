{{-- STEP 1: Personal information --}}
<form id="details-form" method="POST" action="{{ route('citizen.identity-verification.details') }}" class="space-y-3" data-validate>
    @csrf

    <div>
        <h2 class="{{ $h2 }}">Your details</h2>
        <p class="{{ $sub }}">
            Your name comes from your account and can't be changed here.
            @if (!auth()->user()->email)
                Please add your email address.
            @endif
        </p>
    </div>

    <section class="{{ $card }}">
        <h3 class="{{ $h3 }}">Personal information</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Name comes from the account, so it's read-only --}}
            <x-form.input light name="name" label="Full name" :value="auth()->user()->name" readonly autocomplete="name" />

            {{-- Email can only be added when the account has none (SMS sign-up) --}}
            <x-form.input light name="email" label="Email" type="email" :value="old('email', $formDetails['email'] ?? auth()->user()->email)" :disabled="auth()->user()->email !== null"
                :required="auth()->user()->email === null" placeholder="juan@example.com"
                error-message="Enter a valid email address, e.g. juan@example.com." />

            <x-form.input light name="date_of_birth" label="Date of birth" type="date" required :value="old('date_of_birth', $formDetails['date_of_birth'] ?? null)"
                min="1900-01-01" max="{{ now()->subYears(18)->format('Y-m-d') }}" autocomplete="bday"
                error-message="Select your date of birth. You must be at least 18 years old." />

            <x-form.input light name="gender" label="Gender" type="select" required placeholder="Select"
                :options="['Male' => 'Male', 'Female' => 'Female']" :value="old('gender', $formDetails['gender'] ?? null)" error-message="Please select your gender." />

            <x-form.input light name="civil_status" label="Civil status" type="select" required placeholder="Select"
                :options="[
                    'Single' => 'Single',
                    'Married' => 'Married',
                    'Widowed' => 'Widowed',
                    'Separated' => 'Separated',
                    'Divorced' => 'Divorced',
                ]" :value="old('civil_status', $formDetails['civil_status'] ?? null)" error-message="Please select your civil status." />

            <x-form.input light name="place_of_birth" label="Place of birth" required :value="old('place_of_birth', $formDetails['place_of_birth'] ?? null)"
                placeholder="City / Municipality, Province" maxlength="100"
                error-message="Enter your place of birth, e.g. Morong, Rizal." />

            <x-form.input light name="nationality" label="Nationality" required :value="old('nationality', $formDetails['nationality'] ?? null)"
                placeholder="Filipino" pattern="[A-Za-zÑñ\s\-]{2,50}" maxlength="50"
                error-message="Enter your nationality using letters only, e.g. Filipino." />

            <x-form.input light name="religion" label="Religion (optional)" :value="old('religion', $formDetails['religion'] ?? null)"
                placeholder="e.g. Roman Catholic" maxlength="50" />

            <x-form.input light name="occupation" label="Occupation (optional)" :value="old('occupation', $formDetails['occupation'] ?? null)"
                placeholder="e.g. Teacher" maxlength="80" />
        </div>
    </section>

    <section class="{{ $card }}">
        <h3 class="{{ $h3 }}">Address and contact</h3>
        <p class="{{ $sub }} mt-1">Use the address where you live now.</p>
        <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-form.input light name="province" label="Province" required :value="old('province', $formDetails['province'] ?? null)" placeholder="e.g. Rizal"
                maxlength="60" error-message="Enter your province." />

            <div data-address-after="province">
                <x-form.input light name="city_municipality" label="City / Municipality" required :value="old('city_municipality', $formDetails['city_municipality'] ?? null)"
                    placeholder="e.g. Morong" maxlength="60" error-message="Enter your city or municipality." />
            </div>

            <div data-address-after="city_municipality">
                <x-form.input light name="barangay" label="Barangay" required :value="old('barangay', $formDetails['barangay'] ?? null)" placeholder="e.g. San Juan"
                    maxlength="60" error-message="Enter your barangay." />
            </div>

            <div data-address-after="barangay">
                <x-form.input light name="house_number" label="House number (optional)" :value="old('house_number', $formDetails['house_number'] ?? null)"
                    placeholder="e.g. 12-B, Purok 3" maxlength="40" />
            </div>

            <div data-address-after="barangay">
                <x-form.input light name="zip_code" label="ZIP code" required :value="old('zip_code', $formDetails['zip_code'] ?? null)" placeholder="1960"
                    inputmode="numeric" pattern="\d{4}" maxlength="4" oninput="this.value = this.value.replace(/\D/g, '')"
                    error-message="Enter a 4-digit ZIP code, e.g. 1960." />
            </div>

            {{-- prefix="+63": digits only, leading 0 stripped. Mobile numbers are 10 digits starting with 9 --}}
            <x-form.input light name="phone" label="Phone number" type="tel" required prefix="+63"
                placeholder="9171234567" :use-old="false" :value="preg_replace('/^(\+?63|0)/', '', preg_replace('/[^\d+]/', '', (string) old('phone', $formDetails['phone'] ?? '')))" pattern="9\d{9}" maxlength="10"
                error-message="Enter a 10-digit mobile number starting with 9, e.g. 9171234567." />
        </div>
    </section>
</form>


