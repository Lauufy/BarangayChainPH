<div id="createUserModal"
    class="{{ $errors->any() ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center bg-black/70 p-4"
    onclick="if (event.target === this) this.classList.add('hidden')">
    <section
        class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl border border-white/10 bg-[#f5f5f5] text-white shadow-2xl">

        {{-- Modal Header --}}
        <div class="flex items-start justify-between border-b border-blue-950 p-5">
            <div>
                <h2 class="text-lg font-semibold text-blue-900">Create an account</h2>
                <p class="mt-1 text-sm text-blue-800">
                    Staff and admin accounts receive a unique employee ID that can be used to sign in.
                </p>
            </div>

            <button type="button" onclick="document.getElementById('createUserModal').classList.add('hidden')"
                class="ml-4 text-2xl text-blue-600 hover:text-red-600" aria-label="Close modal">
                &times;
            </button>
        </div>

        {{-- Create Account Form --}}
        <form action="{{ route('admin.users.store') }}" method="POST" class="grid gap-4 p-5 sm:grid-cols-2">
            @csrf

            <div class="sm:col-span-2">
                <x-feedbacks.form-errors />
            </div>

            <x-form.input light name="name" label="Full Name" value="{{ old('name', 'Carlo Admin') }}" required
                autofocus autocomplete="name" placeholder="Enter your full name" data-validate />

            <x-form.input light name="email" label="Email Address" type="email" required autocomplete="email"
                placeholder="Enter your email address" data-validate />

            <x-form.input light name="role" label="Role" type="select" :options="[
                'staff' => 'Staff / Employee',
                'admin' => 'Administrator',
            ]"
                value="{{ old('role', 'staff') }}" required />

            <x-form.input light name="department" label="Department" placeholder="Enter department" />

            <x-form.input light name="position" label="Position" placeholder="Enter job position" />

            <x-form.input light name="phone" label="Phone Number" type="tel" autocomplete="tel"
                placeholder="Enter phone number" />

            <x-form.input light name="office" label="Office (Optional)" placeholder="Enter office" />

            <x-form.input light name="date_hired" label="Date Hired (Optional)" type="date"
                max="{{ now()->toDateString() }}" />

            <x-form.input light name="password" label="Password" type="password" required autocomplete="new-password"
                placeholder="Enter password" data-validate />

            <x-form.input light name="password_confirmation" label="Confirm Password" type="password" required
                autocomplete="new-password" placeholder="Confirm password" data-validate />

            {{-- Modal Footer --}}
            <div class="flex justify-end gap-3 border-t border-blue-800 pt-4 sm:col-span-2">
                <button type="button" onclick="document.getElementById('createUserModal').classList.add('hidden')"
                    class="w-full rounded-lg border bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition-all duration-200 ease-in-out hover:-translate-y-1 hover:bg-red-700 hover:shadow-lg active:translate-y-0">
                    Cancel
                </button>

                <x-form.button color="#010048" hoverColor="#090088" type="submit" loading-text="Creating..." class="w-full">
                    Create Account
                </x-form.button>
            </div>
        </form>
    </section>
</div>
