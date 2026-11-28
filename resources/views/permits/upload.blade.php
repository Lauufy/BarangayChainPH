@extends('layouts.permit-verification')

@section('title', 'Upload a permit')

@section('content')
    <form method="POST" action="{{ route('permits.verify.upload') }}" enctype="multipart/form-data"
        class="space-y-6 rounded-xl border border-slate-200 bg-white p-5 sm:p-7 dark:border-slate-800 dark:bg-slate-900">
        @csrf

        {{-- Permit number / QR reference --}}
        <x-form.input name="ref" id="ref" label="Permit number or QR reference" type="text" :value="$reference"
            :light="true" :use-old="false" required maxlength="64" :readonly="\Illuminate\Support\Str::isUuid($reference)" :aria-invalid="isset($fieldErrors['ref']) ? 'true' : null"
            :aria-describedby="'ref-help' . (isset($fieldErrors['ref']) ? ' ref-error' : '')">
            <p id="ref-help" class="mt-2 text-sm text-slate-600 dark:text-slate-300">Use the Permit Number or Document
                Number printed on the permit.</p>
            @if (isset($fieldErrors['ref']))
                <p id="ref-error" role="alert" class="mt-1 text-sm font-medium text-red-800 dark:text-red-300">
                    {{ $fieldErrors['ref'][0] }}</p>
            @endif
        </x-form.input>

        {{-- Permit file --}}
        <div class="space-y-2">
            <x-form.upload-docu name="permit" id="permit" label="Permit file"
                accept="application/pdf,image/jpeg,image/png" :maxMb="10" text="Choose a file or drop it here" compact
                :preview-pdf="true" required :aria-invalid="isset($fieldErrors['permit']) ? 'true' : null" :aria-describedby="'permit-help' . (isset($fieldErrors['permit']) ? ' permit-error' : '')" />
            <p id="permit-help" class="text-sm text-slate-600 dark:text-slate-300">Use the original PDF. JPEG and PNG scans
                can be checked for field differences.</p>
            @if (isset($fieldErrors['permit']))
                <p id="permit-error" role="alert" class="text-sm font-medium text-red-800 dark:text-red-300">
                    {{ $fieldErrors['permit'][0] }}</p>
            @endif
        </div>

        <button type="submit"
            class="inline-flex w-full items-center justify-center rounded-md bg-[#17325c] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#244778] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 sm:w-auto">
            Verify uploaded file
        </button>
    </form>
@endsection
