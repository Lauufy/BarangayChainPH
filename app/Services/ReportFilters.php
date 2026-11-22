<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class ReportFilters
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function prepare(array $input): array
    {
        $filters = [
            'from' => ($input['from'] ?? '') ?: today()->subDays(29)->toDateString(),
            'to' => ($input['to'] ?? '') ?: today()->toDateString(),
            'grouping' => ($input['grouping'] ?? '') ?: 'daily',
        ];

        if (blank($input['permit_type'] ?? null) && filled($input['document_type'] ?? null)) {
            $filters['permit_type'] = $input['document_type'];
        }

        return [...$input, ...$filters];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<int, mixed>>
     */
    public function rules(array $input): array
    {
        $documentTypes = collect(config('barangay_services.services', []))
            ->pluck('type')
            ->all();

        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:from',
                function (string $attribute, mixed $value, \Closure $fail) use ($input): void {
                    if (! is_string($input['from'] ?? null) || ! is_string($value)) {
                        return;
                    }

                    try {
                        $from = Carbon::createFromFormat('!Y-m-d', $input['from']);
                        $to = Carbon::createFromFormat('!Y-m-d', $value);
                    } catch (Throwable) {
                        return;
                    }

                    if ($from !== false && $to !== false && $to->greaterThan($from->copy()->addYears(5))) {
                        $fail('The date range may not exceed five years.');
                    }
                },
            ],
            'permit_type' => ['nullable', 'string', Rule::in($documentTypes)],
            'document_type' => ['nullable', 'string', Rule::in($documentTypes)],
            'grouping' => ['required', Rule::in(['daily', 'monthly', 'yearly'])],
        ];
    }

    /**
     * @return array{from: Carbon, to: Carbon, permit_type: ?string, grouping: string}
     */
    public function validate(Request $request): array
    {
        $input = $this->prepare($request->query());
        $validated = Validator::make($input, $this->rules($input))->validate();

        return $this->toFilters($validated);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{from: Carbon, to: Carbon, permit_type: ?string, grouping: string}
     */
    public function toFilters(array $validated): array
    {
        return [
            'from' => Carbon::parse($validated['from'])->startOfDay(),
            'to' => Carbon::parse($validated['to'])->endOfDay(),
            'permit_type' => $validated['permit_type'] ?? $validated['document_type'] ?? null,
            'grouping' => $validated['grouping'],
        ];
    }
}
