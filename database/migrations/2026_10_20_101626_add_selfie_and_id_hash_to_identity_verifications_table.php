<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            $table->string('id_number_hash', 64)->nullable()->index()->after('document_original_name');
            $table->string('selfie_path')->nullable()->after('id_number_hash');
            $table->string('selfie_source', 10)->nullable()->after('selfie_path'); // camera or upload
        });
    }

    public function down(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            $table->dropColumn(['id_number_hash', 'selfie_path', 'selfie_source']);
        });
    }
};
