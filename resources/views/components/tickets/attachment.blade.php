@props(['message'])
@if ($message->attachment_path)
    <a class="block break-all underline" href="{{ route('tickets.attachment', $message) }}">
        @if ($message->imageContentType())
            <img src="{{ route('tickets.attachment', ['message' => $message, 'preview' => 1]) }}" alt="{{ $message->attachment_name }}"
                class="my-1 max-h-64 max-w-full rounded-lg object-contain" loading="lazy">
        @else
            Download {{ $message->attachment_name }}
        @endif
    </a>
@endif
