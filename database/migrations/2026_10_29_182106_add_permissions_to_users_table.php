<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('permissions')->nullable();
        });

        // Existing staff keep the default staff access
        DB::table('users')->where('role', 'staff')->update([
            'permissions' => json_encode([
                'identity.review',
                'permits.review',
                'tickets.reply',
                'documents.authenticate',
            ]),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
};