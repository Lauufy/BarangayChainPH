<div class="max-w-prose space-y-3 text-sm leading-relaxed
    [&_ol]:list-decimal [&_ol]:pl-5 [&_ul]:list-disc [&_ul]:pl-5 [&_a]:underline">
    {!! \Illuminate\Support\Str::markdown($article->body, [
        'html_input' => 'strip',
        'allow_unsafe_links' => false,
    ]) !!}
</div>
