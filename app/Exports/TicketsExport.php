<?php

namespace App\Exports;

use App\Exports\Contracts\AdminExport;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TicketsExport implements AdminExport
{
    public function filename(): string
    {
        return 'tickets';
    }

    public function headings(): array
    {
        return ['Citizen', 'Citizen Email', 'Reference', 'Last Activity', 'Status'];
    }

    public function query(Request $request): Builder
    {
        return Ticket::query()
            ->with(['serviceRequest.user', 'latestMessage'])
            ->latest('updated_at');
    }

    public function row(Model $model): array
    {
        if (! $model instanceof Ticket) {
            throw new InvalidArgumentException('Ticket exports require Ticket models.');
        }

        return [
            $model->serviceRequest?->applicant_name,
            $model->serviceRequest?->user?->email,
            $model->serviceRequest?->reference_number,
            $model->updated_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            $model->status,
        ];
    }
}
