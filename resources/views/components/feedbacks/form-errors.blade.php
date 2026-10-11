
@props([
    'title' => 'Please review the following errors:',
    'bag' => 'default',
])

@if ($errors->getBag($bag)->any())
    <div
        role="alert"
        aria-labelledby="form-error-title"
        aria-live="assertive"
        {{ $attributes->merge([
            'class' => 'mb-5 rounded-xl border border-red-400/25 bg-red-500/[0.07] p-4 sm:p-5'
        ]) }}
    >
        <div class="flex items-start gap-3">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-red-400/10 text-red-300">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="size-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 8v4" />
                    <path d="M12 16h.01" />
                </svg>
            </div>

            <div class="min-w-0 flex-1">
                <h2
                    id="form-error-title"
                    class="text-sm font-semibold leading-5 text-red-200"
                >
                    {{ $title }}
                </h2>

                <ul class="mt-2 space-y-2">
                    @foreach ($errors->getBag($bag)->all() as $message)
                        <li class="flex items-start gap-2 text-sm leading-5 text-red-200/80">
                            <span class="mt-2 size-1 shrink-0 rounded-full bg-red-300/70" aria-hidden="true"></span>
                            <span class="min-w-0 wrap-break-word">{{ $message }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
