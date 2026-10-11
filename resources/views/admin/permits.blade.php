@extends('layouts.admin')

@section('title', 'Permit & Clearance Processing')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-400">Citizen Services</p>
            <h1 class="mt-2 text-2xl font-bold text-white">Permit & Clearance Processing</h1>
            <p class="mt-2 text-sm text-blue-100/60">Monitor applications and manage permit processing workflows.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach (['Total Requests', 'Pending', 'Processing', 'Completed'] as $label)
                <div class="rounded-xl border border-white/10 bg-[#09172D] p-5">
                    <p class="text-sm text-blue-100/60">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-bold text-white">—</p>
                </div>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-xl border border-white/10 bg-[#09172D]">
            <div class="border-b border-white/10 p-5">
                <h2 class="font-semibold text-white">Permit Applications</h2>
                <p class="mt-1 text-sm text-slate-400">Review submitted requests and their current status.</p>
            </div>

            <div class="flex flex-col gap-3 border-b border-white/10 p-4 sm:flex-row">
                <input type="search" placeholder="Search applicant or reference number..."
                    class="w-full rounded-lg border border-white/10 bg-[#020A18] px-3 py-2.5 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none">
                <select class="rounded-lg border border-white/10 bg-[#020A18] px-3 py-2.5 text-sm text-slate-300">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-187.5 text-left text-sm">
                    <thead class="bg-white/3 text-xs uppercase text-slate-400">
                        <tr>
                            <th class="px-5 py-4">Reference</th>
                            <th class="px-5 py-4">Applicant</th>
                            <th class="px-5 py-4">Document Type</th>
                            <th class="px-5 py-4">Date Submitted</th>
                            <th class="px-5 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                No permit applications connected yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
