<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockchainRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Shows what has been recorded on the blockchain. */
class BlockchainLedgerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $perPage = min(max($request->integer('per_page', 10), 5), 50);

        $records = BlockchainRecord::with('issuedDocument')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('tx_hash', 'like', "%{$search}%")
                        ->orWhere('revoke_tx_hash', 'like', "%{$search}%")
                        ->orWhereHas('issuedDocument', fn ($q) => $q->where('document_number', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        // Each recorded or revoked permit is one confirmed transaction
        $confirmed = BlockchainRecord::whereNotNull('recorded_at')->count()
            + BlockchainRecord::whereNotNull('revoked_at')->count();
        $failed = BlockchainRecord::whereNotNull('error')->count();

        return view('admin.blockchain-ledger.index', [
            'records' => $records,
            'total' => $confirmed + $failed,
            'confirmed' => $confirmed,
            'failed' => $failed,
        ]);
    }
}