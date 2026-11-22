<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Account status for every account: active, inactive, suspended
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('active')->after('role');
        });

        // Copy the old is_active value, if that column exists
        if (Schema::hasColumn('users', 'is_active')) {
            DB::table('users')->where('is_active', false)->update(['status' => 'inactive']);
        }

        // Employment type for staff/admin: full_time, part_time, contract
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('employment_type')->nullable()->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('status'));
        Schema::table('employee_profiles', fn (Blueprint $table) => $table->dropColumn('employment_type'));
    }
};
