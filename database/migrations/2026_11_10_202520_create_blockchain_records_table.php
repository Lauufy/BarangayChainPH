<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. The new table. One row per issued document.
        Schema::create('blockchain_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issued_document_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('network', 40)->nullable();
            $table->string('contract', 42)->nullable();
            $table->string('tx_hash', 66)->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->string('revoke_tx_hash', 66)->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        // 2. Copy the old data, only for permits that have something saved
        DB::table('blockchain_records')->insertUsing(
            ['issued_document_id', 'tx_hash', 'recorded_at', 'revoke_tx_hash', 'revoked_at', 'error', 'created_at', 'updated_at'],
            DB::table('issued_documents')
                ->select('id', 'blockchain_tx_hash', 'blockchain_recorded_at', 'blockchain_revoke_tx_hash', 'blockchain_revoked_at', 'blockchain_error', DB::raw('CURRENT_TIMESTAMP'), DB::raw('CURRENT_TIMESTAMP'))
                ->whereNotNull('blockchain_tx_hash')
                ->orWhereNotNull('blockchain_recorded_at')
                ->orWhereNotNull('blockchain_revoke_tx_hash')
                ->orWhereNotNull('blockchain_revoked_at')
                ->orWhereNotNull('blockchain_error')
        );

        // 3. Remove the old columns
        Schema::table('issued_documents', function (Blueprint $table) {
            $table->dropColumn([
                'blockchain_tx_hash',
                'blockchain_recorded_at',
                'blockchain_revoke_tx_hash',
                'blockchain_revoked_at',
                'blockchain_error',
            ]);
        });
    }

    public function down(): void
    {
        // Put the old columns back
        Schema::table('issued_documents', function (Blueprint $table) {
            $table->string('blockchain_tx_hash', 66)->nullable();
            $table->timestamp('blockchain_recorded_at')->nullable();
            $table->string('blockchain_revoke_tx_hash', 66)->nullable();
            $table->timestamp('blockchain_revoked_at')->nullable();
            $table->text('blockchain_error')->nullable();
        });

        // Copy the data back, then remove the new table
        foreach (DB::table('blockchain_records')->get() as $record) {
            DB::table('issued_documents')->where('id', $record->issued_document_id)->update([
                'blockchain_tx_hash' => $record->tx_hash,
                'blockchain_recorded_at' => $record->recorded_at,
                'blockchain_revoke_tx_hash' => $record->revoke_tx_hash,
                'blockchain_revoked_at' => $record->revoked_at,
                'blockchain_error' => $record->error,
            ]);
        }

        Schema::dropIfExists('blockchain_records');
    }
};