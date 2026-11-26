<details class="group/article min-w-0 rounded-lg border border-slate-200 hover:border-slate-400 open:col-span-full dark:border-slate-700 dark:hover:border-slate-500">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-lg p-3 focus-visible:outline-2 [&::-webkit-details-marker]:hidden">
        <span class="min-w-0">
            <span class="block text-sm font-medium">{{ $article->title }}</span>
            <span class="block truncate text-xs text-slate-500 group-open/article:whitespace-normal dark:text-slate-400">{{ $article->summary }}</span>
        </span>
        <x-lucide-chevron-right aria-hidden="true" class="size-4 shrink-0 group-open/article:rotate-90" />
    </summary>
    <div class="px-4 pb-4">
        @include('citizen.help.partials.article-body')
    </div>
</details>
