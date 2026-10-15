@extends('layouts.citizen')

@section('title', 'Review Application')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <a href="{{ route('citizen.applications') }}"
           class="inline-flex items-center gap-2 text-sm font-semibold text-[#17325c] hover:text-[#10244a]">
            <span aria-hidden="true">←</span>
            Back to applications
        </a>

        <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Submitted application</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#09172D]">
                        {{ $documentTitle ?? 'Certificate of indigency' }}
                    </h1>
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
                        <span class="flex h-4 w-4 items-center justify-center rounded-full bg-amber-200 text-amber-800" aria-hidden="true">
                            <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" d="M12 7v5l3 2" />
                            </svg>
                        </span>
                    @endif
                    {{ $application->status }}
                </span>
            </div>

            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                @if (filled($application->reference_number))
                    <div>
                        <dt class="text-slate-500">Reference number</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $application->reference_number }}</dd>
                    </div>
                @endif

                <div>
                    <dt class="text-slate-500">Date submitted</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $application->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</dd>
                </div>

                @if (filled($application->applicant_name))
                    <div>
                        <dt class="text-slate-500">Full name</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $application->applicant_name }}</dd>
                    </div>
                @endif

                @if (filled($application->age))
                    <div>
                        <dt class="text-slate-500">Age</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $application->age }}</dd>
                    </div>
                @endif

                @if (filled($application->address))
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500">Address</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $application->address }}</dd>
                    </div>
                @endif

                @if (filled($application->purpose))
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500">Purpose</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $application->purpose }}</dd>
                    </div>
                @endif

                @if (filled($application->cancellation_reason))
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500">Cancellation reason</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $application->cancellation_reason }}</dd>
                    </div>
                @endif

                @if ($application->status === 'rejected' && $application->rejection_reason)
                    <div class="rounded-md border border-red-200 bg-red-50 p-4 sm:col-span-2">
                        <dt class="font-semibold text-red-900">Reason for rejection</dt>
                        <dd class="mt-1 text-red-800">{{ $application->rejection_reason }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        @if ($application->isCancellable())
            <section class="rounded-md border border-red-200 bg-red-50 p-5">
                <h2 class="font-semibold text-red-900">Cancel application</h2>
                <p class="mt-1 text-sm leading-6 text-red-800">
                    You can cancel this pending application within two hours of submission. Please provide a reason.
                </p>

                @if ($errors->any())
                    <div role="alert" class="mt-4 text-sm font-medium text-red-800">
                        {{ $errors->first('cancellation_reason') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('citizen.applications.cancel', $application) }}" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="cancellation_reason" required minlength="5" maxlength="500" rows="3"
                        placeholder="Reason for cancellation"
                        class="block w-full rounded-md border border-red-200 bg-white p-3 text-sm text-slate-900 outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20">{{ old('cancellation_reason') }}</textarea>
                    <button class="rounded-md bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                        Cancel application
                    </button>
                </form>
            </section>
        @endif
    </div>
@endsection