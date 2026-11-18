<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            $table->string('ocr_status')->nullable();
            $table->json('ocr_flags')->nullable();
            $table->timestamp('ocr_processed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            $table->dropColumn([
                'ocr_status',
                'ocr_flags',
                'ocr_processed_at',
            ]);
        });
    }
};
