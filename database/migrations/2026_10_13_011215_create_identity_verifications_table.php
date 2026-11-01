<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_verifications', function (Blueprint $table) {
            $table->id();

            // The citizen who submitted the application
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // pending, verified, or rejected
            $table->string('status', 20)->default('pending')->index();

            // Government ID details (file is kept on the private disk)
            $table->string('document_type');
            $table->string('document_path');
            $table->string('document_original_name')->nullable();

            // When the citizen submitted it
            $table->timestamp('submitted_at')->nullable();

            // Review details (staff or admin)
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_verifications');
    }
};