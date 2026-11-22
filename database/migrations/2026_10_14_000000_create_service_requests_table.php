<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference_number')->unique();
            $table->string('service_type');
            $table->string('applicant_name');
            $table->text('address');
            $table->unsignedTinyInteger('age');
            $table->string('purpose', 500);
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
