<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('citizen_profiles', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->string('barangay')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citizen_profiles', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
            $table->string('barangay')->nullable(false)->change();
        });
    }
};
