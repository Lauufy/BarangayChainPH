<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\EmailVerificationOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Makes and checks one-time codes. The code is never stored in plain text.
 * An "otp" array lives in the session: purpose, channel, to, name, payload, hash, expires.
 */
class OtpService
{
    public function __construct(private TextbeeSms $sms) {}

    /** Start a new OTP (purpose: register, login or reset) and send the first code. */
    public function start(string $purpose, string $channel, string $to, string $name, array $payload = []): array
    {
        return $this->send(compact('purpose', 'channel', 'to', 'name', 'payload'));
    }

    /** Start the login 2FA code for a citizen. */
    public function startLogin(User $user, string $channel, bool $remember): array
    {
        $to = $channel === 'sms' ? $user->phone : $user->email;

        return $this->start('login', $channel, $to, $user->name, [
            'user_id' => $user->id,
            'remember' => $remember,
        ]);
    }

    /** Send a fresh code for an OTP array. Returns the array with the new hash and expiry. */
    public function send(array $otp): array
    {
        $isSms = $otp['channel'] === 'sms';
        $limitKey = 'sms-limit:'.$otp['to'];

        // The textbee free plan is small, so allow only 5 texts per hour for each number.
        if ($isSms && RateLimiter::tooManyAttempts($limitKey, 6)) {
            $this->fail('Too many codes were requested for this number. Please try again later.');
        }

        $code = (string) random_int(100000, 999999);

        try {
            if ($isSms) {
                $this->sms->send($otp['to'], "Your BarangayChain PH code is {$code}. It expires in 10 minutes. Do not share it.");
            } else {
                Notification::route('mail', $otp['to'])->notify(new EmailVerificationOtp($code, $otp['name']));
            }
        } catch (\Throwable $e) {
            report($e);
            $this->fail('We could not send your code. Please try again later.');
        }

        if ($isSms) {
            RateLimiter::hit($limitKey, 3600);
        }

        return array_merge($otp, [
            'hash' => Hash::make($code),
            'expires' => now()->addMinutes(10)->timestamp,
        ]);
    }

    public function matches(array $otp, string $code): bool
    {
        return now()->timestamp <= $otp['expires'] && Hash::check($code, $otp['hash']);
    }

    /** Ways a citizen can receive the login code: ['email' => 'j***@x.com', 'sms' => '+63917****567'] */
    public function channelsFor(User $user): array
    {
        return array_filter([
            'email' => $user->email ? $this->mask('email', $user->email) : null,
            // A phone must be verified before it can receive login codes.
            'sms' => $user->phone_verified_at ? $this->mask('sms', $user->phone) : null,
        ]);
    }

    public function mask(string $channel, string $to): string
    {
        return $channel === 'sms'
            ? substr($to, 0, 6).'****'.substr($to, -3)
            : substr($to, 0, 1).'***'.strstr($to, '@');
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['otp' => $message]);
    }
}
