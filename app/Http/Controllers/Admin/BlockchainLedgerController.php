<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BlockchainLedgerExport;
use App\Http\Controllers\Controller;
use App\Models\BlockchainRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Shows what has been recorded on the blockchain. */
class BlockchainLedgerController extends Controller
{
    /** Filterable transaction states. */
    private const STATUSES = [
        'confirmed' => 'Confirmed',
        'failed' => 'Failed',
        'revoked' => 'Revoked',
        'pending' => 'Pending',
        'submitted' => 'Submitted',
        'paused' => 'Paused',
    ];

    public function index(Request $request, BlockchainLedgerExport $export): View
    {
        $perPage = min(max($request->integer('per_page', 10), 5), 50);

        // Ignore unknown status values
        $status = $request->query('status');
        $status = is_string($status) && array_key_exists($status, self::STATUSES) ? $status : null;

        $records = $export->query($request)
            ->when($status, fn($query) => $query->whereRaw(BlockchainRecord::STATE_SQL . ' = ?', [$status]))
            ->paginate($perPage)
            ->appends($request->except('search'));

        $toDate = fn($value) => $value ? Carbon::parse($value) : null;

        // Each recorded or revoked permit is one confirmed transaction
        $recorded = BlockchainRecord::whereNotNull('recorded_at')->count();
        $revoked = BlockchainRecord::whereNotNull('revoked_at')->count();
        $confirmed = $recorded + $revoked;

        $failedQuery = fn() => BlockchainRecord::whereRaw(BlockchainRecord::STATE_SQL . ' = ?', ['failed']);
        $failed = $failedQuery()->count();

        $revokedLatest = $toDate(BlockchainRecord::max('revoked_at'));

        $confirmedLatest = collect([
            $toDate(BlockchainRecord::max('recorded_at')),
            $revokedLatest,
        ])->filter()->max();

        $failedLatest = $toDate($failedQuery()->max('updated_at'));

        // Permits still waiting on the chain, or held while recording is paused
        $waitingQuery = fn() => BlockchainRecord::whereRaw(BlockchainRecord::STATE_SQL . ' in (?, ?)', ['pending', 'paused']);
        $waiting = $waitingQuery()->count();
        $waitingLatest = $toDate($waitingQuery()->max('updated_at'));

        return view('admin.blockchain-ledger.index', [
            'records' => $records,
            'statuses' => self::STATUSES,
            'status' => $status,
            'total' => $confirmed + $failed, // still used by the table toolbar
            'confirmed' => $confirmed,
            'revoked' => $revoked,
            'failed' => $failed,
            'waiting' => $waiting,
            'confirmedLatest' => $confirmedLatest,
            'revokedLatest' => $revokedLatest,
            'failedLatest' => $failedLatest,
            'waitingLatest' => $waitingLatest,
        ]);
    }
}