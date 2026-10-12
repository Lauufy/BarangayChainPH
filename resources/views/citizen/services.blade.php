@extends('layouts.citizen')

@section('title', 'Barangay Services')

@section('content')
    <div class="space-y-7">
        <section>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Citizen portal</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#09172D] sm:text-3xl">Barangay services</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Explore common services from your barangay. Online request submission is not enabled yet; please contact the barangay office to apply.</p>
        </section>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ([
                ['title' => 'Barangay clearance', 'description' => 'Request a clearance for employment, business, or other official purposes.', 'icon' => 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Z'],
                ['title' => 'Certificate of residency', 'description' => 'Request proof of residency within the barangay.', 'icon' => 'M3 10.5 12 3l9 7.5M5 9v11h14V9M9 20v-6h6v6'],
                ['title' => 'Certificate of indigency', 'description' => 'Ask the barangay about eligibility and requirements for this certificate.', 'icon' => 'M12 3v18M17 7.5c0-1.4-2.2-2.5-5-2.5S7 6.1 7 7.5 9.2 10 12 10s5 1.1 5 2.5-2.2 2.5-5 2.5-5-1.1-5-2.5'],
            ] as $service)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path d="{{ $service['icon'] }}" />
                        </svg>
                    </span>
                    <h2 class="mt-5 font-bold text-[#09172D]">{{ $service['title'] }}</h2>
                    <p class="mt-2 min-h-12 text-sm leading-6 text-slate-600">{{ $service['description'] }}</p>
                    <span class="mt-5 inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">In-person request</span>
                </article>
            @endforeach
        </div>

        <aside class="rounded-2xl border border-blue-100 bg-blue-50 p-5 sm:p-6">
            <h2 class="font-semibold text-[#09172D]">Need help with a request?</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Visit or contact your barangay office to confirm current requirements, fees, and processing times.</p>
            <a href="{{ route('citizen.applications') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-emerald-800 hover:text-emerald-900">View my applications <span aria-hidden="true">→</span></a>
        </aside>
    </div>
@endsection
