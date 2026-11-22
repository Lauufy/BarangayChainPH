<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // Copied from the citizen's verified profile when the request is submitted,
            // so later profile changes do not alter old requests.
            $table->string('civil_status', 20)->nullable()->after('age');
            $table->string('contact_number', 30)->nullable()->after('civil_status');
        });

        // Files the citizen uploads for each requirement (one file per requirement)
        Schema::create('service_request_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->string('requirement_key', 50);
            $table->string('label');
            $table->string('disk', 30);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');
            $table->timestamps();

            $table->unique(['service_request_id', 'requirement_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_documents');

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['civil_status', 'contact_number']);
        });
    }
};