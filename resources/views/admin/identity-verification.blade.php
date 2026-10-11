@extends('layouts.admin')

@section('title', 'Digital Identity Verification')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-400">Citizen Services</p>
            <h1 class="mt-2 text-2xl font-bold text-white">Digital Identity Verification</h1>
            <p class="mt-2 text-sm text-blue-100/60">Review citizen identity information and verification submissions.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            @foreach (['Pending Reviews', 'Verified Citizens', 'Rejected Requests'] as $label)
                <div class="rounded-xl border border-white/10 bg-[#09172D] p-5">
                    <p class="text-sm text-blue-100/60">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-bold text-white">—</p>
                </div>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-xl border border-white/10 bg-[#09172D]">
            <div class="border-b border-white/10 p-5">
                <h2 class="font-semibold text-white">Identity Verification Requests</h2>
                <p class="mt-1 text-sm text-slate-400">Review submissions before making a verification decision.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-162.5 text-left text-sm">
                    <thead class="bg-white/3 text-xs uppercase text-slate-400">
                        <tr>
                            <th class="px-5 py-4">Citizen</th>
                            <th class="px-5 py-4">Submitted</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-slate-400">
                                No identity verification records connected yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
