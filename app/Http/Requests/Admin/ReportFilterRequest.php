<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Throwable;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && ($user->isAdmin() || ($user->isStaff() && $user->hasPermission('reports.view')));
    }

    protected function prepareForValidation(): void
    {
        $filters = [
            'from' => $this->input('from') ?: today()->subDays(29)->toDateString(),
            'to' => $this->input('to') ?: today()->toDateString(),
            'grouping' => $this->input('grouping') ?: 'daily',
        ];

        if (! $this->filled('permit_type') && $this->filled('document_type')) {
            $filters['permit_type'] = $this->input('document_type');
        }

        $this->merge($filters);
    }

    public function rules(): array
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
                function (string $attribute, mixed $value, \Closure $fail): void {
                    try {
                        $from = Carbon::createFromFormat('!Y-m-d', (string) $this->input('from'));
                        $to = Carbon::createFromFormat('!Y-m-d', (string) $value);
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
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'from' => Carbon::parse($validated['from'])->startOfDay(),
            'to' => Carbon::parse($validated['to'])->endOfDay(),
            'permit_type' => $validated['permit_type'] ?? $validated['document_type'] ?? null,
            'grouping' => $validated['grouping'],
        ];
    }
}
