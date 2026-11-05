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

        <x-tables.card title="Authentication Records"
            description="Track submitted documents and their verification results."
            :searchable="false">
            <x-tables.table :heads="['Document Reference', 'Document Type', 'Submitted By', 'Authentication Status', 'Date']"
                :empty="true" message="No document authentication records connected yet." />
        </x-tables.card>

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