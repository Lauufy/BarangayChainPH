<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->nullable()->constrained('service_requests')->nullOnDelete();
            $table->string('result', 32);
            $table->timestamps();

            $table->index(['result', 'created_at']);
            $table->index('service_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_logs');
    }
};
