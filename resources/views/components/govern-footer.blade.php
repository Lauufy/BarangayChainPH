<footer class="bg-slate-950 text-white">
    <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">

        <div class="grid gap-10 md:grid-cols-3">

            {{-- BRAND --}}
            <div>
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-blue-900 bg-blue-50">
                        <img src="{{ asset('img/branding/barangayChainPH-logo.png') }}" alt="Barangay San Luis Logo"
                            class="h-full w-full object-cover">
                    </div>

                    <div class="leading-tight">
                        <p class="text-[9px] font-bold uppercase tracking-widest text-blue-400">
                            Barangay Government
                        </p>

                        <h3 class="font-black text-white">
                            Barangay San Luis
                        </h3>

                        <p class="text-xs text-slate-500">
                            Antipolo City, Rizal
                        </p>
                    </div>
                </div>

                <p class="mt-5 max-w-sm text-sm leading-6 text-slate-400">
                    Official online public service portal of Barangay San Luis,
                    Antipolo City, Rizal.
                </p>
            </div>


            {{-- CONTACT --}}
            <div>
                <h3 class="text-xs font-black uppercase tracking-widest text-white">
                    Contact Us
                </h3>

                <div class="mt-3 space-y-4 text-sm">

                    {{-- Address --}}
                    <div class="flex items-start gap-2 mb-1">
                        <span class="shrink-0 text-xs font-medium uppercase tracking-wide text-slate-500">
                            Address:
                        </span>
                        <p class="leading-5 text-slate-300">
                            Barangay San Luis, Antipolo City, Rizal
                        </p>
                    </div>

                    {{-- Phone --}}
                    <div class="flex items-center gap-2 mb-1">
                        <span class="shrink-0 text-xs font-medium uppercase tracking-wide text-slate-500">
                            Phone:
                        </span>
                        <a href="tel:+639233232322" class="text-slate-300 transition hover:text-blue-400">
                            +63 923 323 2322
                        </a>
                    </div>

                    {{-- Email --}}
                    <div class="flex items-center gap-2 mb-1">
                        <span class="shrink-0 text-xs font-medium uppercase tracking-wide text-slate-500">
                            Email:
                        </span>
                        <a href="mailto:barangaychain@gmail.com" class="text-slate-300 transition hover:text-blue-400">
                            barangaychain@gmail.com
                        </a>
                    </div>

                    {{-- Facebook --}}
                    <div class="flex items-center gap-2 mb-1">
                        <span class="shrink-0 text-xs font-medium uppercase tracking-wide text-slate-500">
                            Facebook:
                        </span>

                        <a href="https://www.facebook.com/barangay.sanluis.35" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 font-semibold text-blue-400 transition hover:text-blue-300 hover:underline">
                            Visit our Facebook Page
                        </a>
                    </div>

                </div>



            </div>
            {{-- OFFICE HOURS --}}
            <div>
                <h3 class="text-xs font-black uppercase tracking-widest text-white">
                    Office Hours
                </h3>

                <div class="mt-2 space-y-2 text-sm">

                    <div>
                        <p class="font-semibold text-slate-300">
                            Monday – Friday
                        </p>
                        <p class="mt-1 text-slate-400">
                            8:00 AM – 5:00 PM
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-slate-300">
                            Saturday – Sunday
                        </p>
                        <p class="mt-1 text-slate-500">
                            Closed
                        </p>
                    </div>

                </div>
            </div>

        </div>


        {{-- COPYRIGHT --}}
        <div class="mt-10 border-t border-slate-800 pt-6">
            <div class="flex flex-col gap-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">

                <p>
                    © {{ date('Y') }} Barangay San Luis. All Rights Reserved.
                </p>

                <p>
                    Republic of the Philippines · Antipolo City, Rizal
                </p>

            </div>
        </div>

    </div>
</footer>
