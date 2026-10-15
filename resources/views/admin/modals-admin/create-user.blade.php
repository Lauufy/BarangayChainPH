<div id="createUserModal"
    class="{{ $errors->any() ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
    onclick="if (event.target === this) this.classList.add('hidden')">
    <section
        class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl border border-slate-200 bg-white text-slate-900 shadow-2xl">

        {{-- Modal Header --}}
        <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white p-5">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#17325c] text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path
                            d="M15 19c0-2.2-2.7-4-6-4s-6 1.8-6 4M9 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM19 8v6M22 11h-6" />
                    </svg>
                </span>

                <div>
                    <h2 class="text-lg font-semibold leading-tight text-slate-900">Create an account</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Staff and admin accounts receive a unique employee ID that can be used to sign in.
                    </p>
                </div>
            </div>

            <button type="button" onclick="document.getElementById('createUserModal').classList.add('hidden')"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                aria-label="Close modal">
                &times;
            </button>
        </div>

        {{-- Create Account Form --}}
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf

            <div class="space-y-6 p-5">
                <x-feedbacks.form-errors />

                {{-- Account --}}
                <fieldset>
                    <legend class="text-xs font-bold uppercase tracking-wide text-[#17325c]">Account</legend>

                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <x-form.input light name="name" label="Full Name" value="{{ old('name', 'Carlo Admin') }}"
                            required autofocus autocomplete="name" placeholder="Enter your full name" data-validate />

                        <x-form.input light name="email" label="Email Address" type="email" required
                            autocomplete="email" placeholder="Enter your email address" data-validate />

                        <x-form.input light name="role" label="Role" type="select" :options="[
                            'staff' => 'Staff / Employee',
                            'admin' => 'Administrator',
                        ]"
                            value="{{ old('role', 'staff') }}" required />

                        <x-form.input light name="phone" label="Phone Number" type="tel" autocomplete="tel"
                            placeholder="Enter phone number" />
                    </div>
                </fieldset>

                {{-- Employment --}}
                <fieldset class="border-t border-slate-100 pt-5">
                    <legend class="text-xs font-bold uppercase tracking-wide text-[#17325c]">Employment</legend>

                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <x-form.input light name="department" label="Department" placeholder="Enter department" />

                        <x-form.input light name="position" label="Position" placeholder="Enter job position" />

                        <x-form.input light name="office" label="Office (Optional)" placeholder="Enter office" />

                        <x-form.input light name="date_hired" label="Date Hired (Optional)" type="date"
                            max="{{ now()->toDateString() }}" />
                    </div>
                </fieldset>

                {{-- Security --}}
                <fieldset class="border-t border-slate-100 pt-5">
                    <legend class="text-xs font-bold uppercase tracking-wide text-[#17325c]">Security</legend>

                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <div class="grid">
                            <x-form.input light name="password" label="Password" type="password" required
                                autocomplete="new-password" placeholder="Enter password" data-validate />
                            <x-feedbacks.password-requirement />
                        </div>

                        <x-form.input light name="password_confirmation" label="Confirm Password" type="password"
                            required autocomplete="new-password" placeholder="Confirm password" data-validate />
                    </div>
                </fieldset>
            </div>

            {{-- Modal Footer --}}
            <div class="sticky bottom-0 border-t border-slate-200 bg-slate-50 p-4">
                <div class="grid grid-cols-2 gap-3 sm:ml-auto sm:w-96">
                    <button type="button" onclick="document.getElementById('createUserModal').classList.add('hidden')"
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                        Cancel
                    </button>

                    <x-form.button color="#010048" hoverColor="#090088" type="submit" loading-text="Creating..."
                        class="w-full">
                        Create Account
                    </x-form.button>
                </div>
            </div>
        </form>
    </section>
</div>
