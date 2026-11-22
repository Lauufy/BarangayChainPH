<?php

namespace Tests\Feature\Security;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Covers output and exit behavior of the RLS check command. */
class SecurityCheckRlsTest extends TestCase
{
    public function test_command_exits_cleanly_with_a_message_on_sqlite(): void
    {
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        $this->artisan('security:check-rls')
            ->expectsOutput('RLS check skipped: the active database connection is not PostgreSQL.')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_command_lists_public_tables_and_fails_if_any_have_rls_disabled(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL is not configured for this test run.');
        }

        $tables = DB::select(<<<'SQL'
            SELECT c.relname AS table_name, c.relrowsecurity AS rls_enabled
            FROM pg_catalog.pg_class AS c
            INNER JOIN pg_catalog.pg_namespace AS n ON n.oid = c.relnamespace
            WHERE n.nspname = 'public'
                AND c.relkind IN ('r', 'p')
            ORDER BY c.relname
            SQL);
        $hasDisabledTable = collect($tables)->contains(
            fn (object $table): bool => ! ($table->rls_enabled === true
                || in_array(strtolower((string) $table->rls_enabled), ['1', 't', 'true'], true))
        );

        $exitCode = Artisan::call('security:check-rls');
        $output = Artisan::output();

        $this->assertStringContainsString('Public base table', $output);
        $this->assertStringContainsString('outside the base-table scope', $output);
        foreach ($tables as $table) {
            $this->assertStringContainsString($table->table_name, $output);
        }
        $this->assertSame($hasDisabledTable ? Command::FAILURE : Command::SUCCESS, $exitCode);
    }
}
