<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('security:check-rls')]
#[Description('List public base tables and report whether row-level security is enabled.')]
/** Reports whether public-schema base tables have row-level security enabled. */
class SecurityCheckRls extends Command
{
    public function handle(): int
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'pgsql') {
            $this->info('RLS check skipped: the active database connection is not PostgreSQL.');

            return self::SUCCESS;
        }

        $tables = $connection->select(<<<'SQL'
            SELECT c.relname AS table_name, c.relrowsecurity AS rls_enabled
            FROM pg_catalog.pg_class AS c
            INNER JOIN pg_catalog.pg_namespace AS n ON n.oid = c.relnamespace
            WHERE n.nspname = 'public'
                AND c.relkind IN ('r', 'p')
            ORDER BY c.relname
            SQL);

        $rows = [];
        $hasDisabledTable = false;

        foreach ($tables as $table) {
            $enabled = $this->rlsEnabled($table->rls_enabled);
            $hasDisabledTable = $hasDisabledTable || ! $enabled;
            $rows[] = [$table->table_name, $enabled ? 'ENABLED' : 'DISABLED'];
        }

        $this->table(['Public base table', 'RLS'], $rows);
        $this->line('Scope: regular and partitioned tables in public; other relation types are outside the base-table scope.');

        if ($rows === []) {
            $this->info('No public-schema base tables found.');
        }

        if ($hasDisabledTable) {
            $this->error('RLS is disabled on one or more public base tables.');

            return self::FAILURE;
        }

        $this->info('RLS is enabled on every public base table.');

        return self::SUCCESS;
    }

    private function rlsEnabled(mixed $value): bool
    {
        return $value === true || in_array(strtolower((string) $value), ['1', 't', 'true'], true);
    }
}
