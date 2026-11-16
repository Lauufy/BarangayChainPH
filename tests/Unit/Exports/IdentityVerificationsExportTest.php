<?php

namespace Tests\Unit\Exports;

use App\Exports\IdentityVerificationsExport;
use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IdentityVerificationsExportTest extends TestCase
{
    public function test_headings_and_row_omit_private_verification_files_and_hashes(): void
    {
        $verification = new IdentityVerification([
            'status' => 'verified',
            'submitted_at' => '2026-11-16 13:15:00',
            'document_path' => 'private/document.jpg',
            'selfie_path' => 'private/selfie.jpg',
            'id_number_hash' => 'private-hash',
        ]);
        $verification->setRelation('user', new User(['name' => 'Alex Citizen']));

        $export = new IdentityVerificationsExport;

        $this->assertSame(['Citizen', 'Submitted', 'Status'], $export->headings());
        $this->assertSame([
            'Alex Citizen',
            Carbon::parse('2026-11-16 13:15:00')->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            'verified',
        ], $export->row($verification));
    }
}
