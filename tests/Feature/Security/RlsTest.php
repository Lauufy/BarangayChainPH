<?php

namespace Tests\Feature\Security;

use App\Support\Rls;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Covers the RLS helper behavior on SQLite and PostgreSQL. */
class RlsTest extends TestCase
{
    public function test_helper_and_audit_migration_are_no_ops_on_sqlite(): void
    {
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('audit_logs', fn (Blueprint $table) => $table->id());

        Rls::enable('audit_logs');
        Rls::disable('audit_logs');

        $migration = require database_path('migrations/2026_11_06_121246_enable_rls_for_audit_logs.php');
        $migration->up();
        $migration->down();

        $this->assertSame(['id'], Schema::getColumnListing('audit_logs'));
    }

    public function test_helper_enables_and_disables_rls_idempotently_on_postgres(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL is not configured for this test run.');
        }

        $table = 'rls_test_'.Str::lower(Str::random(12));
        DB::statement("CREATE TEMPORARY TABLE \"{$table}\" (id bigint)");

        try {
            Rls::enable($table);
            Rls::enable($table);

            $enabled = DB::selectOne(
                'SELECT relrowsecurity::text AS enabled FROM pg_catalog.pg_class WHERE relname = ? AND relnamespace = pg_my_temp_schema()',
                [$table]
            );

            $this->assertSame('true', $enabled->enabled);

            Rls::disable($table);

            $disabled = DB::selectOne(
                'SELECT relrowsecurity::text AS enabled FROM pg_catalog.pg_class WHERE relname = ? AND relnamespace = pg_my_temp_schema()',
                [$table]
            );

            $this->assertSame('false', $disabled->enabled);
        } finally {
            DB::statement("DROP TABLE IF EXISTS \"{$table}\"");
        }
    }

    public function test_helper_rejects_unsafe_table_identifiers(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Rls::enable('audit_logs; DROP TABLE users');
    }
}
