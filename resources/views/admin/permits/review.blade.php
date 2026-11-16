@extends('layouts.admin')

@section('title', 'Review Permit Application')

@section('content')
    <div class="space-y-4">
        {{-- Show the request title, location in the workflow, and current status. --}}
        <x-admin.detail-header :title="$application->serviceTitle()" :subtitle="$application->reference_number"
            :crumbs="[
                ['label' => 'Permit Management', 'href' => route('admin.permits.index')],
                ['label' => 'Review Application'],
            ]">
            <x-slot:actions>
                <x-tables.badge :variant="$statusVariant">{{ $statusLabel }}</x-tables.badge>
                <a href="{{ route('admin.permits.index') }}"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Back
                </a>
            </x-slot:actions>
        </x-admin.detail-header>

        <div class="flex flex-col gap-4 lg:flex-row">
            {{-- Applicant information and the approval/rejection decision forms. --}}
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

                            <x-form.input light id="rejection_reason" name="rejection_reason" type="textarea"
                                label="Reason for rejection" required minlength="5" maxlength="500" rows="3" />

                            @error('rejection_reason')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </form>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2 sm:items-end">
                            <form method="POST" action="{{ route('admin.permits.approve', $application) }}"
                                class="space-y-2">
                                @csrf

                                <x-form.input light id="expires_at" name="expires_at" type="date" label="Valid until"
                                    required min="{{ now()->addDay()->toDateString() }}" />

                                @error('expires_at')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <div class="flex flex-wrap gap-2">
                                    @foreach ([3 => '3 months', 6 => '6 months', 12 => '1 year'] as $months => $label)
                                        <button type="button"
                                            onclick="setPermitExpiry(this, {{ $months }})"
                                            class="rounded border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700 hover:bg-slate-50">
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>

                                <x-form.button type="submit" color="green" class="w-full">
                                    Approve application
                                </x-form.button>
                            </form>

                            <x-form.button type="submit" variant="danger" form="reject-form" class="w-full">
                                Reject application
                            </x-form.button>
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

            {{-- Service-specific answers, supporting documents, print controls, and blockchain details. --}}
            <aside class="space-y-4 lg:w-96 lg:shrink-0">
                {{-- Request-specific answers and uploaded supporting documents. --}}
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

                {{-- Allow authorized staff to adjust the approved document's print limit. --}}
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
                                <x-form.input light id="print_limit" name="print_limit" type="number"
                                    label="Allowed prints" required max="1000"
                                    min="{{ $application->print_count }}"
                                    :value="old('print_limit', $application->effectivePrintLimit())" />

                                @error('print_limit')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <x-form.button type="submit" color="blue" class="w-full">
                                Update print allowance
                            </x-form.button>
                        </form>
                    </section>
                @endif

                {{-- Reissue an authenticated PDF while retaining the current record until success. --}}
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
                                onsubmit="event.preventDefault(); var form = this; tConfirmation({ title: 'Reissue this document?', text: 'Are you sure you want to reissue this document? The current document will become invalid after the replacement is successfully generated.', confirmLabel: 'Yes, reissue' }).then(function (ok) { if (ok) form.submit(); });">
                                @csrf

                                <x-form.input light id="expires_at" name="expires_at" type="date"
                                    label="Valid until (new document)" required
                                    min="{{ now()->addDay()->toDateString() }}" />

                                @error('expires_at')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <div class="flex flex-wrap gap-2">
                                    @foreach ([3 => '3 months', 6 => '6 months', 12 => '1 year'] as $months => $label)
                                        <button type="button"
                                            onclick="setPermitExpiry(this, {{ $months }})"
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
                {{-- Show or retry the active document's blockchain recording. --}}
                @if ($isApproved && $activeDocuments->isNotEmpty())
                    <section class="admin-card">
                        <h2 class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-900">
                            Blockchain record
                        </h2>

                        <div class="space-y-3 p-4">
                            @if ($currentRecord?->recorded_at && ! $currentRecord->tx_hash)
                                <p class="text-sm text-amber-700">
                                    The permit is valid on-chain, but its transaction hash still needs recovery.
                                </p>

                                <form method="POST"
                                    action="{{ route('admin.permits.blockchain-retry', $application) }}">
                                    @csrf
                                    <x-form.button type="submit" color="blue" class="w-full">
                                        Retry transaction lookup
                                    </x-form.button>
                                </form>
                            @elseif ($currentRecord?->recorded_at)
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
                                    <x-form.button type="submit" color="blue" class="w-full">
                                        Record on blockchain
                                    </x-form.button>
                                </form>
                            @endif
                        </div>
                    </section>
                @endif
            </aside>
        </div>
    </div>
    <script>
        function setPermitExpiry(button, months) {
            const date = new Date();
            const day = date.getDate();

            date.setDate(1);
            date.setMonth(date.getMonth() + months);
            date.setDate(Math.min(day, new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate()));

            button.form.elements.namedItem('expires_at').value = [
                date.getFullYear(),
                String(date.getMonth() + 1).padStart(2, '0'),
                String(date.getDate()).padStart(2, '0'),
            ].join('-');
        }
    </script>
@endsection
