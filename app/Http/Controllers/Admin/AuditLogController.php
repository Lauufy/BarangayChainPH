<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AuditLogsExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /** Show filtered audit records so authorized staff can review changes. */
    public function index(Request $request, AuditLogsExport $export): View
    {
        $filters = $export->filters($request);
        $perPage = $request->integer('per_page', 20);
        $perPage = in_array($perPage, [10, 15, 20, 25, 50], true) ? $perPage : 20;
        $logs = $export->queryFromFilters($filters)->paginate($perPage)->appends($request->except('search'));
        $logs->setCollection($logs->getCollection()->map(function (AuditLog $log) use ($export): array {
            [$date, $user, $action, $record, $changes] = $export->row($log);

            return compact('date', 'user', 'action', 'record', 'changes');
        }));

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'userFilter' => $filters['user_id'] ?? '',
            'actionFilter' => $filters['action'] ?? '',
            'dateFilter' => $filters['date'] ?? '',
            'isEmpty' => $logs->isEmpty(),
        ]);
    }
}
