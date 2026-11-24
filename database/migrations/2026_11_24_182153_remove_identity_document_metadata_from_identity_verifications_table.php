<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            if (! Schema::hasColumn('identity_verifications', 'document_back_key')) {
                $table->string('document_back_key')->nullable();
            }
        });

        if (Schema::hasColumn('identity_verifications', 'document_back_path')) {
            DB::table('identity_verifications')
                ->whereNull('document_back_key')
                ->whereNotNull('document_back_path')
                ->update(['document_back_key' => DB::raw('document_back_path')]);
        }

        Schema::table('identity_verifications', function (Blueprint $table) {
            foreach ([
                'document_original_name',
                'document_back_path',
                'document_back_original_name',
                'document_validation_requires_review',
            ] as $column) {
                if (Schema::hasColumn('identity_verifications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            if (! Schema::hasColumn('identity_verifications', 'document_back_path')) {
                $table->string('document_back_path')->nullable();
            }
        });

        if (Schema::hasColumn('identity_verifications', 'document_back_key')) {
            DB::table('identity_verifications')
                ->whereNull('document_back_path')
                ->whereNotNull('document_back_key')
                ->update(['document_back_path' => DB::raw('document_back_key')]);
        }

        Schema::table('identity_verifications', function (Blueprint $table) {
            if (Schema::hasColumn('identity_verifications', 'document_back_key')) {
                $table->dropColumn('document_back_key');
            }

            if (! Schema::hasColumn('identity_verifications', 'document_original_name')) {
                $table->string('document_original_name')->nullable();
            }
            if (! Schema::hasColumn('identity_verifications', 'document_back_original_name')) {
                $table->string('document_back_original_name')->nullable();
            }
            if (! Schema::hasColumn('identity_verifications', 'document_validation_requires_review')) {
                $table->boolean('document_validation_requires_review')->default(false);
            }
        });
    }
};
