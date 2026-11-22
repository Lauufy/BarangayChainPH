<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'pgsql') {
            return;
        }

        $tables = $connection->select(<<<'SQL'
            SELECT c.relname AS table_name
            FROM pg_catalog.pg_class AS c
            INNER JOIN pg_catalog.pg_namespace AS n ON n.oid = c.relnamespace
            WHERE n.nspname = 'public'
                AND c.relkind IN ('r', 'p')
            ORDER BY c.relname
            SQL);

        foreach ($tables as $table) {
            $tableName = str_replace('"', '""', (string) $table->table_name);
            $connection->statement('ALTER TABLE "public"."'.$tableName.'" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void {}
};
