@props(['reference' => null, 'person' => null, 'note' => null])

<tr @if ($note) data-record-note="{{ $note }}" @endif>
    {{ $slot }}
    @if ($person)
        <td data-mobile-only data-person data-label="Applicant">{{ $person }}</td>
    @endif
    <td data-mobile-only data-card-footer>
        @if ($reference)
            <span data-card-reference>{{ $reference }}</span>
        @endif
        <button type="button" data-record-open aria-haspopup="dialog">
            View Details
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                <path d="M4 12h15m-7-7 7 7-7 7" />
            </svg>
        </button>
    </td>
</tr>
