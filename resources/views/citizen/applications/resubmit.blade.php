@extends('layouts.citizen')
@section('title', 'Resubmit application')
@section('content')
    <div class="mx-auto max-w-3xl space-y-5">
        <header class="space-y-2 border-b border-slate-200 pb-4 dark:border-slate-700">
            <a href="{{ route('citizen.applications.show', $application) }}" class="text-sm font-semibold text-blue-800 underline dark:text-blue-300">{{ $application->serviceTitle() }} · {{ $application->reference_number }}</a>
            <h1 class="text-2xl font-bold">Resubmit application</h1>
            <p class="text-sm">Update the {{ count($revision->items) === 1 ? 'item' : 'items' }} below and send your application back for review.</p>
        </header>
        <x-feedbacks.form-errors />
        @if ($revision->message)
            <section class="rounded-md bg-amber-50 p-4 text-sm text-amber-950 dark:bg-amber-950 dark:text-amber-100">
                <h2 class="font-semibold">Message from the reviewer</h2>
                <p class="mt-1 whitespace-pre-line">{{ $revision->message }}</p>
            </section>
        @endif
        <form method="POST" enctype="multipart/form-data" action="{{ route('citizen.resubmissions.submit', $application) }}" class="space-y-5">
            @csrf
            <input type="hidden" name="revision" value="{{ $revision->id }}">
            @foreach ($revision->items as $key => $item)
                @php
                    $field = $fields[$key];
                    $value = old("corrections.$key", $field['value']);
                    $control = 'block w-full rounded-md border border-red-500 px-3 py-2 dark:bg-slate-900 dark:border-red-400';
                @endphp
                <div class="space-y-1.5" data-original="{{ $field['value'] }}">
                    @if ($field['type'] !== 'tel')
                        <label for="field-{{ $key }}" class="font-semibold">{{ $item['label'] }}</label>
                    @endif
                    @if ($field['type'] === 'tel')
                        <x-form.input light raised type="tel" prefix="+63" :label="$item['label']"
                            :id="'field-'.$key" :name="'corrections['.$key.']'" :value="$value" :use-old="false"
                            placeholder="9171234567" :required="$field['required'] ?? true" aria-describedby="note-{{ $key }}" />
                    @elseif ($field['type'] === 'file')
                        <p class="text-sm">Previous upload · {{ $item['old'] ?? 'None' }}</p>
                        <input class="{{ $control }}" id="field-{{ $key }}" name="corrections[{{ $key }}]" type="file" accept=".pdf,.jpg,.jpeg,.png" required aria-describedby="note-{{ $key }}">
                        <small>JPG, PNG or PDF · Up to 10 MB</small>
                    @elseif ($field['type'] === 'select' && empty($field['other']))
                        <select class="{{ $control }}" id="field-{{ $key }}" name="corrections[{{ $key }}]" @required($field['required'] ?? true) aria-describedby="note-{{ $key }}">
                            @if (!in_array($value, $field['options'], true))<option value="" selected>Select {{ strtolower($field['label']) }}</option>@endif
                            @foreach ($field['options'] as $option)<option @selected($option === $value)>{{ $option }}</option>@endforeach
                        </select>
                    @elseif ($field['type'] === 'textarea')
                        <textarea class="{{ $control }}" id="field-{{ $key }}" name="corrections[{{ $key }}]" @required($field['required'] ?? true) aria-describedby="note-{{ $key }}">{{ $value }}</textarea>
                    @else
                        <input class="{{ $control }}" id="field-{{ $key }}" name="corrections[{{ $key }}]" value="{{ $value }}" type="{{ $field['type'] === 'select' ? 'text' : $field['type'] }}" step="{{ $field['step'] ?? '1' }}" @required($field['required'] ?? true) aria-describedby="note-{{ $key }}">
                    @endif
                    <p id="note-{{ $key }}" class="flex gap-2 text-sm text-red-800 dark:text-red-300">
                        <x-lucide-triangle-alert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        <span class="whitespace-pre-line">{{ $item['note'] }}</span>
                    </p>
                    @error("corrections.$key")<p class="text-sm font-semibold text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <details class="rounded-md border border-slate-200 p-3 dark:border-slate-700">
                <summary class="cursor-pointer text-sm font-semibold">View other submitted details</summary>
                <dl class="mt-3 grid gap-3 text-sm text-slate-600 sm:grid-cols-2 dark:text-slate-300">
                    @foreach (array_diff_key($fields, $revision->items) as $field)
                        <div><dt>{{ $field['label'] }}</dt><dd>{{ $field['value'] ?? 'Not provided' }}</dd></div>
                    @endforeach
                </dl>
            </details>
            <button class="rounded-md bg-blue-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-900">Submit corrections</button>
        </form>
        <script @if (app()->bound('csp-nonce')) nonce="{{ app('csp-nonce') }}" @endif>
            (() => {
                const form = document.currentScript.previousElementSibling;
                form.querySelectorAll('[data-original]').forEach(group => {
                    const input = group.querySelector('input, select, textarea');
                    if (input.type === 'file') return;
                    const validate = () => {
                        const value = input.value.trim();
                        const original = group.dataset.original.trim();
                        const previous = input.type === 'tel' ? original.replace(/^(?:\+?63|0)/, '') : original;
                        const unchanged = value === previous ||
                            (input.type === 'number' && previous !== '' && value !== '' && Number(value) === Number(previous));
                        input.setCustomValidity(unchanged ? 'Enter a value different from the previous answer.' : '');
                    };
                    input.addEventListener('input', validate);
                    input.addEventListener('change', validate);
                    validate();
                });
            })();
        </script>
    </div>
@endsection
