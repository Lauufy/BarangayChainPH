<?php

namespace App\Exports;

use App\Exports\Contracts\AdminExport;
use App\Models\IssuedDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DocumentAuthenticationExport implements AdminExport
{
    public function filename(): string
    {
        return 'document-authentication';
    }

    public function headings(): array
    {
        return ['Document Reference', 'Document Type', 'Submitted By', 'Authentication Status', 'Date'];
    }

    public function query(Request $request): Builder
    {
        return IssuedDocument::query()
            ->with('serviceRequest')
            ->latest('id');
    }

    public function row(Model $model): array
    {
        if (! $model instanceof IssuedDocument) {
            throw new InvalidArgumentException('Document authentication exports require IssuedDocument models.');
        }

        return [
            $model->document_number.' v'.$model->version,
            $model->serviceRequest?->serviceTitle(),
            $model->serviceRequest?->applicant_name,
            $model->revoked_at ? 'Replaced' : 'Active',
            $model->issued_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
        ];
    }
}
