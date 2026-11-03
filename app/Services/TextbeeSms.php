<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextbeeSms
{
    public function send(string $to, string $message): void
    {
        $key = config('services.textbee.key');

        // While developing without a textbee key, write the SMS to the log instead.
        if (blank($key) && app()->isLocal()) {
            Log::info("SMS to {$to}: {$message}");

            return;
        }

        Http::withHeaders(['x-api-key' => $key])
            ->timeout(15)
            ->post(
                'https://api.textbee.dev/api/v1/gateway/devices/'.config('services.textbee.device').'/send-sms',
                ['recipients' => [$to], 'message' => $message]
            )
            ->throw();
    }
}
