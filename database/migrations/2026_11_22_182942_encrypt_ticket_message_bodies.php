<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ticket_messages')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                if (blank($row->body) || $this->isEncrypted($row->body)) {
                    continue;
                }

                DB::table('ticket_messages')
                    ->where('id', $row->id)
                    ->update(['body' => Crypt::encryptString($row->body)]);
            }
        });
    }

    public function down(): void
    {
        DB::table('ticket_messages')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                if (blank($row->body)) {
                    continue;
                }

                DB::table('ticket_messages')
                    ->where('id', $row->id)
                    ->update(['body' => Crypt::decryptString($row->body)]);
            }
        });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
