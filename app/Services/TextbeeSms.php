<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextbeeSms
{
    public function send(string $to, string $message): void
    {
        $key = config('services.textbee.key');

        if (blank($key)) {
            if (app()->isLocal()) {
                $this->logLocally($to, $message);

                return;
            }

            // Never try to send without a key outside local development.
            throw new \RuntimeException('SMS gateway is not configured.');
        }

        Http::withHeaders(['x-api-key' => $key])
            ->timeout(15)
            ->post(
                'https://api.textbee.dev/api/v1/gateway/devices/'.config('services.textbee.device').'/send-sms',
                ['recipients' => [$to], 'message' => $message]
            )
            ->throw();
    }

    // Local development only: the code is hidden unless TEXTBEE_LOG_CODES is turned on.
    private function logLocally(string $to, string $message): void
    {
        $showCode = (bool) config('services.textbee.log_codes', false);

        Log::info('SMS (local, no API key)', [
            'to' => $this->maskPhone($to),
            'message' => $showCode ? $message : preg_replace('/\d{4,8}/', '******', $message),
        ]);
    }

    private function maskPhone(string $phone): string
    {
        $length = strlen($phone);

        return $length > 6
            ? substr($phone, 0, 4).str_repeat('*', $length - 6).substr($phone, -2)
            : '****';
    }
}
