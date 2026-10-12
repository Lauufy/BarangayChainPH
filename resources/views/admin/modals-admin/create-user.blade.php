<div id="createUserModal"
    class="{{ $errors->any() ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center bg-black/70 p-4"
    onclick="if (event.target === this) this.classList.add('hidden')">
    <section
        class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl border border-white/10 bg-[#09172D] text-white shadow-2xl">

        {{-- Modal Header --}}
        <div class="flex items-start justify-between border-b border-white/10 p-5">
            <div>
                <h2 class="text-lg font-semibold">Create an account</h2>
                <p class="mt-1 text-sm text-blue-100/50">
                    Staff and admin accounts receive a unique employee ID that can be used to sign in.
                </p>
            </div>

            <button type="button" onclick="document.getElementById('createUserModal').classList.add('hidden')"
                class="ml-4 text-2xl text-slate-400 hover:text-white" aria-label="Close modal">
                &times;
            </button>
        </div>

        {{-- Create Account Form --}}
        <form action="{{ route('admin.users.store') }}" method="POST" class="grid gap-4 p-5 sm:grid-cols-2">
            @csrf

            <div class="sm:col-span-2">
                <x-feedbacks.form-errors />
            </div>

            <x-form.input name="name" label="Full Name" value="{{ old('name', 'Carlo Admin') }}" required autofocus autocomplete="name"
                placeholder="Enter your full name" data-validate />

            <x-form.input name="email" label="Email Address" type="email" required autocomplete="email"
                placeholder="Enter your email address" data-validate />

            <x-form.input name="role" label="Role" type="select" :options="[
                'staff' => 'Staff / Employee',
                'admin' => 'Administrator',
            ]"
                value="{{ old('role', 'staff') }}" required />

            <x-form.input name="department" label="Department" placeholder="Enter department" />

            <x-form.input name="position" label="Position" placeholder="Enter job position" />

            <x-form.input name="phone" label="Phone Number" type="tel" autocomplete="tel"
                placeholder="Enter phone number" />

            <x-form.input name="office" label="Office (Optional)" placeholder="Enter office" />

            <x-form.input name="date_hired" label="Date Hired (Optional)" type="date"
                max="{{ now()->toDateString() }}" />

            <x-form.input name="password" label="Password" type="password" required autocomplete="new-password"
                placeholder="Enter password" data-validate />

            <x-form.input name="password_confirmation" label="Confirm Password" type="password" required
                autocomplete="new-password" placeholder="Confirm password" data-validate />

            {{-- Modal Footer --}}
            <div class="flex justify-end gap-3 border-t border-white/10 pt-4 sm:col-span-2">
                <button type="button" onclick="document.getElementById('createUserModal').classList.add('hidden')"
                    class="rounded-lg border border-white/10 px-4 py-2.5 text-sm text-slate-300 hover:bg-white/5">
                    Cancel
                </button>

                <x-form.button type="submit" loading-text="Creating...">
                    Create Account
                </x-form.button>
            </div>
        </form>
    </section>
</div>
