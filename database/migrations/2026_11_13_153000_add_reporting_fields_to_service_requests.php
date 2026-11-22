<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable();
            $table->index('service_type');
            $table->index('created_at');
        });

        // Legacy rows have no approval event time; updated_at is the best available estimate and may reflect a later edit.
        DB::table('service_requests')
            ->where('status', 'approved')
            ->whereNull('approved_at')
            ->update(['approved_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex(['service_type']);
            $table->dropIndex(['created_at']);
            $table->dropColumn('approved_at');
        });
    }
};
