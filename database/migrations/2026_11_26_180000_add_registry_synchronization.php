<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_deployments', function (Blueprint $table) {
            $table->string('active_network', 40)->nullable()->unique();
            $table->string('owner_address', 42)->nullable();
            $table->text('pending_control')->nullable();
            $table->timestamp('synced_at')->nullable();
        });
        Schema::table('blockchain_records', function (Blueprint $table) {
            $table->text('signed_transaction')->nullable();
            $table->text('signed_revocation')->nullable();
            $table->timestamp('revoke_submitted_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->json('previous_attempts')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contract_deployments', function (Blueprint $table) {
            $table->dropUnique(['active_network']);
            $table->dropColumn(['active_network', 'owner_address', 'pending_control', 'synced_at']);
        });
        Schema::table('blockchain_records', function (Blueprint $table) {
            $table->dropColumn(['signed_transaction', 'signed_revocation', 'revoke_submitted_at', 'submitted_at', 'previous_attempts']);
        });
    }
};
