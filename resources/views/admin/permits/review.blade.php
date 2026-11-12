@extends('layouts.admin')

@use('App\Models\ServiceRequest')

@section('title', 'Review Permit Application')

@section('content')
    @php
        $status = $application->status;
        $isApproved = $status === ServiceRequest::APPROVED;
        $isOpen = in_array($status, [ServiceRequest::PENDING, ServiceRequest::PROCESSING], true);
        $activeDocuments = $application->issuedDocuments->whereNull('revoked_at');
        $currentRecord = $activeDocuments->sortByDesc('version')->first()?->blockchainRecord;

        // Status badge: label, badge colours, dot colour
        [$statusLabel, $badgeClass, $dotClass] = [
            'pending' => ['Pending review', 'border-amber-200 bg-amber-50 text-amber-800', 'bg-amber-500'],
            'processing' => ['Processing', 'border-blue-200 bg-blue-50 text-blue-800', 'bg-blue-500'],
            'approved' => ['Approved', 'border-green-200 bg-green-50 text-green-800', 'bg-green-500'],
            'rejected' => ['Rejected', 'border-red-200 bg-red-50 text-red-800', 'bg-red-500'],
            'cancelled' => ['Cancelled', 'border-slate-200 bg-slate-50 text-slate-700', 'bg-slate-400'],
        ][$status] ?? [ucfirst($status), '', ''];

        // Applicant details (empty values are skipped)
        $details = array_filter(
            [
                'Full name' => $application->applicant_name,
                'Age' => $application->age,
                'Civil status' => $application->civil_status,
                'Contact number' => $application->contact_number,
                'Email' => $application->user->email,
                'Date submitted' => $application->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A'),
                'Permit number' => $application->permit_number,
                'Prints remaining' => $isApproved
                    ? $application->printsRemaining() . ' of ' . $application->effectivePrintLimit()
                    : null,
            ],
            fn($value) => filled($value),
        );

        // Service-specific answers from step 2, labelled from config/barangay_services.php
        $fieldLabels = collect($application->serviceConfig()['fields'] ?? [])->pluck('label', 'key');

        $requestDetails = ['Purpose' => $application->purpose];

        foreach ($application->details ?? [] as $key => $value) {
            $requestDetails[$fieldLabels[$key] ?? ucfirst(str_replace('_', ' ', $key))] = $value;
        }

        $requestDetails['Cancellation reason'] = $application->cancellation_reason;
        $requestDetails = array_filter($requestDetails, fn($value) => filled($value));
    @endphp

    <div class="space-y-4">
        {{-- Header --}}
        <header
            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route('admin.permits.index') }}"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50"
                    aria-label="Back to permit management" title="Back to permit management">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </a>

                <div class="min-w-0">
                    <h1 class="truncate text-xl font-bold leading-tight text-slate-900">
                        {{ $application->serviceTitle() }}
                    </h1>
                    <p class="truncate text-xs text-slate-500">{{ $application->reference_number }}</p>
                </div>
            </div>

            <span
                class="{{ $badgeClass }} inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold">
                <span class="{{ $dotClass }} h-1.5 w-1.5 rounded-full"></span>
                {{ $statusLabel }}
            </span>
        </header>

        <div class="flex flex-col gap-4 lg:flex-row">
            {{-- Left: applicant details + decision --}}
            <section class="admin-card min-w-0 flex-1">
                <h2 class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-900">Applicant details</h2>

                <dl class="grid gap-x-4 gap-y-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($details as $label => $value)
                        <div>
                            <dt class="text-[11px] font-semibold uppercase text-slate-500">{{ $label }}</dt>
                            <dd class="wrap-break-word text-sm text-slate-900">{{ $value }}</dd>
                        </div>
                    @endforeach

                    <div class="sm:col-span-2 xl:col-span-3">
                        <dt class="text-[11px] font-semibold uppercase text-slate-500">Address</dt>
                        <dd class="wrap-break-word text-sm text-slate-900">{{ $application->address }}</dd>
                    </div>
                </dl>

                {{-- Decision --}}
                @if ($isOpen)
                    <div class="border-t border-slate-200 bg-slate-50/60 p-4">
                        <h3 class="text-sm font-semibold text-slate-900">Decision</h3>
                        <p class="mt-1 text-xs text-slate-500">Approve this request or reject it with a reason.</p>

                        {{-- The reject button below submits this form through its "form" attribute --}}
                        <form id="reject-form" method="POST" action="{{ route('admin.permits.reject', $application) }}"
                            class="mt-3 space-y-1.5">
                            @csrf

                            <label for="rejection_reason" class="block text-xs font-semibold text-slate-700">
                                Reason for rejection
                            </label>
                            <textarea id="rejection_reason" name="rejection_reason" required minlength="5" maxlength="500" rows="3"
                                class="block w-full rounded-md border border-slate-300 bg-white p-3 text-sm text-slate-900 outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20">{{ old('rejection_reason') }}</textarea>

                            @error('rejection_reason')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </form>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2 sm:items-end">
                            <form method="POST" action="{{ route('admin.permits.approve', $application) }}"
                                class="space-y-2">
                                @csrf

                                <label for="expires_at" class="block text-xs font-semibold text-slate-700">
                                    Valid until
                                </label>
                                <input id="expires_at" name="expires_at" type="date" required
                                    min="{{ now()->addDay()->toDateString() }}" value="{{ old('expires_at') }}"
                                    class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">

                                @error('expires_at')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <div class="flex flex-wrap gap-2">
                                    @foreach ([3 => '3 months', 6 => '6 months', 12 => '1 year'] as $months => $label)
                                        <button type="button"
                                            onclick="const d = new Date(); d.setMonth(d.getMonth() + {{ $months }}); this.form.expires_at.value = d.toLocaleDateString('en-CA');"
                                            class="rounded border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700 hover:bg-slate-50">
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>

                                <button
                                    class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                                    Approve application
                                </button>
                            </form>

                            <button type="submit" form="reject-form"
                                class="w-full rounded-md bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                                Reject application
                            </button>
                        </div>
                    @elseif ($application->rejection_reason)
                        <div class="border-t border-slate-200 bg-slate-50/60 p-4">
                            <h3 class="text-sm font-semibold text-slate-900">Decision</h3>

                            <div class="mt-3 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                                <p class="text-[11px] font-bold uppercase">Rejection reason</p>
                                <p class="mt-0.5">{{ $application->rejection_reason }}</p>
                            </div>
                        </div>
                @endif
            </section>

            {{-- Right: request details, uploads, print access, reissue --}}
            <aside class="space-y-4 lg:w-96 lg:shrink-0">
                <section class="admin-card">
                    <h2 class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-900">Request details
                    </h2>

                    <dl class="space-y-3 p-4">
                        @forelse ($requestDetails as $label => $value)
                            <div>
                                <dt class="text-[11px] font-semibold uppercase text-slate-500">{{ $label }}</dt>
                                <dd class="wrap-break-word text-sm text-slate-900">{{ $value }}</dd>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No additional details.</p>
                        @endforelse
                    </dl>
                </section>

                {{-- Uploaded requirements (opened through a permission-checked route) --}}
                <section class="admin-card">
                    <h2 class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-900">
                        Submitted documents
                    </h2>

                    <ul class="divide-y divide-slate-100">
                        @forelse ($application->documents as $document)
                            <li class="flex items-center justify-between gap-3 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-900">{{ $document->label }}</p>
                                    <p class="truncate text-xs text-slate-500">
                                        {{ $document->original_name }} · {{ number_format($document->size / 1024) }} KB
                                    </p>
                                </div>

                                <a href="{{ route('admin.permits.documents.show', [$application, $document]) }}"
                                    target="_blank" rel="noopener"
                                    class="shrink-0 rounded-md border border-[#17325c] px-3 py-1.5 text-xs font-semibold text-[#17325c] hover:bg-slate-50">
                                    View
                                </a>
                            </li>
                        @empty
                            <li class="px-4 py-3 text-sm text-slate-500">No documents were uploaded.</li>
                        @endforelse
                    </ul>
                </section>

                @if ($isApproved && auth()->user()->hasPermission('permits.print-limit'))
                    <section class="admin-card">
                        <div class="border-b border-slate-200 px-4 py-3">
                            <h2 class="text-sm font-semibold text-slate-900">Print access</h2>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Increase the total print allowance when helping a citizen print their approved certificate.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('admin.permits.print-limit.update', $application) }}"
                            class="space-y-3 p-4">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label for="print_limit" class="block text-xs font-semibold text-slate-700">Allowed
                                    prints</label>
                                <input id="print_limit" name="print_limit" type="number" required max="1000"
                                    min="{{ $application->print_count }}"
                                    value="{{ old('print_limit', $application->effectivePrintLimit()) }}"
                                    class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-[#17325c] focus:ring-2 focus:ring-[#17325c]/20">

                                @error('print_limit')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <button
                                class="w-full rounded-md bg-[#17325c] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#10244a]">
                                Update print allowance
                            </button>
                        </form>
                    </section>
                @endif

                @if ($isApproved && $activeDocuments->isNotEmpty())
                    <section class="admin-card">
                        <div class="border-b border-slate-200 px-4 py-3">
                            <h2 class="text-sm font-semibold text-slate-900">Document authentication</h2>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Generate a replacement authenticated PDF while preserving the previous document record.
                            </p>
                        </div>

                        <div class="space-y-3 p-4">
                            <p class="text-xs text-slate-600">
                                Active document:
                                <span class="font-semibold text-slate-900">Version
                                    {{ $activeDocuments->max('version') }}</span>
                            </p>

                            <form method="POST" action="{{ route('admin.permits.reissue', $application) }}"
                                class="space-y-2"
                                onsubmit="return confirm('Are you sure you want to reissue this document? The current document will become invalid after the replacement is successfully generated.');">
                                @csrf

                                <label for="expires_at" class="block text-xs font-semibold text-slate-700">
                                    Valid until (new document)
                                </label>
                                <input id="expires_at" name="expires_at" type="date" required
                                    min="{{ now()->addDay()->toDateString() }}" value="{{ old('expires_at') }}"
                                    class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-[#17325c] focus:ring-2 focus:ring-[#17325c]/20">

                                @error('expires_at')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <div class="flex flex-wrap gap-2">
                                    @foreach ([3 => '3 months', 6 => '6 months', 12 => '1 year'] as $months => $label)
                                        <button type="button"
                                            onclick="const d = new Date(); d.setMonth(d.getMonth() + {{ $months }}); this.form.expires_at.value = d.toLocaleDateString('en-CA');"
                                            class="rounded border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700 hover:bg-slate-50">
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>

                                <button type="submit"
                                    class="w-full rounded-md border border-[#17325c] bg-white px-4 py-2.5 text-sm font-semibold text-[#17325c] hover:bg-slate-50">
                                    Reissue document
                                </button>
                            </form>
                        </div>
                    </section>
                @endif
                {{-- Blockchain record of the active document --}}
                @if ($isApproved && $activeDocuments->isNotEmpty())
                    <section class="admin-card">
                        <h2 class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-900">
                            Blockchain record
                        </h2>

                        <div class="space-y-3 p-4">
                            @if ($currentRecord?->recorded_at)
                                <p class="text-sm text-emerald-700">
                                    Recorded on
                                    {{ $currentRecord->recorded_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}.
                                </p>
                            @else
                                <p class="text-sm text-amber-700">Not recorded on the blockchain yet.</p>

                                @if ($currentRecord?->error)
                                    <p class="text-xs text-slate-500">The last attempt failed. You can try again.</p>
                                @endif

                                <form method="POST"
                                    action="{{ route('admin.permits.blockchain-retry', $application) }}">
                                    @csrf
                                    <button
                                        class="w-full rounded-md bg-[#17325c] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#10244a]">
                                        Record on blockchain
                                    </button>
                                </form>
                            @endif
                        </div>
                    </section>
                @endif
            </aside>
        </div>
    </div>
@endsection
