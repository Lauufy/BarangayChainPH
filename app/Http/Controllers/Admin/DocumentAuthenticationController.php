<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DocumentAuthenticationExport;
use App\Http\Controllers\Controller;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Lists the documents issued after a request is approved. */
class DocumentAuthenticationController extends Controller
{
    public function index(Request $request, DocumentAuthenticationExport $export): View
    {
        $perPage = min(max($request->integer('per_page', 10), 5), 50);

        $documents = $export->query($request)->paginate($perPage)->appends($request->except('search'));

        return view('admin.document-authentication.index', [
            'documents' => $documents,
            'submitted' => ServiceRequest::count(),
            'pending' => ServiceRequest::whereIn('status', [ServiceRequest::PENDING, ServiceRequest::PROCESSING])->count(),
            'authenticated' => IssuedDocument::whereNull('revoked_at')->count(),
        ]);
    }
}
