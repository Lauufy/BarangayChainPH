<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('citizen_profiles', function (Blueprint $table) {
            $table->string('civil_status')->nullable();
            $table->string('nationality')->nullable();
            $table->text('religion')->nullable();
            $table->string('gender')->nullable();
            $table->string('house_number')->nullable();
            $table->string('street')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('province')->nullable();
            $table->string('zip_code', 10)->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('occupation')->nullable();
        });

        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('office')->nullable();
            $table->date('date_hired')->nullable();
        });

        $timestamp = now();

        DB::table('users')
            ->whereIn('role', ['staff', 'admin'])
            ->orderBy('id')
            ->get(['id', 'role'])
            ->each(function (object $user) use ($timestamp): void {
                $employeeId = 'BSLCNPH-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);
                $profile = DB::table('employee_profiles')
                    ->where('user_id', $user->id)
                    ->first();

                if ($profile) {
                    DB::table('employee_profiles')
                        ->where('user_id', $user->id)
                        ->update([
                            'employee_id' => $employeeId,
                            'updated_at' => $timestamp,
                        ]);

                    return;
                }

                DB::table('employee_profiles')->insert([
                    'user_id' => $user->id,
                    'employee_id' => $employeeId,
                    'department' => $user->role === 'admin' ? 'Administration' : 'Unassigned',
                    'position' => $user->role === 'admin' ? 'Administrator' : 'Staff',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn(['office', 'date_hired']);
        });

        Schema::table('citizen_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'civil_status',
                'nationality',
                'religion',
                'gender',
                'house_number',
                'street',
                'city_municipality',
                'province',
                'zip_code',
                'place_of_birth',
                'occupation',
            ]);
        });
    }
};
