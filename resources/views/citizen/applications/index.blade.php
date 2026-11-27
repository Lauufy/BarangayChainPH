@extends('layouts.citizen')

@use('App\Models\ServiceRequest')

@section('title', 'My Applications')

@section('content')
    @php
        // Badge colour per status (same map as the admin permits page)
        $statuses = [
            'pending' => 'warning',
            'resubmission_required' => 'warning',
            'processing' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'neutral',
        ];
    @endphp

    <div class="space-y-6">
        {{-- Header --}}
        <header class="space-y-2 border-b border-(--gray-200) pb-4 dark:border-(--gray-700)">
            <nav class="flex items-center gap-2 text-xs text-(--gray-500) dark:text-(--gray-400)" aria-label="Breadcrumb">
                <a href="{{ route('citizen.services') }}" class="hover:text-(--blue-700) dark:hover:text-(--blue-300)">Services</a>
                <span aria-hidden="true">›</span>
                <span class="font-semibold text-(--blue-700) dark:text-(--blue-300)">My applications</span>
            </nav>

            <div>
                <h1 class="text-2xl font-bold tracking-tight text-(--blue-900) sm:text-3xl dark:text-(--blue-300)">My applications</h1>
                <p class="mt-1 text-sm text-(--gray-600) dark:text-(--gray-300)">Track the progress of your barangay service requests in one place.</p>
            </div>
        </header>

        <x-tables.card title="Service requests"
            description="Review your submitted requests and their current status."
            :paginator="$applications->hasPages() ? $applications : null" :searchable="false" :per-page-options="[]">
            <x-tables.table
                :heads="['Reference', 'Service', 'Date Submitted', 'Status', ['label' => 'Actions', 'align' => 'center']]"
                :empty="$applications->isEmpty()"
                message="No applications yet. Submit a barangay service request to start tracking its progress here."
            >
                @foreach ($applications as $application)
                    @php
                        $hasTicket = (bool) $application->ticket;
                        $canPrint = $application->canPrint();
                        $canCancel = $application->isCancellable();
                    @endphp

                    <tr>
                        {{-- Summary: always visible on mobile --}}
                        <td data-title class="whitespace-nowrap font-semibold text-slate-900 dark:text-slate-100">
                            {{ $application->reference_number }}
                        </td>
                        <td class="text-slate-600 dark:text-slate-300">{{ $application->serviceTitle() }}</td>

                        {{-- Details: revealed when the card is tapped on mobile --}}
                        <x-tables.detail label="Date submitted" class="whitespace-nowrap text-slate-600 dark:text-slate-300">
                            {{ $application->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                        </x-tables.detail>

                        <td data-side="right">
                            <x-tables.badge :variant="$statuses[$application->status] ?? 'neutral'" labeled class="capitalize">
                                {{ str_replace('_', ' ', $application->status) }}
                            </x-tables.badge>
                        </td>

                        <x-tables.detail side="full" class="text-center">
                            <div class="flex flex-wrap items-start justify-center gap-2">
                                @if ($application->status === ServiceRequest::RESUBMISSION_REQUIRED)
                                    <x-tables.action label="Resubmit" icon="pencil" :href="route('citizen.resubmissions.edit', $application)" />
                                @endif

                                <x-tables.action label="Review" icon="file-search" :href="route('citizen.applications.show', $application)" />

                                @if ($hasTicket)
                                    <x-tables.action label="Messages" icon="message" :href="route('citizen.tickets.show', $application)" />
                                @endif

                                @if ($canPrint)
                                    <x-tables.action :label="'Get printable PDF ('.$application->printsRemaining().' remaining)'" icon="printer" :action="route('citizen.applications.print', $application)" />
                                @endif

                                {{-- Cancel: opens the shared cancellation modal below --}}
                                @if ($canCancel)
                                    <x-tables.action label="Cancel application {{ $application->reference_number }}" icon="x" variant="danger"
                                        data-cancel-open data-id="{{ $application->id }}"
                                        data-ref="{{ $application->reference_number }}"
                                        data-action="{{ route('citizen.applications.cancel', $application) }}" />
                                @endif
                            </div>
                        </x-tables.detail>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>
    </div>

    {{-- ============================================================ --}}
    {{-- CANCEL MODAL (one shared native <dialog>)                    --}}
    {{-- ============================================================ --}}
    <dialog id="cancel-dialog"
        class="m-auto w-[calc(100%-2rem)] max-w-md overflow-hidden rounded-lg bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60 backdrop:backdrop-blur-sm dark:bg-slate-900 dark:text-slate-100 dark:ring-1 dark:ring-slate-800">
        <form id="cancel-form" method="POST" action="" class="p-4 sm:p-5" novalidate>
            @csrf
            <input type="hidden" name="application_id" id="cancel-application-id" value="{{ old('application_id') }}">

            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Cancel application</h3>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                You are cancelling <span id="cancel-ref" class="font-semibold text-slate-700 dark:text-slate-200"></span>.
                This cannot be undone.
            </p>

            <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-800">
                <label for="cancellation_reason"
                    class="mb-1.5 block text-xs font-semibold text-slate-800 dark:text-slate-100">
                    Reason for cancellation <span class="text-red-500">*</span>
                </label>
                <textarea id="cancellation_reason" name="cancellation_reason" rows="4" minlength="5" maxlength="500"
                    placeholder="Tell us why you are cancelling this application..."
                    class="block w-full rounded-md border border-slate-300 bg-white p-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:hover:border-slate-600">{{ old('cancellation_reason') }}</textarea>
                <p id="cancel-error"
                    class="{{ $errors->has('cancellation_reason') ? '' : 'hidden' }} mt-1 text-xs text-red-600 dark:text-red-400">
                    {{ $errors->first('cancellation_reason') ?: 'Enter a reason of at least 5 characters.' }}
                </p>
            </div>

            <div class="mt-4 flex items-center justify-end gap-3">
                <button type="button" id="cancel-close"
                    class="rounded-md bg-slate-200 px-5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    Keep application
                </button>
                <button type="button" id="cancel-confirm"
                    class="rounded-md bg-red-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-60">
                    Confirm cancellation
                </button>
            </div>
        </form>
    </dialog>

    <script>
        (function() {
            // Handlers live on `document` so they keep working if the page content is replaced.
            var backdropPress = null;

            function openCancel(button) {
                var dialog = document.getElementById('cancel-dialog');
                var reason = document.getElementById('cancellation_reason');

                document.getElementById('cancel-form').action = button.dataset.action;
                document.getElementById('cancel-application-id').value = button.dataset.id;
                document.getElementById('cancel-ref').textContent = button.dataset.ref;
                document.getElementById('cancel-confirm').disabled = false;

                if (!dialog.open) dialog.showModal();
                reason.focus();
            }

            document.addEventListener('mousedown', function(e) {
                backdropPress = e.target instanceof HTMLDialogElement ? e.target : null;
            });

            document.addEventListener('click', function(e) {
                var target = e.target;

                // A press that starts and ends on the dark backdrop closes the dialog
                if (backdropPress && target === backdropPress) backdropPress.close();
                backdropPress = null;

                var opener = target.closest('[data-cancel-open]');
                if (opener) {
                    document.getElementById('cancellation_reason').value = '';
                    document.getElementById('cancel-error').classList.add('hidden');
                    openCancel(opener);
                    return;
                }

                if (target.closest('#cancel-close')) {
                    document.getElementById('cancel-dialog').close();
                    return;
                }

                var confirm = target.closest('#cancel-confirm');
                if (confirm) {
                    var reason = document.getElementById('cancellation_reason');
                    var error = document.getElementById('cancel-error');

                    if (reason.value.trim().length < 5) {
                        error.textContent = 'Enter a reason of at least 5 characters.';
                        error.classList.remove('hidden');
                        reason.focus();
                        return;
                    }

                    confirm.disabled = true; // prevent double submit
                    document.getElementById('cancel-form').submit();
                }
            });

            document.addEventListener('input', function(e) {
                if (e.target.id === 'cancellation_reason') {
                    document.getElementById('cancel-error').classList.add('hidden');
                }
            });

            // Reopen the modal for the same application when the server refused the reason
            @if ($errors->has('cancellation_reason') && old('application_id'))
                var failed = document.querySelector('[data-cancel-open][data-id="{{ e(old('application_id')) }}"]');
                if (failed) openCancel(failed);
            @endif
        })();
    </script>
@endsection