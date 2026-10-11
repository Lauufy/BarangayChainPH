@props([
    'title' => 'Please fix the following:',
    'bag' => 'default',
])

@if ($errors->getBag($bag)->any())
    <div role="alert"
        {{ $attributes->merge(['class' => 'mb-[clamp(0.75rem,2vh,1rem)] rounded-lg border border-red-400/30 bg-red-400/10 px-4 py-[clamp(0.5rem,1vh,0.625rem)]']) }}>

        <p class="text-[clamp(0.7rem,0.9vw,0.875rem)] font-medium text-red-300">
            {{ $title }}
        </p>

        <ul class="mt-2 list-disc space-y-1 pl-5 text-[clamp(0.65rem,0.85vw,0.875rem)] text-red-200/80">
            @foreach ($errors->getBag($bag)->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif