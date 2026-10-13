@props(['heads', 'empty' => false, 'message' => 'No records found.'])

<div class="overflow-x-auto">
    <table class="w-full min-w-160 text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
                @foreach ($heads as $head)
                    <th class="px-5 py-3 font-semibold">{{ $head }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
            @if ($empty)
                <tr>
                    <td colspan="{{ count($heads) }}" class="px-5 py-12 text-center text-slate-400">{{ $message }}</td>
                </tr>
            @else
                {{ $slot }}
            @endif
        </tbody>
    </table>
</div>