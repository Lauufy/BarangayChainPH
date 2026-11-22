<?php

namespace Tests\Unit\Exports;

use App\Exports\TicketsExport;
use App\Models\ServiceRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TicketsExportTest extends TestCase
{
    public function test_headings_and_row_export_ticket_metadata_without_conversation_bodies(): void
    {
        $serviceRequest = new ServiceRequest([
            'applicant_name' => 'Alex Citizen',
            'reference_number' => 'BRGY-007',
        ]);
        $serviceRequest->setRelation('user', new User(['email' => 'alex@example.test']));
        $ticket = new Ticket;
        $ticket->setRawAttributes([
            'status' => 'open',
            'updated_at' => '2026-11-16 13:15:00',
        ]);
        $ticket->setRelation('serviceRequest', $serviceRequest);

        $export = new TicketsExport;

        $this->assertSame(['Citizen', 'Citizen Email', 'Reference', 'Last Activity', 'Status'], $export->headings());
        $this->assertSame([
            'Alex Citizen',
            'alex@example.test',
            'BRGY-007',
            Carbon::parse('2026-11-16 13:15:00')->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            'open',
        ], $export->row($ticket));
    }
}
