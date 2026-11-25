<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('service_request_id')->nullable()->change();
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->string('attachment_path')->nullable();
            $table->text('attachment_name')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('tickets')->whereNull('service_request_id')->exists()) {
            throw new RuntimeException('Archive standalone tickets before rolling back this migration.');
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('service_request_id')->nullable(false)->change();
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name']);
        });
    }
};
