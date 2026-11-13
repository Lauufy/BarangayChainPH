<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'from' => $this->input('from') ?: today()->subDays(29)->toDateString(),
            'to' => $this->input('to') ?: today()->toDateString(),
        ]);
    }

    public function rules(): array
    {
        $documentTypes = collect(config('barangay_services.services', []))
            ->pluck('type')
            ->all();

        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'document_type' => ['nullable', 'string', Rule::in($documentTypes)],
        ];
    }

    /**
     * @return array{from: Carbon, to: Carbon, document_type: ?string}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'from' => Carbon::parse($validated['from'])->startOfDay(),
            'to' => Carbon::parse($validated['to'])->endOfDay(),
            'document_type' => $validated['document_type'] ?? null,
        ];
    }
}
