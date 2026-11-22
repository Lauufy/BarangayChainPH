<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contract_deployments', function (Blueprint $t) {
            $t->id();
            $t->string('version', 20);
            $t->string('network', 40);
            $t->string('contract_address', 42);
            $t->string('deploy_tx_hash', 66)->nullable();
            $t->boolean('is_active')->default(false);
            $t->timestamp('deployed_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_deployments');
    }
};
