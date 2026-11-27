<x-mail::message>
# Hello {{ $contactMessage->first_name }},

{{ $staffName }} from Barangay San Luis replied to your message about **{{ $contactMessage->subject }}**.

{!! nl2br(e($reply)) !!}

Thanks,<br />
Barangay San Luis
</x-mail::message>