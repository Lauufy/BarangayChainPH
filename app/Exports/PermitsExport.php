<?php

namespace App\Exports;

use App\Exports\Contracts\AdminExport;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PermitsExport implements AdminExport
{
    public function filename(): string
    {
        return 'permits';
    }

    public function headings(): array
    {
        return ['Reference', 'Applicant', 'Applicant Email', 'Document Type', 'Date Submitted', 'Status'];
    }

    public function query(Request $request): Builder
    {
        return ServiceRequest::query()
            ->with('user')
            ->latest();
    }

    public function row(Model $model): array
    {
        if (! $model instanceof ServiceRequest) {
            throw new InvalidArgumentException('Permit exports require ServiceRequest models.');
        }

        return [
            $model->reference_number,
            $model->applicant_name,
            $model->user?->email,
            $model->serviceTitle(),
            $model->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            $model->status,
        ];
    }
}
