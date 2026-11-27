{{--
    Reusable stepper: resources/views/components/stepper.blade.php

    Use it anywhere:
        <x-stepper id="setup-stepper" title="Setup" :steps="['Account', 'Profile', 'Upload', 'Done']" :current="1" />

    Change the step from JavaScript (the pills fill, the counter and labels update):
        Stepper.set('setup-stepper', 3);                  // by id
        Stepper.set(element, 2);                          // or by element

    Change the title from JavaScript:
        Stepper.title('setup-stepper', 'Create account');

    Listen for changes:
        document.getElementById('setup-stepper').addEventListener('stepper:change', (e) => e.detail.step);

    Colors follow the theme (light / dark). Pills: light = blue-900, dark = celadon-500
    (done / now = solid, next = 20% tint). Labels: done / now = dark text, next = gray text.
--}}
@props(['steps' => [], 'current' => 1, 'id' => 'stepper', 'title' => 'Setup'])

@php
    $total = max(count($steps), 1);
    $current = max(1, min($total, (int) $current));
@endphp

<div id="{{ $id }}" data-stepper data-current="{{ $current }}" data-total="{{ $total }}"
    {{ $attributes->class('w-full') }}>

    {{-- Header: title left, "Step X of N" right --}}
    <div class="flex items-center justify-between">
        <h3 data-stepper-title class="text-base font-semibold text-(--gray-900) dark:text-(--white)">{{ $title }}</h3>
        <span class="text-xs text-(--gray-500) dark:text-(--gray-400)">
            Step <span data-stepper-current>{{ $current }}</span> of {{ $total }}
        </span>
    </div>

    {{-- Segmented progress: one pill per step --}}
    <div class="mt-4 flex w-full gap-1.5"
        role="progressbar" aria-label="Progress" aria-valuemin="1" aria-valuemax="{{ $total }}" aria-valuenow="{{ $current }}">
        @foreach ($steps as $label)
            @php $n = $loop->iteration; @endphp
            <span data-seg="{{ $n }}" data-state="{{ $n <= $current ? 'filled' : 'empty' }}"
                class="h-1.5 flex-1 rounded-full transition-colors duration-300 motion-reduce:transition-none
                    bg-[color-mix(in_srgb,var(--blue-900)_50%,transparent)]
                    data-[state=filled]:bg-(--blue-900)
                    dark:bg-[color-mix(in_srgb,var(--celadon-500)_20%,transparent)]
                    dark:data-[state=filled]:bg-(--celadon-500)"></span>
        @endforeach
    </div>

    {{-- Labels --}}
    <ol class="mt-6 grid" style="grid-template-columns: repeat({{ $total }}, minmax(0, 1fr))">
        @foreach ($steps as $label)
            @php
                $n = $loop->iteration;
                $state = $n < $current ? 'done' : ($n === $current ? 'now' : 'next');
            @endphp

            <li data-step="{{ $n }}" data-state="{{ $state }}" @if ($state === 'now') aria-current="step" @endif
                class="group min-w-0 truncate px-1 text-center text-xs font-medium transition-colors duration-300
                    text-(--gray-500) dark:text-(--gray-400)
                    data-[state=done]:text-(--gray-900) data-[state=now]:text-(--gray-900)
                    dark:data-[state=done]:text-(--white) dark:data-[state=now]:text-(--white)
                    sm:text-sm">
                {{ $label }}
            </li>
        @endforeach
    </ol>
</div>

@once
    <script @if (app()->bound('csp-nonce')) nonce="{{ app('csp-nonce') }}" @endif>
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

                el.querySelectorAll('[data-seg]').forEach((seg, i) => {
                    seg.dataset.state = i + 1 <= step ? 'filled' : 'empty';
                });

                const counter = el.querySelector('[data-stepper-current]');
                if (counter) counter.textContent = step;

                const bar = el.querySelector('[role="progressbar"]');
                if (bar) bar.setAttribute('aria-valuenow', step);

                el.dataset.current = step;
                el.dispatchEvent(new CustomEvent('stepper:change', { detail: { step } }));
            },

            // Change the title text: Stepper.title('setup-stepper', 'Create account')
            title(target, text) {
                const el = typeof target === 'string' ? document.getElementById(target) : target;
                const heading = el?.querySelector('[data-stepper-title]');
                if (heading) heading.textContent = text;
            },
        };
    </script>
@endonce