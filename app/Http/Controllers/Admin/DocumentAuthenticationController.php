<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DocumentAuthenticationExport;
use App\Http\Controllers\Controller;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Lists the documents issued after a request is approved. */
class DocumentAuthenticationController extends Controller
{
    public function show(IssuedDocument $document): Response
    {
        abort_if(blank($document->file_path), 404);

        $disk = Storage::disk($document->disk);
        abort_unless($disk->exists($document->file_path), 404);
        $pdf = $disk->get($document->file_path);
        abort_if($pdf === null, 404);
        abort_unless(hash_equals((string) $document->file_hash, hash('sha256', $pdf)), 409, 'The document failed its integrity check.');

        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $document->document_number);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}-v{$document->version}.pdf\"",
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function index(Request $request, DocumentAuthenticationExport $export): View
    {
        $perPage = min(max($request->integer('per_page', 10), 5), 50);

        $status = $request->query('document-authentication_status');

        $from = match ($request->query('document-authentication_date')) {
            'today' => now()->startOfDay(),
            '7days' => now()->subDays(6)->startOfDay(),
            '30days' => now()->subDays(29)->startOfDay(),
            'year' => now()->startOfYear(),
            default => null,
        };

        $documents = $export->query($request)
            ->when($status === 'active', fn ($query) => $query->whereNull('revoked_at'))
            ->when($status === 'replaced', fn ($query) => $query->whereNotNull('revoked_at'))
            ->when($from, fn ($query) => $query->where('issued_at', '>=', $from))
            ->paginate($perPage)
            ->appends($request->except('search'));

        $open = ServiceRequest::whereIn('status', [ServiceRequest::PENDING, ServiceRequest::PROCESSING]);
        $active = IssuedDocument::whereNull('revoked_at');

        $toDate = fn ($value) => $value ? Carbon::parse($value) : null;

        return view('admin.document-authentication.index', [
            'documents' => $documents,
            'totalDocuments' => IssuedDocument::count(),
            'submitted' => ServiceRequest::count(),
            'pending' => (clone $open)->count(),
            'authenticated' => (clone $active)->count(),

            'submittedLatest' => $toDate(ServiceRequest::max('created_at')),
            'pendingLatest' => $toDate((clone $open)->min('created_at')),
            'authenticatedLatest' => $toDate((clone $active)->max('issued_at')),
        ]);
    }
}
