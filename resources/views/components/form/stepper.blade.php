{{--
    Reusable animated stepper: resources/views/components/stepper.blade.php

    Use it anywhere:
        <x-stepper id="apply-stepper" :steps="['Details', 'ID', 'Selfie', 'Review']" :current="1" />

    Change the step from JavaScript (the line fills, the dot pops, the check draws in):
        Stepper.set('apply-stepper', 3);          // by id
        Stepper.set(element, 2);                  // or by element

    Listen for changes:
        document.getElementById('apply-stepper').addEventListener('stepper:change', (e) => e.detail.step);

    Colors follow the theme (light / dark). All styling is driven by the data-state on each step:
    done = green check, now = blue number with a ring, next = outline number.
--}}
@props(['steps' => [], 'current' => 1, 'id' => 'stepper'])

<ol id="{{ $id }}" data-stepper data-current="{{ $current }}" aria-label="Progress"
    style="grid-template-columns: repeat({{ count($steps) }}, minmax(0, 1fr))"
    {{ $attributes->class('grid w-full rounded-lg bg-(--white) p-4 shadow-sm ring-1 ring-(--gray-200) dark:bg-(--gray-800) dark:ring-(--gray-700)') }}>

    @foreach ($steps as $label)
        @php
            $n = $loop->iteration;
            $state = $n < $current ? 'done' : ($n === (int) $current ? 'now' : 'next');
        @endphp

        <li data-step="{{ $n }}" data-state="{{ $state }}" @if ($state === 'now') aria-current="step" @endif
            class="group relative flex min-w-0 flex-col items-center text-center">

            {{-- Line coming into this step: fills from left to right --}}
            @unless ($loop->first)
                <span aria-hidden="true" class="absolute right-1/2 top-3.5 h-0.5 w-full overflow-hidden bg-(--gray-200) dark:bg-(--gray-700)">
                    <span class="block h-full origin-left scale-x-0 bg-(--green-600) transition-transform duration-500 ease-out group-data-[state=done]:scale-x-100 group-data-[state=now]:scale-x-100 motion-reduce:transition-none"></span>
                </span>
            @endunless

            {{-- Dot: number fades out and the check pops in when done --}}
            <span class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2 text-xs font-bold transition-all duration-300 ease-out motion-reduce:transition-none
                group-data-[state=next]:border-(--gray-300) group-data-[state=next]:bg-(--white) group-data-[state=next]:text-(--gray-500) dark:group-data-[state=next]:border-(--gray-600) dark:group-data-[state=next]:bg-(--gray-800)
                group-data-[state=now]:scale-110 group-data-[state=now]:border-(--blue-600) group-data-[state=now]:bg-(--blue-600) group-data-[state=now]:text-(--white) group-data-[state=now]:ring-4 group-data-[state=now]:ring-[color-mix(in_srgb,var(--blue-600)_25%,transparent)]
                group-data-[state=done]:border-(--green-600) group-data-[state=done]:bg-(--green-600) group-data-[state=done]:text-(--white)">
                <span class="transition-opacity duration-200 group-data-[state=done]:opacity-0">{{ $n }}</span>
                <svg class="absolute h-4 w-4 scale-50 opacity-0 transition duration-300 ease-out group-data-[state=done]:scale-100 group-data-[state=done]:opacity-100 motion-reduce:transition-none"
                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 111.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd" />
                </svg>
            </span>

            <p class="mt-2 px-1 text-[11px] font-semibold text-(--gray-500) transition-colors duration-300 group-data-[state=done]:text-(--blue-900) group-data-[state=now]:text-(--blue-900) dark:text-(--gray-400) dark:group-data-[state=done]:text-(--blue-300) dark:group-data-[state=now]:text-(--blue-300) sm:text-xs">{{ $label }}</p>
        </li>
    @endforeach
</ol>

@once
    <script>
        window.Stepper = {
            // Go to a step (1 = first). Steps before it become "done", the others "next".
            set(target, step) {
                const el = typeof target === 'string' ? document.getElementById(target) : target;
                if (!el) return;

                const items = [...el.querySelectorAll('[data-step]')];
                step = Math.max(1, Math.min(items.length, Number(step)));

                items.forEach((item, i) => {
                    const n = i + 1;
                    item.dataset.state = n < step ? 'done' : n === step ? 'now' : 'next';
                    if (n === step) item.setAttribute('aria-current', 'step');
                    else item.removeAttribute('aria-current');
                });

                el.dataset.current = step;
                el.dispatchEvent(new CustomEvent('stepper:change', { detail: { step } }));
            },
        };
    </script>
@endonce