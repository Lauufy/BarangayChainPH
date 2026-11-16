<?php

namespace App\Http\Requests\Admin;

use App\Services\ReportFilters;
use Illuminate\Foundation\Http\FormRequest;

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
        $this->merge(app(ReportFilters::class)->prepare($this->all()));
    }

    public function rules(): array
    {
        return app(ReportFilters::class)->rules($this->all());
    }

    /**
     * @return array{from: Carbon, to: Carbon, permit_type: ?string, grouping: string}
     */
    public function filters(): array
    {
        return app(ReportFilters::class)->toFilters($this->validated());
    }
}
