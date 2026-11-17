{{-- Register a new contract deployment. Opened by the "Add" button on the Smart Contracts page. --}}
<style>
    #deployment-modal[open] { animation: deployment-modal-in 160ms ease-out; }
    #deployment-modal[open]::backdrop { animation: deployment-modal-fade 160ms ease-out; }
    body:has(#deployment-modal[open]) { overflow: hidden; }

    @keyframes deployment-modal-in {
        from { opacity: 0; transform: translateY(8px) scale(0.98); }
        to { opacity: 1; transform: none; }
    }

    @keyframes deployment-modal-fade {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>

<dialog id="deployment-modal" aria-labelledby="deployment-modal-title"
    class="m-auto max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-none border border-slate-200 border-t-4 border-t-emerald-700 bg-white p-0 shadow-2xl backdrop:bg-slate-900/40 backdrop:backdrop-blur-sm">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
        <div class="flex items-start gap-4">
            <span class="grid size-10 shrink-0 place-items-center bg-emerald-50 text-emerald-700">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14" />
                    <path d="M12 5v14" />
                </svg>
            </span>
            <div>
                <h2 id="deployment-modal-title" class="text-lg font-semibold text-slate-900">Register a new deployment</h2>
                <p class="mt-0.5 text-sm text-slate-500">Save a record of a contract version you deployed.</p>
            </div>
        </div>

        <button type="button" aria-label="Close" onclick="this.closest('dialog').close()"
            class="grid size-8 shrink-0 place-items-center text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
            </svg>
        </button>
    </div>

    <form method="POST" action="{{ route('admin.smart-contracts.deployments.store') }}">
        @csrf

        <div class="space-y-5 px-6 py-5">
            <div class="border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm text-slate-700">
                Deploy the new contract version with Hardhat first, then record it here. This only saves the record.
                It does not change which contract the system uses. That is set by
                <span class="font-mono text-xs">BLOCKCHAIN_CONTRACT</span> in <span class="font-mono text-xs">.env</span>.
            </div>

            <x-feedbacks.form-errors />

            <div class="grid gap-4 sm:grid-cols-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 sm:col-span-2">Contract details</p>

                <div>
                    <x-form.input name="version" label="Version" :light="true" :value="old('version')"
                        maxlength="20" required placeholder="2.0" />
                </div>

                <div>
                    <x-form.input name="network" label="Network" :light="true" :value="old('network', $networkName)"
                        maxlength="40" required />
                </div>

                <div class="sm:col-span-2">
                    <x-form.input name="contract_address" label="Contract address" :light="true"
                        :value="old('contract_address')" required placeholder="0x..." class="font-mono" />
                </div>

                <p class="mt-2 border-t border-slate-200 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500 sm:col-span-2">
                    Deployment record (optional)
                </p>

                <div class="sm:col-span-2">
                    <x-form.input name="deploy_tx_hash" label="Deployment transaction hash" :light="true"
                        :value="old('deploy_tx_hash')" placeholder="0x..." class="font-mono" />
                </div>

                <div>
                    <x-form.input name="deployed_at" label="Deployed on" type="date" :light="true"
                        :value="old('deployed_at')" :max="now()->toDateString()" />
                </div>

                <div>
                    <x-form.input name="notes" label="Notes" :light="true" :value="old('notes')"
                        maxlength="500" placeholder="What changed in this version" />
                </div>

                <label for="make_active" class="flex cursor-pointer items-center gap-3 border border-slate-200 bg-slate-50 px-4 py-3 sm:col-span-2">
                    <input id="make_active" name="make_active" type="checkbox" value="1"
                        @checked(old('make_active')) class="size-5 rounded-none border-slate-300">
                    <span class="text-sm text-slate-700">
                        Mark as the active contract <span class="text-slate-500">(retires the current one)</span>
                    </span>
                </label>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <button type="button" onclick="this.closest('dialog').close()"
                class="inline-flex min-h-11 items-center rounded-none border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">
                Cancel
            </button>
            <button type="submit"
                class="inline-flex min-h-11 items-center rounded-none bg-emerald-700 px-5 text-sm font-semibold text-white transition hover:bg-emerald-800">
                Record deployment
            </button>
        </div>
    </form>
</dialog>

<script>
    (function () {
        var dialog = document.getElementById('deployment-modal');

        if (!dialog) {
            return;
        }

        // Move the modal to <body> so layout scripts and content re-renders can't touch it.
        document.body.appendChild(dialog);

        function openModal() {
            if (!dialog.open) {
                dialog.showModal();
            }
        }

        // Open after the click has finished, so no click handler in the layout
        // can treat the opening click as an outside click.
        document.querySelectorAll('[data-open-deployment-modal]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                setTimeout(openModal, 0);
            });
        });

        // Reopen after a failed submit so the errors are visible.
        @if ($errors->hasAny(['version', 'network', 'contract_address', 'deploy_tx_hash', 'deployed_at', 'notes']))
            openModal();
        @endif
    })();
</script>