
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issued_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_request_id')
                ->constrained()
                ->restrictOnDelete();

            $table->uuid('document_uuid')->unique();
            $table->string('document_number');
            $table->unsignedInteger('version')->default(1);

            $table->string('disk')->default('supabase');
            $table->string('file_path');
            $table->char('file_hash', 64);

            $table->timestamp('issued_at');
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['service_request_id', 'version']
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issued_documents');
    }
};
