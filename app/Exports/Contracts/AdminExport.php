<?php

namespace App\Exports\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

interface AdminExport
{
    public function filename(): string;

    /**
     * @return list<string>
     */
    public function headings(): array;

    public function query(Request $request): Builder;

    /**
     * @return list<mixed>
     */
    public function row(Model $model): array;
}
