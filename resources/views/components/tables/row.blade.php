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
        </button>
    </td>
</tr>
