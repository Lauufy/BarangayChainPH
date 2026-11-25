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
            $table->string('status', 32)->default('pending')->change();
        });
        Schema::create('permit_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reviewer_name');
            $table->json('items');
            $table->text('message')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        DB::table('service_requests')->where('status', 'resubmission_required')->update(['status' => 'pending']);
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
        Schema::dropIfExists('permit_revisions');
    }
};
