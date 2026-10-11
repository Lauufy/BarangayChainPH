@extends('layouts.admin')

@section('title', 'Notifications & Messaging')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-400">Communication</p>
            <h1 class="mt-2 text-2xl font-bold text-white">Notifications & Messaging</h1>
            <p class="mt-2 text-sm text-blue-100/60">Monitor system notifications and citizen service communications.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            @foreach (['Total Notifications', 'Sent Successfully', 'Delivery Failed'] as $label)
                <div class="rounded-xl border border-white/10 bg-[#09172D] p-5">
                    <p class="text-sm text-blue-100/60">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-bold text-white">—</p>
                </div>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-xl border border-white/10 bg-[#09172D]">
            <div class="border-b border-white/10 p-5">
                <h2 class="font-semibold text-white">Notification History</h2>
                <p class="mt-1 text-sm text-slate-400">Review messages and delivery status.</p>
            </div>

            <div class="flex flex-col gap-3 border-b border-white/10 p-4 sm:flex-row">
                <input type="search" placeholder="Search notification..."
                    class="w-full rounded-lg border border-white/10 bg-[#020A18] px-3 py-2.5 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none">
                <select class="rounded-lg border border-white/10 bg-[#020A18] px-3 py-2.5 text-sm text-slate-300">
                    <option value="">All delivery statuses</option>
                    <option value="sent">Sent</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-162.5 text-left text-sm">
                    <thead class="bg-white/3 text-xs uppercase text-slate-400">
                        <tr>
                            <th class="px-5 py-4">Recipient</th>
                            <th class="px-5 py-4">Message</th>
                            <th class="px-5 py-4">Channel</th>
                            <th class="px-5 py-4">Date</th>
                            <th class="px-5 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                No notification records connected yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
