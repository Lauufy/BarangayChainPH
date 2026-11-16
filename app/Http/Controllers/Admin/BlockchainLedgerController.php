<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BlockchainLedgerExport;
use App\Http\Controllers\Controller;
use App\Models\BlockchainRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Shows what has been recorded on the blockchain. */
class BlockchainLedgerController extends Controller
{
    public function index(Request $request, BlockchainLedgerExport $export): View
    {
        $perPage = min(max($request->integer('per_page', 10), 5), 50);

        $records = $export->query($request)->paginate($perPage)->appends($request->except('search'));

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
