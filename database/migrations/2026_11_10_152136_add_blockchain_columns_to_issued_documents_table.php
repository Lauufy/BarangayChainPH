<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issued_documents', function (Blueprint $table) {
            // Receipt from the issue transaction (66 characters: 0x + 64)
            $table->string('blockchain_tx_hash', 66)->nullable()->after('revoked_at');
            // When the hash was confirmed on the blockchain. NULL means not recorded yet.
            $table->timestamp('blockchain_recorded_at')->nullable()->after('blockchain_tx_hash');
            // Receipt from the revoke transaction
            $table->string('blockchain_revoke_tx_hash', 66)->nullable()->after('blockchain_recorded_at');
            $table->timestamp('blockchain_revoked_at')->nullable()->after('blockchain_revoke_tx_hash');
            // Last error, so a failed job is easy to diagnose
            $table->text('blockchain_error')->nullable()->after('blockchain_revoked_at');
        });
    }

    public function down(): void
    {
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
};
