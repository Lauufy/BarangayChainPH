<?php

namespace App\Http\Controllers\Admin;

use App\Exports\Contracts\AdminExport;
use App\Exports\Contracts\AdminReportExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    public function show(Request $request, string $module, ExportService $exports): Response
    {
        $definition = config("exports.modules.{$module}");
        abort_unless(is_array($definition), 404);

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        if (($definition['role'] ?? null) === 'admin') {
            abort_unless($actor->isAdmin(), 403);
        } elseif (! $actor->isAdmin()) {
            abort_unless(
                is_string($definition['permission'] ?? null)
                    && $actor->hasPermission($definition['permission']),
                403
            );
        }

        $validated = $request->validate([
            'format' => ['sometimes', Rule::in(['csv', 'pdf'])],
        ]);
        $format = $validated['format'] ?? 'csv';
        $export = app($definition['export']);
        abort_unless($export instanceof AdminExport || $export instanceof AdminReportExport, 404);
        $filters = Arr::only($request->query(), $definition['filters'] ?? []);

        return $exports->download(
            $export,
            $module,
            $format,
            $request,
            $actor,
            $filters,
            str_replace(
                ['{module}', '{format}'],
                [$module, $format],
                $definition['audit_action'] ?? 'admin_{module}_{format}_exported'
            )
        );
    }
}
