<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issued_documents', function (Blueprint $table) {
            $table->json('canonical_fields')->nullable();
            $table->foreignId('replaced_by')->nullable()->constrained('issued_documents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('issued_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replaced_by');
            $table->dropColumn('canonical_fields');
        });
    }
};
