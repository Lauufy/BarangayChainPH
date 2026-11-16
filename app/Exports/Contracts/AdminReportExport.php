<?php

namespace App\Exports\Contracts;

use Illuminate\Http\Request;

interface AdminReportExport
{
    /**
     * @return array{
     *     filename: string,
     *     filters: array<string, mixed>,
     *     csv_rows: list<list<mixed>>,
     *     pdf_view: string,
     *     pdf_data: array<string, mixed>
     * }
     */
    public function data(Request $request): array;
}
