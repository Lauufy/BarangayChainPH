@extends('layouts.admin')

@use('App\Models\ServiceRequest')

@section('title', 'Review Permit Application')

@section('content')
    @php
        $isApproved = $application->status === ServiceRequest::APPROVED;
        $activeDocuments = $application->issuedDocuments->whereNull('revoked_at');

        [$badgeClass, $dotClass] = [
            'pending' => ['border-amber-200 bg-amber-50 text-amber-800', 'bg-amber-500'],
            'processing' => ['border-blue-200 bg-blue-50 text-blue-800', 'bg-blue-500'],
            'approved' => ['border-green-200 bg-green-50 text-green-800', 'bg-green-500'],
            'rejected' => ['border-red-200 bg-red-50 text-red-800', 'bg-red-500'],
            'cancelled' => ['border-slate-200 bg-slate-50 text-slate-700', 'bg-slate-400'],
        ][$application->status] ?? ['', ''];

        $details = [];

        if (filled($application->applicant_name)) {
            $details['Full name'] = $application->applicant_name;
        }
        if (filled($application->age)) {
            $details['Age'] = $application->age;
        }
        if (filled($application->user->email)) {
            $details['Email'] = $application->user->email;
        }

        $details['Date submitted'] = $application->created_at
            ->timezone(config('app.timezone'))
            ->format('M j, Y g:i A');

        if (filled($application->permit_number)) {
            $details['Permit number'] = $application->permit_number;
        }
        if ($isApproved) {
            $details['Prints remaining'] = $application->printsRemaining().' of '.$application->effectivePrintLimit();
        }

        $requestDetails = array_filter([
            'Purpose' => $application->purpose,
            'Cancellation reason' => $application->cancellation_reason,
        ], fn($value) => filled($value));
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
                        {{ match ($application->service_type) {
                            ServiceRequest::BARANGAY_CLEARANCE => 'Barangay clearance',
                            ServiceRequest::CERTIFICATE_OF_RESIDENCY => 'Certificate of residency',
                            default => 'Certificate of indigency',
                        } }}
                    </h1>
                    <p class="truncate text-xs text-slate-500">{{ $application->reference_number }}</p>
                </div>
            </div>

            <span
                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold capitalize {{ $badgeClass }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
                {{ $application->status === ServiceRequest::PENDING ? 'Pending review' : $application->status }}
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
                @if ($application->status === ServiceRequest::PENDING)
                    <div class="border-t border-slate-200 bg-slate-50/60 p-4">
                        <h3 class="text-sm font-semibold text-slate-900">Decision</h3>
                        <p class="mt-1 text-xs text-slate-500">Approve this request or reject it with a reason.</p>

                        {{-- The reject form uses "contents" so its fields share the grid with the approve button. --}}
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <form method="POST" action="{{ route('admin.permits.reject', $application) }}"
                                class="contents">
                                @csrf

                                <div class="order-1 space-y-1.5 sm:col-span-2 sm:row-start-1">
                                    <label for="rejection_reason" class="block text-xs font-semibold text-slate-700">Reason
                                        for rejection</label>
                                    <textarea id="rejection_reason" name="rejection_reason" required minlength="5" maxlength="500" rows="3"
                                        class="block w-full rounded-md border border-slate-300 bg-white p-3 text-sm text-slate-900 outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20">{{ old('rejection_reason') }}</textarea>
                                    @error('rejection_reason')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <button
                                    class="order-3 w-full rounded-md bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 sm:col-start-2 sm:row-start-2">
                                    Reject application
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.permits.approve', $application) }}"
                                class="order-2 sm:col-start-1 sm:row-start-2">
                                @csrf
                                <button
                                    class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                                    Approve application
                                </button>
                            </form>
                        </div>
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

            {{-- Right: request details + print access --}}
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
                                <span class="font-semibold text-slate-900">Version {{ $activeDocuments->max('version') }}</span>
                            </p>

                            <form method="POST" action="{{ route('admin.permits.reissue', $application) }}"
                                onsubmit="return confirm('Are you sure you want to reissue this document? The current document will become invalid after the replacement is successfully generated.');">
                                @csrf

                                <button type="submit"
                                    class="w-full rounded-md border border-[#17325c] bg-white px-4 py-2.5 text-sm font-semibold text-[#17325c] hover:bg-slate-50">
                                    Reissue document
                                </button>
                            </form>
                        </div>
                    </section>
                @endif
            </aside>
        </div>
    </div>
@endsection