@extends('layouts.citizen')

@section('title', 'Please wait before submitting again')

@section('content')
    <section class="mx-auto max-w-2xl rounded-xl border border-(--gray-200) bg-(--white) p-6 shadow-sm dark:border-(--gray-700) dark:bg-(--gray-800)">
        <h1 class="text-xl font-bold text-(--blue-900) dark:text-(--blue-300)">
            Please wait before submitting again
        </h1>
        <p class="mt-3 text-sm text-(--gray-600) dark:text-(--gray-300)">
            Too many service applications were submitted in a short time. Please wait a moment before trying again.
        </p>
        <a href="{{ route('citizen.applications') }}"
            class="mt-5 inline-flex rounded-md bg-(--blue-600) px-4 py-2.5 text-sm font-semibold text-(--white) hover:bg-(--blue-700)">
            View your applications
        </a>
    </section>
@endsection
