<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite keeps the RESTRICT constraint created by the original migration.
            return;
        }

        Schema::table('issued_documents', function (Blueprint $table) {
            $table->dropForeign('issued_documents_service_request_id_foreign');
            $table->foreign('service_request_id', 'issued_documents_service_request_id_foreign')
                ->references('id')
                ->on('service_requests')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('issued_documents', function (Blueprint $table) {
            $table->dropForeign('issued_documents_service_request_id_foreign');
            $table->foreign('service_request_id', 'issued_documents_service_request_id_foreign')
                ->references('id')
                ->on('service_requests')
                ->restrictOnDelete();
        });
    }
};
