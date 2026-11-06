<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Enables or disables PostgreSQL row-level security for a table. */
final class Rls
{
    public static function enable(string $table): void
    {
        self::setEnabled($table, true);
    }

    public static function disable(string $table): void
    {
        self::setEnabled($table, false);
    }

    private static function setEnabled(string $table, bool $enabled): void
    {
        if (preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/', $table) !== 1) {
            throw new InvalidArgumentException('RLS table names must be simple SQL identifiers.');
        }

        $connection = DB::connection();

        if ($connection->getDriverName() !== 'pgsql') {
            return;
        }

        $quotedTable = '"'.str_replace('"', '""', $table).'"';
        $operation = $enabled ? 'ENABLE' : 'DISABLE';

        $connection->statement("ALTER TABLE {$quotedTable} {$operation} ROW LEVEL SECURITY");
    }
}
