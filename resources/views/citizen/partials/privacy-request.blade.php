<section class="mt-8 rounded-lg border border-(--gray-200) p-5 dark:border-(--gray-700)">
    <h2 class="text-lg font-semibold text-(--gray-900) dark:text-(--gray-50)">Your personal data</h2>

    <p class="mt-1 text-sm text-(--gray-600) dark:text-(--gray-300)">
        You may ask to see, correct, or delete the personal data we keep about you.
        Issued permits may be kept as official records.
    </p>

    <x-feedbacks.form-errors />

    <form action="{{ route('citizen.privacy-request') }}" method="POST" class="mt-4 space-y-4">
        @csrf

        <div>
            <label for="type" class="block text-sm font-medium">What do you need?</label>
            <select id="type" name="type" required class="mt-1 w-full rounded-md border p-2">
                <option value="access">See a copy of my data</option>
                <option value="correction">Correct my data</option>
                <option value="erasure">Delete my account and data</option>
            </select>
        </div>

        <div>
            <label for="details" class="block text-sm font-medium">Details (optional)</label>
            <textarea id="details" name="details" rows="3" maxlength="1000"
                      class="mt-1 w-full rounded-md border p-2">{{ old('details') }}</textarea>
        </div>

        <x-form.input
            light
            name="current_password"
            label="Confirm with your password"
            type="password"
            required
            autocomplete="current-password"
        />

        <x-form.button type="submit" color="blue" loading-text="Sending...">
            SEND REQUEST
        </x-form.button>
    </form>
</section>