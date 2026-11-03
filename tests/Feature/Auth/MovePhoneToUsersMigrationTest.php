<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MovePhoneToUsersMigrationTest extends TestCase
{
    private const MIGRATION_PATH = 'migrations/2026_11_03_000001_move_phone_to_users.php';

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    public function test_rollback_restores_each_user_phone_to_its_profile(): void
    {
        $citizen = User::factory()->create([
            'phone' => '+639171234567',
            'phone_verified_at' => now(),
        ]);
        $citizen->citizenProfile()->create([]);

        $employee = User::factory()->create([
            'phone' => '+639181234567',
            'phone_verified_at' => now(),
        ]);
        $employee->employeeProfile()->create([
            'employee_id' => 'EMP-1',
            'department' => 'Office',
            'position' => 'Staff',
        ]);

        $this->assertSame(0, Artisan::call('migrate:rollback', [
            '--path' => [database_path(self::MIGRATION_PATH)],
            '--realpath' => true,
            '--force' => true,
        ]));

        $this->assertDatabaseHas('citizen_profiles', [
            'user_id' => $citizen->id,
            'phone' => '+639171234567',
        ]);
        $this->assertDatabaseHas('employee_profiles', [
            'user_id' => $employee->id,
            'phone' => '+639181234567',
        ]);
    }

    public function test_preserves_profiles_while_moving_legacy_phone_numbers(): void
    {
        $citizen = User::factory()->create();
        $duplicate = User::factory()->create();
        $junk = User::factory()->create();
        $formatted = User::factory()->create();

        $this->assertSame(0, Artisan::call('migrate:rollback', [
            '--path' => [database_path(self::MIGRATION_PATH)],
            '--realpath' => true,
            '--force' => true,
        ]));

        DB::table('citizen_profiles')->insert([
            [
                'user_id' => $citizen->id,
                'phone' => '09171234567',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $junk->id,
                'phone' => 'junk text',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('employee_profiles')->insert([
            [
                'user_id' => $duplicate->id,
                'employee_id' => 'EMP-1',
                'department' => 'Office',
                'position' => 'Staff',
                'phone' => '+63 917 123 4567',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $formatted->id,
                'employee_id' => 'EMP-2',
                'department' => 'Office',
                'position' => 'Staff',
                'phone' => '+63 918-765-4321',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->assertDatabaseHas('citizen_profiles', [
            'user_id' => $citizen->id,
            'phone' => '09171234567',
        ]);
        $this->assertDatabaseCount('citizen_profiles', 2);
        $this->assertDatabaseCount('employee_profiles', 2);
        $this->assertSame(1, DB::scalar('PRAGMA foreign_keys'));
        $this->assertProfilesCascadeOnUserDelete();

        $this->assertSame(0, Artisan::call('migrate', [
            '--path' => [database_path(self::MIGRATION_PATH)],
            '--realpath' => true,
            '--force' => true,
        ]));

        $this->assertSame(1, DB::scalar('PRAGMA foreign_keys'));
        $this->assertFalse(Schema::hasColumn('citizen_profiles', 'phone'));
        $this->assertFalse(Schema::hasColumn('employee_profiles', 'phone'));
        $this->assertSame([
            'phones' => [
                $citizen->id => '+639171234567',
                $duplicate->id => null,
                $junk->id => null,
                $formatted->id => '+639187654321',
            ],
            'profile_rows' => 4,
        ], [
            'phones' => User::whereIn('id', [$citizen->id, $duplicate->id, $junk->id, $formatted->id])
                ->orderBy('id')
                ->pluck('phone', 'id')
                ->all(),
            'profile_rows' => DB::table('citizen_profiles')->count() + DB::table('employee_profiles')->count(),
        ]);
    }

    private function assertProfilesCascadeOnUserDelete(): void
    {
        foreach (['citizen_profiles', 'employee_profiles'] as $profileTable) {
            $foreignKeys = DB::select("PRAGMA foreign_key_list('{$profileTable}')");

            $this->assertContains(
                ['table' => 'users', 'from' => 'user_id', 'on_delete' => 'CASCADE'],
                array_map(
                    static fn (object $foreignKey): array => [
                        'table' => $foreignKey->table,
                        'from' => $foreignKey->from,
                        'on_delete' => $foreignKey->on_delete,
                    ],
                    $foreignKeys
                )
            );
        }
    }
}
