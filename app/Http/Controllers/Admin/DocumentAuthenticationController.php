<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Lists the documents issued after a request is approved. */
class DocumentAuthenticationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $perPage = min(max($request->integer('per_page', 10), 5), 50);

        $documents = IssuedDocument::with('serviceRequest')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('document_number', 'like', "%{$search}%")
                        ->orWhereHas('serviceRequest', fn ($q) => $q->where('applicant_name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.document-authentication.index', [
            'documents' => $documents,
            'submitted' => ServiceRequest::count(),
            'pending' => ServiceRequest::whereIn('status', [ServiceRequest::PENDING, ServiceRequest::PROCESSING])->count(),
            'authenticated' => IssuedDocument::whereNull('revoked_at')->count(),
        ]);
    }
}