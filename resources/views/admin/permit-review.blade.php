@extends('layouts.admin')

@section('title', 'Review Permit Application')

@section('content')
    <div class="space-y-6">
        <div>
            <a href="{{ route('admin.permits.index') }}" class="text-sm font-semibold text-[#17325c] hover:underline">
                &larr; Back to permit management
            </a>
            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Submitted application</p>
                    <h1 class="mt-2 text-2xl font-bold text-slate-900">
                        {{ match ($application->service_type) {
                            \App\Models\ServiceRequest::BARANGAY_CLEARANCE => 'Barangay clearance',
                            \App\Models\ServiceRequest::CERTIFICATE_OF_RESIDENCY => 'Certificate of residency',
                            default => 'Certificate of indigency',
                        } }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $application->reference_number }}</p>
                </div>
                <span @class([
                    'inline-flex w-fit items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold capitalize',
                    'border-amber-200 bg-amber-50 text-amber-800' => $application->status === 'pending',
                    'border-blue-200 bg-blue-50 text-blue-800' => $application->status === 'processing',
                    'border-green-200 bg-green-50 text-green-800' => $application->status === 'approved',
                    'border-red-200 bg-red-50 text-red-800' => $application->status === 'rejected',
                    'border-slate-200 bg-slate-50 text-slate-700' => $application->status === 'cancelled',
                ])>
                    @if ($application->status === 'pending')
                        <span class="flex h-4 w-4 items-center justify-center rounded-full bg-amber-200 text-amber-800"
                            aria-hidden="true">
                            <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="3">
                                <path stroke-linecap="round" d="M12 7v5l3 2" />
                            </svg>
                        </span>
                    @endif
                    {{ $application->status }}
                </span>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="admin-card">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="font-semibold text-slate-900">Applicant details</h2>
                </div>
                <dl class="grid gap-4 p-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">Full name</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $application->applicant_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">Age</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $application->age }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">Email</dt>
                        <dd class="mt-1 break-words text-sm text-slate-900">{{ $application->user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">Date submitted</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $application->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</dd>
                    </div>
                    @if ($application->permit_number)
                        <div>
                            <dt class="text-xs font-semibold uppercase text-slate-500">Permit number</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $application->permit_number }}</dd>
                        </div>
                    @endif
                    @if ($application->status === \App\Models\ServiceRequest::APPROVED)
                        <div>
                            <dt class="text-xs font-semibold uppercase text-slate-500">Prints remaining</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">
                                {{ $application->printsRemaining() }}
                                of {{ $application->effectivePrintLimit() }}
                            </dd>
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Address</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $application->address }}</dd>
                    </div>
                </dl>
            </section>

            <section class="admin-card">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="font-semibold text-slate-900">Request details</h2>
                </div>
                <dl class="grid gap-4 p-5">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">Purpose</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $application->purpose }}</dd>
                    </div>
                    @if ($application->cancellation_reason)
                        <div>
                            <dt class="text-xs font-semibold uppercase text-slate-500">Cancellation reason</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $application->cancellation_reason }}</dd>
                        </div>
                    @endif
                </dl>
            </section>
        </div>

        @if ($application->rejection_reason)
            <section class="admin-card border-red-200">
                <div class="border-b border-red-100 p-5">
                    <h2 class="font-semibold text-red-900">Rejection reason</h2>
                </div>
                <p class="p-5 text-sm text-red-800">{{ $application->rejection_reason }}</p>
            </section>
        @endif

        @if ($application->status === \App\Models\ServiceRequest::PENDING)
            <section class="admin-card">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="font-semibold text-slate-900">Application decision</h2>
                    <p class="mt-1 text-sm text-slate-500">Approve this request or reject it with a reason.</p>
                </div>
                <div class="grid gap-6 p-5 lg:grid-cols-2">
                    <form method="POST" action="{{ route('admin.permits.approve', $application) }}">
                        @csrf
                        <button type="submit"
                            class="rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                            Approve application
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.permits.reject', $application) }}" class="space-y-3">
                        @csrf
                        <label for="rejection_reason" class="block text-sm font-semibold text-slate-700">
                            Reason for rejection
                        </label>
                        <textarea id="rejection_reason" name="rejection_reason" required minlength="5" maxlength="500" rows="3"
                            class="block w-full rounded-md border border-slate-300 bg-white p-3 text-sm text-slate-900 outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20">{{ old('rejection_reason') }}</textarea>
                        @error('rejection_reason')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                            class="rounded-md bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                            Reject application
                        </button>
                    </form>
                </div>
            </section>
        @endif

        @if ($application->status === \App\Models\ServiceRequest::APPROVED)
            <section class="admin-card">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="font-semibold text-slate-900">Print access</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Increase the total print allowance when helping a citizen print their approved certificate.
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.permits.print-limit.update', $application) }}"
                    class="flex flex-col gap-3 p-5 sm:flex-row sm:items-end">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="print_limit" class="block text-sm font-semibold text-slate-700">Allowed prints</label>
                        <input id="print_limit" name="print_limit" type="number" min="{{ $application->print_count }}"
                            max="1000" required value="{{ old('print_limit', $application->effectivePrintLimit()) }}"
                            class="mt-1 block w-32 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-[#17325c] focus:ring-2 focus:ring-[#17325c]/20">
                    </div>
                    <button type="submit"
                        class="rounded-md bg-[#17325c] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#10244a]">
                        Update print allowance
                    </button>
                </form>
                @error('print_limit')
                    <p class="px-5 pb-5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </section>
        @endif
    </div>
@endsection
