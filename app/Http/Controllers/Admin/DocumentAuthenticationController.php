<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DocumentAuthenticationExport;
use App\Http\Controllers\Controller;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Carbon;

/** Lists the documents issued after a request is approved. */
class DocumentAuthenticationController extends Controller
{
    public function index(Request $request, DocumentAuthenticationExport $export): View
    {
        $perPage = min(max($request->integer('per_page', 10), 5), 50);

        $documents = $export->query($request)->paginate($perPage)->appends($request->except('search'));

        $open = ServiceRequest::whereIn('status', [ServiceRequest::PENDING, ServiceRequest::PROCESSING]);
        $active = IssuedDocument::whereNull('revoked_at');

        $toDate = fn($value) => $value ? Carbon::parse($value) : null;

        return view('admin.document-authentication.index', [
            'documents' => $documents,
            'submitted' => ServiceRequest::count(),
            'pending' => (clone $open)->count(),
            'authenticated' => (clone $active)->count(),

            'submittedLatest' => $toDate(ServiceRequest::max('created_at')),
            'pendingLatest' => $toDate((clone $open)->min('created_at')),
            'authenticatedLatest' => $toDate((clone $active)->max('issued_at')),
        ]);
    }
}
