<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Fields that are now saved encrypted.
    private const FIELDS = ['address', 'place_of_birth', 'occupation', 'house_number', 'street'];

    public function up(): void
    {
        // Encrypted text is longer than the original, so these columns must be "text".
        Schema::table('citizen_profiles', function (Blueprint $table) {
            $table->text('place_of_birth')->nullable()->change();
            $table->text('occupation')->nullable()->change();
            $table->text('house_number')->nullable()->change();
            $table->text('street')->nullable()->change();
        });

        // Encrypt the data that is already saved (safe to run twice).
        DB::table('citizen_profiles')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                $updates = [];

                foreach (self::FIELDS as $field) {
                    $value = $row->{$field};

                    if (blank($value) || $this->isEncrypted($value)) {
                        continue;
                    }

                    $updates[$field] = Crypt::encryptString($value);
                }

                if ($updates !== []) {
                    DB::table('citizen_profiles')->where('id', $row->id)->update($updates);
                }
            }
        });
    }

    public function down(): void
    {
        // Put the readable values back (the columns stay "text").
        DB::table('citizen_profiles')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                $updates = [];

                foreach (self::FIELDS as $field) {
                    $value = $row->{$field};

                    if (blank($value) || ! $this->isEncrypted($value)) {
                        continue;
                    }

                    $updates[$field] = Crypt::decryptString($value);
                }

                if ($updates !== []) {
                    DB::table('citizen_profiles')->where('id', $row->id)->update($updates);
                }
            }
        });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
};
