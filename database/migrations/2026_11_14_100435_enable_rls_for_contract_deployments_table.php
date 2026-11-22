<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // RLS is a Postgres feature, so skip on other databases
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE "public"."contract_deployments" ENABLE ROW LEVEL SECURITY');
    }

    public function down(): void {}
};
