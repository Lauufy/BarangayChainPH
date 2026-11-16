<?php

namespace App\Exports;

use App\Exports\Contracts\AdminExport;
use App\Models\IdentityVerification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;

class IdentityVerificationsExport implements AdminExport
{
    public function filename(): string
    {
        return 'identity-verifications';
    }

    public function headings(): array
    {
        return ['Citizen', 'Submitted', 'Status'];
    }

    public function query(Request $request): Builder
    {
        return IdentityVerification::query()
            ->with('user')
            ->latest('submitted_at');
    }

    public function row(Model $model): array
    {
        if (! $model instanceof IdentityVerification) {
            throw new InvalidArgumentException('Identity verification exports require IdentityVerification models.');
        }

        return [
            $model->user?->name,
            $model->submitted_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            $model->status,
        ];
    }
}
