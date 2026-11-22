<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Set when a citizen's personal data was erased.
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('erased_at')->nullable();
        });

        // Every request about personal data (access, correction, erasure).
        Schema::create('data_subject_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->string('status', 20)->default('pending')->index();
            $table->text('details')->nullable();
            $table->timestamp('due_at');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_subject_requests');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('erased_at');
        });
    }
};