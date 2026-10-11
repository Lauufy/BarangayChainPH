@extends('layouts.admin')

@section('title', 'Document Authentication')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-400">Document Security</p>
            <h1 class="mt-2 text-2xl font-bold text-white">Document Authentication</h1>
            <p class="mt-2 text-sm text-blue-100/60">Review document verification records and authentication results.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            @foreach (['Documents Submitted', 'Pending Review', 'Authenticated'] as $label)
                <div class="rounded-xl border border-white/10 bg-[#09172D] p-5">
                    <p class="text-sm text-blue-100/60">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-bold text-white">—</p>
                </div>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-xl border border-white/10 bg-[#09172D]">
            <div class="border-b border-white/10 p-5">
                <h2 class="font-semibold text-white">Authentication Records</h2>
                <p class="mt-1 text-sm text-slate-400">Track submitted documents and their verification results.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-175 text-left text-sm">
                    <thead class="bg-white/3 text-xs uppercase text-slate-400">
                        <tr>
                            <th class="px-5 py-4">Document Reference</th>
                            <th class="px-5 py-4">Document Type</th>
                            <th class="px-5 py-4">Submitted By</th>
                            <th class="px-5 py-4">Authentication Status</th>
                            <th class="px-5 py-4">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                No document authentication records connected yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-emerald-400/20 bg-emerald-400/5 p-5">
            <h2 class="font-semibold text-white">Authentication Integrity</h2>
            <p class="mt-2 text-sm leading-6 text-slate-400">
                When integrated, this module can display document hashes and verification results.
                A hash comparison can help detect changes to a document, but does not by itself establish
                the identity of its issuer or prove that the original document was legitimate.
            </p>
        </div>
    </div>
@endsection
