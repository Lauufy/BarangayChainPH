<?php

use App\Support\Phone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Phone-only accounts have no email.
            $table->string('email')->nullable()->change();
            $table->string('phone', 20)->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();
        });

        // Copy the old phone numbers (saved as +639XXXXXXXXX) into the users table.
        foreach (['citizen_profiles', 'employee_profiles'] as $profiles) {
            foreach (DB::table($profiles)->get(['user_id', 'phone']) as $row) {
                $phone = Phone::normalize($row->phone);

                if ($phone && ! DB::table('users')->where('phone', $phone)->exists()) {
                    DB::table('users')->where('id', $row->user_id)->update(['phone' => $phone]);
                }
            }

            Schema::table($profiles, fn (Blueprint $table) => $table->dropColumn('phone'));
        }
    }

    public function down(): void
    {
        foreach (['citizen_profiles', 'employee_profiles'] as $profiles) {
            Schema::table($profiles, fn (Blueprint $table) => $table->string('phone', 30)->nullable());

            foreach (DB::table('users')->whereNotNull('phone')->get(['id', 'phone']) as $user) {
                DB::table($profiles)->where('user_id', $user->id)->update(['phone' => $user->phone]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn(['phone', 'phone_verified_at']);
        });
    }
};
