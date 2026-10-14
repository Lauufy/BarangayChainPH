@extends('layouts.admin')

@section('title', 'Document Authentication')

@section('content')
    <div class="space-y-6">
        <x-admin.page-header label="Document Security" title="Document Authentication"
            text="Review document verification records and authentication results." />

        {{-- Numbers --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin.stat-card label="Documents Submitted" />
            <x-admin.stat-card label="Pending Review" />
            <x-admin.stat-card label="Authenticated" />
        </div>

        <div class="admin-card">
            <div class="border-b border-slate-200 p-5">
                <h2 class="font-semibold text-slate-900">Authentication Records</h2>
                <p class="mt-1 text-sm text-slate-500">Track submitted documents and their verification results.</p>
            </div>

            <x-admin.table :heads="['Document Reference', 'Document Type', 'Submitted By', 'Authentication Status', 'Date']"
                :empty="true" message="No document authentication records connected yet." />
        </div>

        {{-- Note --}}
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
            <h2 class="font-semibold text-emerald-900">Authentication Integrity</h2>
            <p class="mt-2 text-sm leading-6 text-emerald-800">
                When integrated, this module can display document hashes and verification results.
                A hash comparison can help detect changes to a document, but does not by itself establish
                the identity of its issuer or prove that the original document was legitimate.
            </p>
        </div>
    </div>
@endsection